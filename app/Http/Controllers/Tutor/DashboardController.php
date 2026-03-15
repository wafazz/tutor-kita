<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\TutorPayout;
use App\Models\TutorRequest;
use App\Models\TutorSession;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $now = Carbon::now();

        $stats = [
            'activeBookings' => Booking::where('tutor_id', $user->id)
                ->whereIn('status', ['confirmed', 'active'])->count(),
            'totalBookings' => Booking::where('tutor_id', $user->id)->count(),
            'monthSessions' => TutorSession::whereHas('booking', fn ($q) => $q->where('tutor_id', $user->id))
                ->whereMonth('session_date', $now->month)
                ->whereYear('session_date', $now->year)
                ->where('status', 'completed')
                ->count(),
            'totalSessions' => TutorSession::whereHas('booking', fn ($q) => $q->where('tutor_id', $user->id))
                ->where('status', 'completed')
                ->count(),
            'pendingOffers' => TutorRequest::where('matched_tutor_id', $user->id)
                ->where('status', 'open')
                ->count(),
            'rating' => $user->tutorProfile?->rating_avg ?? 0,
            'totalStudents' => Booking::where('tutor_id', $user->id)
                ->whereIn('status', ['confirmed', 'active', 'completed'])
                ->distinct('student_id')
                ->count('student_id'),
            'profileComplete' => $user->tutorProfile?->verification_status ?? 'pending',
        ];

        $totalCommission = Payment::whereHas('booking', fn ($q) => $q->where('tutor_id', $user->id))
            ->where('status', 'success')
            ->sum('tutor_payout');

        $paidCommission = TutorPayout::where('tutor_id', $user->id)
            ->where('status', 'paid')
            ->sum('amount');

        $availableCommission = $totalCommission - $paidCommission;

        $commission = [
            'total' => number_format($totalCommission, 2),
            'available' => number_format($availableCommission, 2),
            'paid' => number_format($paidCommission, 2),
        ];

        $upcomingSessions = TutorSession::whereHas('booking', fn ($q) => $q->where('tutor_id', $user->id))
            ->with(['booking.parent', 'booking.student', 'booking.subject'])
            ->where('status', 'scheduled')
            ->where('session_date', '>=', $now->toDateString())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'date' => $s->session_date->format('M d, Y'),
                'time' => $s->start_time,
                'student' => $s->booking->student->name,
                'subject' => $s->booking->subject->name,
                'parent' => $s->booking->parent->name,
                'location_type' => $s->booking->location_type,
            ]);

        $pendingJobs = TutorRequest::with(['parent', 'subject', 'student'])
            ->where('matched_tutor_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'parent_name' => $r->parent->name,
                'student_name' => $r->student->name ?? '-',
                'subject_name' => $r->subject->name,
                'area' => $r->preferred_area,
            ]);

        $recentSessions = TutorSession::whereHas('booking', fn ($q) => $q->where('tutor_id', $user->id))
            ->with(['booking.student', 'booking.subject'])
            ->where('status', 'completed')
            ->orderBy('session_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'date' => $s->session_date->format('M d, Y'),
                'student' => $s->booking->student->name,
                'subject' => $s->booking->subject->name,
                'duration' => $s->duration_minutes,
                'confirmed' => $s->parent_confirmed,
            ]);

        return Inertia::render('Tutor/Dashboard', [
            'stats' => $stats,
            'commission' => $commission,
            'upcomingSessions' => $upcomingSessions,
            'pendingJobs' => $pendingJobs,
            'recentSessions' => $recentSessions,
        ]);
    }
}
