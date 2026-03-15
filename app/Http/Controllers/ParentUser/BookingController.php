<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::where('parent_id', auth()->id())
            ->with(['tutor', 'student', 'subject'])
            ->latest()
            ->get();

        return Inertia::render('Parent/Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    public function show(Booking $booking)
    {
        abort_unless($booking->parent_id === auth()->id(), 403);

        $booking->load(['tutor', 'student', 'subject', 'sessions', 'review']);

        return Inertia::render('Parent/Bookings/Show', [
            'booking' => $booking,
        ]);
    }
}
