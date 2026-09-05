<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TutorPayout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    /**
     * Bookings that may owe this tutor money.
     *
     * Attribution is per booking, not per payment: a grouped request raises one
     * payment covering several tutors. How much is actually owed depends on the
     * package's payout policy, so the amount comes from payableNow().
     *
     * The period, when given, is matched on session dates via the booking —
     * payments.session_id is not populated by any flow.
     */
    private function payableBookings(int $tutorId, ?string $periodStart = null, ?string $periodEnd = null)
    {
        return Booking::where('tutor_id', $tutorId)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->with(['payment:id,status,paid_at', 'tutorRequest.package', 'sessions'])
            ->when($periodStart && $periodEnd, function ($q) use ($periodStart, $periodEnd) {
                // What puts a booking in a period depends on its policy: work
                // delivered for session-based packages, the payment itself for
                // upfront ones, which may have no sessions at all yet.
                $q->where(function ($outer) use ($periodStart, $periodEnd) {
                    $outer->whereHas(
                        'sessions',
                        fn ($sq) => $sq->whereBetween('session_date', [$periodStart, $periodEnd])
                    )->orWhere(function ($upfront) use ($periodStart, $periodEnd) {
                        $upfront->whereHas(
                            'tutorRequest.package',
                            fn ($pq) => $pq->where('payout_policy', 'upfront')
                        )->whereHas(
                            'payment',
                            fn ($pq) => $pq->where('status', 'success')
                                ->whereBetween('paid_at', [$periodStart.' 00:00:00', $periodEnd.' 23:59:59'])
                        );
                    });
                });
            });
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
                // Earned under each package's policy, less what has been paid.
                $unpaid = $this->payableBookings($tutor->id)->get()
                    ->sum(fn ($b) => $b->payableNow());

                return [
                    'id' => $tutor->id,
                    'name' => $tutor->name,
                    'email' => $tutor->email,
                    'unpaid_amount' => round($unpaid, 2),
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

        // Select and record under one transaction so two concurrent runs cannot
        // pay the same accrual twice.
        $result = DB::transaction(function () use ($request) {
            $bookings = $this->payableBookings(
                (int) $request->tutor_id,
                $request->period_start,
                $request->period_end
            )->lockForUpdate()->get();

            // Only what each booking has earned and not yet been paid.
            $slices = $bookings
                ->map(fn ($b) => ['booking' => $b, 'amount' => $b->payableNow()])
                ->filter(fn ($s) => $s['amount'] > 0)
                ->values();

            if ($slices->isEmpty()) {
                return null;
            }

            $amount = round((float) $slices->sum('amount'), 2);

            $payout = TutorPayout::create([
                'tutor_id' => $request->tutor_id,
                'amount' => $amount,
                'sessions_count' => $slices->sum(
                    fn ($s) => $s['booking']->sessions
                        ->whereBetween('session_date', [$request->period_start, $request->period_end])
                        ->count()
                ),
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'status' => 'pending',
            ]);

            foreach ($slices as $slice) {
                $booking = $slice['booking'];

                $payout->bookings()->attach($booking->id, ['amount' => $slice['amount']]);

                // Running total is the guard: this money cannot be paid again.
                $booking->increment('paid_out_amount', $slice['amount']);
            }

            return $payout;
        });

        if (! $result) {
            return back()->with('error', 'Nothing has accrued for this tutor in this period.');
        }

        return redirect()->route('admin.payouts.index')
            ->with('success', "Payout of RM {$result->amount} created for {$result->sessions_count} session(s).");
    }

    public function show(TutorPayout $payout)
    {
        $payout->load('tutor');

        // The bookings this payout actually claimed — a historical record, not
        // re-derived from the period, so it stays accurate as data changes.
        $bookings = $payout->bookings()
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
