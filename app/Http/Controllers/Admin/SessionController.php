<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TutorSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $query = TutorSession::with(['booking.tutor', 'booking.parent', 'booking.student', 'booking.subject']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->orderBy('session_date', 'desc')->orderBy('start_time', 'desc')->paginate(15);

        return Inertia::render('Admin/Sessions/Index', [
            'sessions' => $sessions,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(TutorSession $session)
    {
        $session->load(['booking.tutor', 'booking.parent', 'booking.student', 'booking.subject']);

        return Inertia::render('Admin/Sessions/Show', [
            'session' => $session,
        ]);
    }

    public function manualCheckIn(Request $request, TutorSession $session)
    {
        abort_unless(in_array($session->status, ['scheduled', 'missed']), 403);

        $session->update([
            'checked_in_at' => now(),
            'check_in_method' => 'manual',
            'status' => 'checked_in',
        ]);

        return back()->with('success', 'Manual check-in recorded.');
    }

    public function manualCheckOut(TutorSession $session)
    {
        abort_unless($session->status === 'checked_in', 403);

        $duration = $session->checked_in_at->diffInMinutes(now());

        $session->update([
            'checked_out_at' => now(),
            'duration_minutes' => $duration,
            'status' => 'completed',
        ]);

        return back()->with('success', 'Manual check-out recorded. Duration: ' . $duration . ' minutes.');
    }

    public function cancel(TutorSession $session)
    {
        abort_unless(in_array($session->status, ['scheduled', 'missed']), 403);

        $session->update(['status' => 'cancelled']);

        return back()->with('success', 'Session cancelled.');
    }
}
