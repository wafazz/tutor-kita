<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::where('tutor_id', auth()->id())
            ->with(['parent', 'booking.subject', 'booking.student'])
            ->latest()
            ->paginate(15);

        $stats = [
            'totalReviews' => Review::where('tutor_id', auth()->id())->count(),
            'averageRating' => Review::where('tutor_id', auth()->id())->avg('rating') ?? 0,
            'fiveStars' => Review::where('tutor_id', auth()->id())->where('rating', 5)->count(),
            'fourStars' => Review::where('tutor_id', auth()->id())->where('rating', 4)->count(),
            'threeStars' => Review::where('tutor_id', auth()->id())->where('rating', 3)->count(),
            'twoStars' => Review::where('tutor_id', auth()->id())->where('rating', 2)->count(),
            'oneStar' => Review::where('tutor_id', auth()->id())->where('rating', 1)->count(),
        ];

        return Inertia::render('Tutor/Reviews/Index', [
            'reviews' => $reviews,
            'stats' => $stats,
        ]);
    }
}
