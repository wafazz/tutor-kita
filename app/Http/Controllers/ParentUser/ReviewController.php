<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function create(Booking $booking)
    {
        abort_unless($booking->parent_id === auth()->id(), 403);
        abort_if($booking->review, 403, 'Already reviewed.');

        $booking->load(['tutor', 'student', 'subject']);

        return Inertia::render('Parent/Reviews/Create', [
            'booking' => $booking,
        ]);
    }

    public function store(Request $request, Booking $booking)
    {
        abort_unless($booking->parent_id === auth()->id(), 403);
        abort_if($booking->review, 403, 'Already reviewed.');

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Review::create([
            'booking_id' => $booking->id,
            'parent_id' => auth()->id(),
            'tutor_id' => $booking->tutor_id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        $tutor = $booking->tutor;
        $avg = Review::where('tutor_id', $tutor->id)->avg('rating');
        $tutor->tutorProfile?->update(['rating_avg' => round($avg, 2)]);

        return redirect()->route('parent.bookings.show', $booking->id)
            ->with('success', 'Review submitted successfully.');
    }
}
