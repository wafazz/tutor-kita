<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
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
                $this->createGroupPayment($group);
                return redirect()->back()->with('success', "Assigned to {$tutor->name}. All subjects matched — payment pending from parent.");
            }

            $remaining = $group->where('status', 'open')->count();
            return redirect()->back()->with('success', "Assigned to {$tutor->name}. {$remaining} subject(s) still need tutor assignment.");
        }

        // Single request: create payment immediately
        $this->createSinglePayment($tutorRequest, $tutor);

        $amount = $this->calculateAmount($tutorRequest);
        return redirect()->back()->with('success', "Request assigned to {$tutor->name}. Payment of RM " . number_format($amount, 2) . " pending from parent.");
    }

    private function calculateAmount(TutorRequest $tutorRequest): float
    {
        $tutorRequest->load(['subject', 'package']);
        $subject = $tutorRequest->subject;
        $package = $tutorRequest->package;
        if (!$subject || !$package) {
            return 0;
        }

        $location = $tutorRequest->preferred_location ?? 'home';
        $rate = $location === 'online'
            ? (float) $subject->hourly_rate_online
            : (float) $subject->hourly_rate_home;

        return $rate * (float) $package->duration_hours * $package->total_sessions;
    }

    private function createSinglePayment(TutorRequest $tutorRequest, User $tutor): void
    {
        $amount = $this->calculateAmount($tutorRequest);
        $commissionRate = $tutor->tutorProfile?->commission_rate ?? 20;
        $commissionAmount = $amount * ((float) $commissionRate / 100);
        $tutorPayout = $amount - $commissionAmount;

        Payment::updateOrCreate(
            ['tutor_request_id' => $tutorRequest->id],
            [
                'parent_id' => $tutorRequest->parent_id,
                'amount' => $amount,
                'commission_amount' => $commissionAmount,
                'tutor_payout' => $tutorPayout,
                'payment_method' => 'fpx',
                'status' => 'pending',
            ]
        );
    }

    private function createGroupPayment($group): void
    {
        $firstRequest = $group->first();
        $totalAmount = 0;

        // Sum up price per subject based on its rate × package duration × sessions
        foreach ($group as $req) {
            $totalAmount += $this->calculateAmount($req);
        }

        // Calculate commission across all tutors
        $totalCommission = 0;
        foreach ($group as $req) {
            $tutor = User::find($req->matched_tutor_id);
            $rate = $tutor?->tutorProfile?->commission_rate ?? 20;
            $reqAmount = $this->calculateAmount($req);
            $totalCommission += $reqAmount * ((float) $rate / 100);
        }
        $tutorPayout = $totalAmount - $totalCommission;

        Payment::updateOrCreate(
            ['tutor_request_id' => $firstRequest->id],
            [
                'parent_id' => $firstRequest->parent_id,
                'amount' => round($totalAmount, 2),
                'commission_amount' => round($totalCommission, 2),
                'tutor_payout' => round($tutorPayout, 2),
                'payment_method' => 'fpx',
                'status' => 'pending',
            ]
        );
    }
}
