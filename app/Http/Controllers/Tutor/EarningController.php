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
        // request raises one payment covering several tutors, so reading the
        // payment total would credit the whole group to a single tutor.
        $earned = Booking::where('tutor_id', $tutorId)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'));

        $totalEarned = (clone $earned)->sum('tutor_payout');

        $monthEarned = (clone $earned)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year))
            ->sum('tutor_payout');

        $totalPaid = TutorPayout::where('tutor_id', $tutorId)
            ->where('status', 'paid')
            ->sum('amount');

        $pendingPayout = TutorPayout::where('tutor_id', $tutorId)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        $recentEarnings = (clone $earned)
            ->with(['student:id,name', 'subject:id,name', 'payment:id,paid_at'])
            ->orderByDesc('id')
            ->take(10)
            ->get();

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
