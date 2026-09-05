<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\TutorRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $query = TutorRequest::with(['parent', 'student', 'subject', 'package', 'matchedTutor']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/Requests/Index', [
            'requests' => $query->latest()->paginate(15),
            'filters' => $request->only('status'),
        ]);
    }

    public function show(TutorRequest $tutorRequest)
    {
        $tutorRequest->load(['parent', 'student', 'subject', 'package', 'matchedTutor', 'payment']);

        $allTutors = User::where('role', 'tutor')
            ->whereHas('tutorProfile', fn ($q) => $q->where('verification_status', 'verified'))
            ->with('tutorProfile')
            ->orderBy('name')
            ->get();

        // Load group siblings if part of a group
        $groupRequests = [];
        if ($tutorRequest->request_group) {
            $groupRequests = TutorRequest::where('request_group', $tutorRequest->request_group)
                ->with(['subject', 'matchedTutor'])
                ->get();
        }

        return Inertia::render('Admin/Requests/Show', [
            'tutorRequest' => $tutorRequest,
            'allTutors' => $allTutors,
            'groupRequests' => $groupRequests,
        ]);
    }

    public function match(Request $request, TutorRequest $tutorRequest)
    {
        $validated = $request->validate([
            'matched_tutor_id' => 'required|exists:users,id',
        ]);

        $tutor = User::findOrFail($validated['matched_tutor_id']);

        $tutorRequest->update([
            'status' => 'matched',
            'matched_tutor_id' => $validated['matched_tutor_id'],
            'matched_at' => now(),
        ]);

        // For grouped requests: only create payment when ALL subjects are assigned
        if ($tutorRequest->request_group) {
            $group = TutorRequest::where('request_group', $tutorRequest->request_group)->get();
            $allMatched = $group->every(fn ($r) => $r->status === 'matched');

            if ($allMatched) {
                if ($unpriced = $this->firstUnpricedRequest($group)) {
                    return redirect()->back()->with('error',
                        "Assigned to {$tutor->name}, but no payment was raised: {$unpriced} Fix it, then re-assign a tutor to that subject.");
                }

                $this->createGroupPayment($group);

                return redirect()->back()->with('success', "Assigned to {$tutor->name}. All subjects matched — payment pending from parent.");
            }

            $remaining = $group->where('status', 'open')->count();

            return redirect()->back()->with('success', "Assigned to {$tutor->name}. {$remaining} subject(s) still need tutor assignment.");
        }

        // Single request: create payment immediately
        if ($unpriced = $this->firstUnpricedRequest(collect([$tutorRequest]))) {
            return redirect()->back()->with('error',
                "Assigned to {$tutor->name}, but no payment was raised: {$unpriced}");
        }

        $this->createSinglePayment($tutorRequest, $tutor);

        $amount = $tutorRequest->calculateAmount();

        return redirect()->back()->with('success', "Request assigned to {$tutor->name}. Payment of RM ".number_format($amount, 2).' pending from parent.');
    }

    /**
     * Why a request cannot be priced, or null when it can.
     *
     * A zero price would bill the parent nothing and leave the tutor
     * unpayable, with nothing on the record to show why — so approval stops
     * here rather than raising a RM0 payment.
     */
    private function firstUnpricedRequest($requests): ?string
    {
        foreach ($requests as $request) {
            $request->loadMissing(['subject', 'package']);

            if (! $request->subject || ! $request->package) {
                return "request #{$request->id} has no subject or package attached.";
            }

            if ($request->calculateAmount() <= 0) {
                return "'{$request->subject->name}' priced at RM0 for this package — check the subject's hourly rate.";
            }
        }

        return null;
    }

    /**
     * Raise or re-price the pending payment for a request.
     *
     * updateOrCreate on its own would rewrite the amount of a payment the
     * parent has already settled — re-approving a request would silently
     * restate money that has been collected, and the pricing formula can
     * legitimately differ from what an older payment recorded. Anything past
     * 'pending' is therefore left exactly as it was charged.
     */
    private function upsertPendingPayment(int $tutorRequestId, array $attributes): void
    {
        $existing = Payment::where('tutor_request_id', $tutorRequestId)->first();

        if ($existing && $existing->status !== 'pending') {
            return;
        }

        Payment::updateOrCreate(['tutor_request_id' => $tutorRequestId], $attributes);
    }

    private function createSinglePayment(TutorRequest $tutorRequest, User $tutor): void
    {
        $amount = $tutorRequest->calculateAmount();

        $commissionRate = $tutor->tutorProfile?->commission_rate ?? Setting::defaultCommissionRate();
        $commissionAmount = $amount * ((float) $commissionRate / 100);
        $tutorPayout = $amount - $commissionAmount;

        $this->upsertPendingPayment($tutorRequest->id, [
            'parent_id' => $tutorRequest->parent_id,
            'amount' => $amount,
            'commission_amount' => $commissionAmount,
            'tutor_payout' => $tutorPayout,
            'payment_method' => 'fpx',
            'status' => 'pending',
        ]);
    }

    private function createGroupPayment($group): void
    {
        $firstRequest = $group->first();
        $totalAmount = 0;

        // Sum up price per subject based on its rate × package duration × sessions
        foreach ($group as $req) {
            $totalAmount += $req->calculateAmount();
        }

        // Calculate commission across all tutors
        $totalCommission = 0;
        foreach ($group as $req) {
            $tutor = User::find($req->matched_tutor_id);
            $rate = $tutor?->tutorProfile?->commission_rate ?? Setting::defaultCommissionRate();
            $reqAmount = $req->calculateAmount();
            $totalCommission += $reqAmount * ((float) $rate / 100);
        }
        $tutorPayout = $totalAmount - $totalCommission;

        $this->upsertPendingPayment($firstRequest->id, [
            'parent_id' => $firstRequest->parent_id,
            'amount' => round($totalAmount, 2),
            'commission_amount' => round($totalCommission, 2),
            'tutor_payout' => round($tutorPayout, 2),
            'payment_method' => 'fpx',
            'status' => 'pending',
        ]);
    }
}
