<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\TutorRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobController extends Controller
{
    public function index()
    {
        $requests = TutorRequest::where('matched_tutor_id', auth()->id())
            ->where('status', 'matched')
            ->with(['parent', 'student', 'subject', 'package'])
            ->latest()
            ->get();

        return Inertia::render('Tutor/Jobs/Index', [
            'jobs' => $requests,
        ]);
    }

    public function show(TutorRequest $tutorRequest)
    {
        abort_unless($tutorRequest->matched_tutor_id === auth()->id(), 403);

        $tutorRequest->load(['parent', 'student', 'subject', 'package']);

        return Inertia::render('Tutor/Jobs/Show', [
            'job' => $tutorRequest,
        ]);
    }

    public function accept(Request $request, TutorRequest $tutorRequest)
    {
        abort_unless($tutorRequest->matched_tutor_id === auth()->id(), 403);

        $validated = $request->validate([
            'schedule_day' => 'required|string|max:255',
            'schedule_time' => 'required|string|max:255',
            'duration_hours' => 'required|numeric|min:0.5|max:8',
            'location_type' => 'required|in:online,home,center',
            'location_address' => 'nullable|string|max:500',
        ]);

        $tutorRequest->update([
            'tutor_accepted' => true,
            'schedule_day' => $validated['schedule_day'],
            'schedule_time' => $validated['schedule_time'],
            'duration_hours' => $validated['duration_hours'],
            'location_type' => $validated['location_type'],
            'location_address' => $validated['location_address'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Job accepted. Waiting for parent payment.');
    }

    public function reject(TutorRequest $tutorRequest)
    {
        abort_unless($tutorRequest->matched_tutor_id === auth()->id(), 403);

        $tutorRequest->update([
            'status' => 'open',
            'matched_tutor_id' => null,
            'matched_at' => null,
        ]);

        return redirect()->back()->with('success', 'Job rejected. Request reopened.');
    }
}
