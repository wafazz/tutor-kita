<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with(['parent', 'tutor', 'booking.subject'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
        ]);
    }

    public function destroy(Review $review)
    {
        $tutorId = $review->tutor_id;
        $review->delete();

        $avg = Review::where('tutor_id', $tutorId)->avg('rating');
        $tutor = \App\Models\User::find($tutorId);
        $tutor?->tutorProfile?->update(['rating_avg' => $avg ? round($avg, 2) : 0]);

        return redirect()->back()->with('success', 'Review deleted.');
    }
}
