<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::where('tutor_id', auth()->id())
            ->with(['parent', 'student', 'subject'])
            ->latest()
            ->get();

        return Inertia::render('Tutor/Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    public function show(Booking $booking)
    {
        abort_unless($booking->tutor_id === auth()->id(), 403);

        $booking->load(['parent', 'student', 'subject', 'sessions']);

        return Inertia::render('Tutor/Bookings/Show', [
            'booking' => $booking,
        ]);
    }
}
