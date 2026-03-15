<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\TutorProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TutorBrowseController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = TutorProfile::where('verification_status', 'verified')
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->with('user');

        if ($request->filled('subject')) {
            $subject = $request->input('subject');
            $query->whereJsonContains('subjects', $subject);
        }

        if ($request->filled('area')) {
            $query->where('location_area', 'like', '%' . $request->input('area') . '%');
        }

        if ($request->filled('state')) {
            $query->where('location_state', $request->input('state'));
        }

        $sort = $request->input('sort', 'rating');
        if ($sort === 'rating') {
            $query->orderByDesc('rating_avg');
        } elseif ($sort === 'rate_low') {
            $query->orderBy('hourly_rate');
        } elseif ($sort === 'rate_high') {
            $query->orderByDesc('hourly_rate');
        } elseif ($sort === 'experience') {
            $query->orderByDesc('experience_years');
        }

        $tutors = $query->paginate(12)->through(fn ($tp) => [
            'id' => $tp->user_id,
            'name' => $tp->user->name,
            'subjects' => $tp->subjects ?? [],
            'education_level' => $tp->education_level,
            'experience_years' => $tp->experience_years,
            'hourly_rate' => $tp->hourly_rate,
            'location_area' => $tp->location_area,
            'location_state' => $tp->location_state,
            'rating_avg' => $tp->rating_avg,
            'total_sessions' => $tp->total_sessions,
            'bio' => $tp->bio,
        ]);

        $subjects = Subject::where('is_active', true)->pluck('name');

        $states = TutorProfile::where('verification_status', 'verified')
            ->whereNotNull('location_state')
            ->distinct()
            ->pluck('location_state');

        return Inertia::render('Tutors/Browse', [
            'tutors' => $tutors,
            'subjects' => $subjects,
            'states' => $states,
            'filters' => $request->only(['subject', 'area', 'state', 'sort']),
        ]);
    }
}
