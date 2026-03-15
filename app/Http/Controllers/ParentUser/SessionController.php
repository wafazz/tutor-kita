<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\TutorSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $query = TutorSession::whereHas('booking', fn ($q) => $q->where('parent_id', auth()->id()))
            ->with(['booking.tutor', 'booking.student', 'booking.subject']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->orderBy('session_date', 'desc')->orderBy('start_time', 'desc')->paginate(15);

        return Inertia::render('Parent/Sessions/Index', [
            'sessions' => $sessions,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(TutorSession $session)
    {
        abort_unless($session->booking->parent_id === auth()->id(), 403);

        $session->load(['booking.tutor', 'booking.student', 'booking.subject']);

        return Inertia::render('Parent/Sessions/Show', [
            'session' => $session,
        ]);
    }

    public function confirm(TutorSession $session)
    {
        abort_unless($session->booking->parent_id === auth()->id(), 403);
        abort_unless($session->status === 'completed', 403);

        $session->update(['parent_confirmed' => true]);

        return back()->with('success', 'Session confirmed. Thank you!');
    }
}
