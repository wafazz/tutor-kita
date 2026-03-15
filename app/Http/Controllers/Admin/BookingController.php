<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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
}
