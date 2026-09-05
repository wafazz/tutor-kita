<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TutorPayout;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayoutController extends Controller
{
    /**
     * Bookings a tutor is owed for.
     *
     * Attribution is per booking rather than per payment: a grouped request
     * raises one payment covering several tutors, so summing the payment
     * would credit the whole group to whichever tutor it happens to link to.
     *
     * The period, when given, is matched on session dates via the booking —
     * payments.session_id is not populated by any flow.
     */
    private function payableBookings(int $tutorId, ?string $periodStart = null, ?string $periodEnd = null)
    {
        return Booking::where('tutor_id', $tutorId)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->when(
                $periodStart && $periodEnd,
                fn ($q) => $q->whereHas(
                    'sessions',
                    fn ($sq) => $sq->whereBetween('session_date', [$periodStart, $periodEnd])
                )
            );
    }

    public function index(Request $request)
    {
        $query = TutorPayout::with('tutor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->orderBy('created_at', 'desc')->paginate(15);

        $totals = [
            'pending' => TutorPayout::where('status', 'pending')->sum('amount'),
            'processing' => TutorPayout::where('status', 'processing')->sum('amount'),
            'paid' => TutorPayout::where('status', 'paid')->sum('amount'),
        ];

        return Inertia::render('Admin/Payouts/Index', [
            'payouts' => $payouts,
            'totals' => $totals,
            'filters' => $request->only('status'),
        ]);
    }

    public function create()
    {
        $tutors = User::where('role', 'tutor')
            ->whereHas('tutorProfile', fn ($q) => $q->where('verification_status', 'verified'))
            ->get()
            ->map(function ($tutor) {
                $earned = (float) $this->payableBookings($tutor->id)->sum('tutor_payout');

                // Everything already committed to a payout, in any state.
                $alreadyPaidOut = (float) TutorPayout::where('tutor_id', $tutor->id)->sum('amount');

                return [
                    'id' => $tutor->id,
                    'name' => $tutor->name,
                    'email' => $tutor->email,
                    'unpaid_amount' => round(max(0, $earned - $alreadyPaidOut), 2),
                ];
            })
            ->filter(fn ($t) => $t['unpaid_amount'] > 0)
            ->values();

        return Inertia::render('Admin/Payouts/Create', [
            'tutors' => $tutors,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tutor_id' => 'required|exists:users,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $bookings = $this->payableBookings(
            (int) $request->tutor_id,
            $request->period_start,
            $request->period_end
        )->with(['sessions' => fn ($q) => $q->whereBetween('session_date', [$request->period_start, $request->period_end])])
            ->get();

        $amount = round((float) $bookings->sum('tutor_payout'), 2);
        $sessionsCount = $bookings->sum(fn ($b) => $b->sessions->count());

        if ($amount <= 0) {
            return back()->with('error', 'No payable sessions found for this period.');
        }

        TutorPayout::create([
            'tutor_id' => $request->tutor_id,
            'amount' => $amount,
            'sessions_count' => $sessionsCount,
            'period_start' => $request->period_start,
            'period_end' => $request->period_end,
            'status' => 'pending',
        ]);

        return redirect()->route('admin.payouts.index')
            ->with('success', "Payout of RM {$amount} created for {$sessionsCount} session(s).");
    }

    public function show(TutorPayout $payout)
    {
        $payout->load('tutor');

        $bookings = $this->payableBookings($payout->tutor_id, $payout->period_start, $payout->period_end)
            ->with([
                'student:id,name',
                'subject:id,name',
                'payment:id,paid_at,status',
                'sessions' => fn ($q) => $q->whereBetween('session_date', [$payout->period_start, $payout->period_end]),
            ])
            ->get();

        return Inertia::render('Admin/Payouts/Show', [
            'payout' => $payout,
            'bookings' => $bookings,
        ]);
    }

    public function markProcessing(TutorPayout $payout)
    {
        abort_unless($payout->status === 'pending', 403);
        $payout->update(['status' => 'processing']);

        return back()->with('success', 'Payout marked as processing.');
    }

    public function markPaid(Request $request, TutorPayout $payout)
    {
        abort_unless(in_array($payout->status, ['pending', 'processing']), 403);

        $request->validate(['reference' => 'nullable|string|max:255']);

        $payout->update([
            'status' => 'paid',
            'paid_at' => now(),
            'reference' => $request->reference,
        ]);

        return back()->with('success', 'Payout marked as paid.');
    }
}
