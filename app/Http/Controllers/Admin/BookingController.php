<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\BookingCancellation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['tutor', 'parent', 'student', 'subject']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $query->latest()->paginate(15),
            'filters' => $request->only('status'),
        ]);
    }

    public function show(Booking $booking)
    {
        $booking->load(['tutor', 'parent', 'student', 'subject', 'tutorRequest', 'sessions', 'payments']);

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => $booking,
        ]);
    }

    /**
     * End a booking and settle everything attached to it.
     *
     * The tutor keeps what they taught, the remaining sessions are cancelled so
     * nothing further accrues, and the parent is owed back the undelivered
     * share less anything already paid out.
     */
    public function cancel(Request $request, Booking $booking, BookingCancellation $cancellation)
    {
        abort_if($booking->status === 'cancelled', 403, 'This booking is already cancelled.');

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $outcome = $cancellation->cancel($booking, $validated['reason'] ?? null);

        $message = sprintf(
            'Booking cancelled. %d session(s) already taught are kept, %d cancelled. Tutor keeps RM%s; RM%s is refundable to the parent.',
            $outcome['delivered'],
            $outcome['cancelled_sessions'],
            number_format($outcome['tutor_keeps'], 2),
            number_format($outcome['refundable'], 2),
        );

        return back()->with('success', $message);
    }
}
