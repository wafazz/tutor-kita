<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Student;
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
            'children' => $user->students()->count(),
            'activeBookings' => Booking::where('parent_id', $user->id)
                ->whereIn('status', ['confirmed', 'active'])->count(),
            'totalBookings' => Booking::where('parent_id', $user->id)->count(),
            'openRequests' => TutorRequest::where('parent_id', $user->id)
                ->where('status', 'open')->count(),
            'upcomingSessions' => TutorSession::whereHas('booking', fn ($q) => $q->where('parent_id', $user->id))
                ->where('status', 'scheduled')
                ->where('session_date', '>=', $now->toDateString())
                ->count(),
            'completedSessions' => TutorSession::whereHas('booking', fn ($q) => $q->where('parent_id', $user->id))
                ->where('status', 'completed')
                ->count(),
            'pendingConfirm' => TutorSession::whereHas('booking', fn ($q) => $q->where('parent_id', $user->id))
                ->where('status', 'completed')
                ->where('parent_confirmed', false)
                ->count(),
            'activeTutors' => Booking::where('parent_id', $user->id)
                ->whereIn('status', ['confirmed', 'active'])
                ->distinct('tutor_id')
                ->count('tutor_id'),
        ];

        $upcomingSessions = TutorSession::whereHas('booking', fn ($q) => $q->where('parent_id', $user->id))
            ->with(['booking.tutor', 'booking.student', 'booking.subject'])
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

        $activeRequests = TutorRequest::with(['subject', 'student'])
            ->where('parent_id', $user->id)
            ->whereIn('status', ['open', 'matched'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'subject_name' => $r->subject->name,
                'student_name' => $r->student->name ?? '-',
                'area' => $r->preferred_area,
                'status' => $r->status,
            ]);

        $sessionsToConfirm = TutorSession::whereHas('booking', fn ($q) => $q->where('parent_id', $user->id))
            ->with(['booking.tutor', 'booking.student', 'booking.subject'])
            ->where('status', 'completed')
            ->where('parent_confirmed', false)
            ->orderBy('session_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'date' => $s->session_date->format('M d, Y'),
                'tutor' => $s->booking->tutor->name,
                'student' => $s->booking->student->name,
                'subject' => $s->booking->subject->name,
                'duration' => $s->duration_minutes,
            ]);

        $childProgress = Student::where('parent_id', $user->id)
            ->get()
            ->map(function ($student) {
                $bookings = Booking::where('student_id', $student->id)
                    ->whereIn('status', ['confirmed', 'active', 'completed'])
                    ->with('subject')
                    ->get();

                $completedSessions = TutorSession::whereHas('booking', fn ($q) => $q->where('student_id', $student->id))
                    ->where('status', 'completed')
                    ->count();

                $totalHours = TutorSession::whereHas('booking', fn ($q) => $q->where('student_id', $student->id))
                    ->where('status', 'completed')
                    ->sum('duration_minutes');

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'school' => $student->school,
                    'education_level' => $student->education_level,
                    'subjects' => $bookings->pluck('subject.name')->unique()->values(),
                    'totalBookings' => $bookings->count(),
                    'completedSessions' => $completedSessions,
                    'totalHours' => round(intval($totalHours) / 60, 1),
                ];
            });

        return Inertia::render('Parent/Dashboard', [
            'stats' => $stats,
            'upcomingSessions' => $upcomingSessions,
            'activeRequests' => $activeRequests,
            'sessionsToConfirm' => $sessionsToConfirm,
            'childProgress' => $childProgress,
        ]);
    }
}
