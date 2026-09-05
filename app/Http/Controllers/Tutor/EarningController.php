<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TutorPayout;
use Inertia\Inertia;

class EarningController extends Controller
{
    public function index()
    {
        $tutorId = auth()->id();

        // Earnings are attributed per booking, not per payment: a grouped
        // request raises one payment covering several tutors. How much has been
        // earned depends on the package's payout policy, so it is accrued
        // per booking rather than read straight off tutor_payout.
        $bookings = Booking::where('tutor_id', $tutorId)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->with(['payment:id,status,paid_at', 'tutorRequest.package', 'sessions', 'student:id,name', 'subject:id,name'])
            ->orderByDesc('id')
            ->get();

        $totalEarned = round($bookings->sum(fn ($b) => $b->accruedPayout()), 2);

        $monthEarned = round(
            $bookings->filter(fn ($b) => $b->payment?->paid_at
                && $b->payment->paid_at->isSameMonth(now()))
                ->sum(fn ($b) => $b->accruedPayout()),
            2
        );

        $totalPaid = TutorPayout::where('tutor_id', $tutorId)
            ->where('status', 'paid')
            ->sum('amount');

        $pendingPayout = TutorPayout::where('tutor_id', $tutorId)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        $recentEarnings = $bookings->take(10)->map(fn ($b) => [
            'id' => $b->id,
            'amount' => $b->amount,
            'commission_amount' => $b->commission_amount,
            'tutor_payout' => $b->tutor_payout,
            'accrued' => $b->accruedPayout(),
            'student' => $b->student ? ['name' => $b->student->name] : null,
            'subject' => $b->subject ? ['name' => $b->subject->name] : null,
            'payment' => $b->payment ? ['paid_at' => $b->payment->paid_at] : null,
        ])->values();

        $payouts = TutorPayout::where('tutor_id', $tutorId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return Inertia::render('Tutor/Earnings/Index', [
            'stats' => [
                'totalEarned' => $totalEarned,
                'totalPaid' => $totalPaid,
                'pendingPayout' => $pendingPayout,
                'monthEarned' => $monthEarned,
                'balance' => $totalEarned - $totalPaid - $pendingPayout,
            ],
            'recentEarnings' => $recentEarnings,
            'payouts' => $payouts,
        ]);
    }
}
