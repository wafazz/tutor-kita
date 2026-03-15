<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorPayout;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\TutorSession;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $now = Carbon::now();

        $stats = [
            'totalTutors' => User::where('role', 'tutor')->count(),
            'verifiedTutors' => TutorProfile::where('verification_status', 'verified')->count(),
            'pendingTutors' => TutorProfile::where('verification_status', 'pending')->count(),
            'totalParents' => User::where('role', 'parent')->count(),
            'totalStudents' => Student::count(),
            'totalSubjects' => Subject::where('is_active', true)->count(),
            'openRequests' => TutorRequest::where('status', 'open')->count(),
            'activeBookings' => Booking::whereIn('status', ['confirmed', 'active'])->count(),
            'totalBookings' => Booking::count(),
            'todaySessions' => TutorSession::whereDate('session_date', $now->toDateString())->count(),
            'completedSessions' => TutorSession::where('status', 'completed')->count(),
            'monthSessions' => TutorSession::whereMonth('session_date', $now->month)
                ->whereYear('session_date', $now->year)
                ->count(),
            'totalSales' => (float) Payment::where('status', 'success')->sum('amount'),
            'salesThisMonth' => (float) Payment::where('status', 'success')
                ->whereMonth('paid_at', $now->month)
                ->whereYear('paid_at', $now->year)
                ->sum('amount'),
            'salesLastMonth' => (float) Payment::where('status', 'success')
                ->whereMonth('paid_at', $now->copy()->subMonth()->month)
                ->whereYear('paid_at', $now->copy()->subMonth()->year)
                ->sum('amount'),
            'commissionPaid' => (float) TutorPayout::where('status', 'paid')->sum('amount'),
            'commissionPending' => (float) Payment::where('status', 'pending')->sum('commission_amount'),
        ];

        $recentTutors = User::where('role', 'tutor')
            ->with('tutorProfile')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
                'status' => $t->tutorProfile?->verification_status ?? 'pending',
                'created_at' => $t->created_at->format('M d, Y'),
            ]);

        $recentRequests = TutorRequest::with(['parent', 'subject'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'parent_name' => $r->parent->name,
                'subject_name' => $r->subject->name,
                'area' => $r->preferred_area,
                'status' => $r->status,
                'created_at' => $r->created_at->format('M d, Y'),
            ]);

        $upcomingSessions = TutorSession::with(['booking.tutor', 'booking.student', 'booking.subject'])
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
                'tutor' => $s->booking->tutor->name,
                'student' => $s->booking->student->name,
                'subject' => $s->booking->subject->name,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentTutors' => $recentTutors,
            'recentRequests' => $recentRequests,
            'upcomingSessions' => $upcomingSessions,
        ]);
    }
}
