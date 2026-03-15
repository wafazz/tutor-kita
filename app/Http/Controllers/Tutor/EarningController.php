<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\TutorPayout;
use Inertia\Inertia;

class EarningController extends Controller
{
    public function index()
    {
        $tutorId = auth()->id();

        $totalEarned = Payment::where('status', 'success')
            ->whereHas('booking', fn ($q) => $q->where('tutor_id', $tutorId))
            ->sum('tutor_payout');

        $totalPaid = TutorPayout::where('tutor_id', $tutorId)
            ->where('status', 'paid')
            ->sum('amount');

        $pendingPayout = TutorPayout::where('tutor_id', $tutorId)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        $monthEarned = Payment::where('status', 'success')
            ->whereHas('booking', fn ($q) => $q->where('tutor_id', $tutorId))
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('tutor_payout');

        $recentPayments = Payment::where('status', 'success')
            ->whereHas('booking', fn ($q) => $q->where('tutor_id', $tutorId))
            ->with(['booking.student', 'booking.subject', 'session'])
            ->orderBy('paid_at', 'desc')
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
            'recentPayments' => $recentPayments,
            'payouts' => $payouts,
        ]);
    }
}
