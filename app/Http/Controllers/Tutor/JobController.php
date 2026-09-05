<?php

namespace App\Http\Controllers\Tutor;

use App\Enums\DeliveryMode;
use App\Http\Controllers\Controller;
use App\Models\TutorRequest;
use App\Support\ScheduleConflictDetector;
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

    /**
     * Why this slot cannot be taken, or null when it can.
     *
     * Both diaries are checked: a tutor free at that hour is no use if the
     * student is already busy, and the reverse.
     */
    private function firstClash(TutorRequest $tutorRequest, array $validated): ?string
    {
        $tutorRequest->loadMissing('student');

        $detector = app(ScheduleConflictDetector::class);
        $tutor = auth()->user();

        $mode = match ($validated['location_type']) {
            'online' => DeliveryMode::OnlineSolo,
            'center' => DeliveryMode::CentreGroup,
            default => DeliveryMode::HomeStudent,
        };

        $day = $validated['schedule_day'];
        $time = substr($validated['schedule_time'], 0, 5);
        $hours = (float) $validated['duration_hours'];
        $latitude = $tutorRequest->student?->latitude;
        $longitude = $tutorRequest->student?->longitude;

        $tutorClash = $detector->check(
            tutorId: $tutor->id, day: $day, time: $time, durationHours: $hours,
            mode: $mode, latitude: $latitude, longitude: $longitude,
        )->first();

        if ($tutorClash) {
            return $tutorClash->message($tutor->name);
        }

        if ($tutorRequest->student) {
            $studentClash = $detector->checkStudent(
                studentId: $tutorRequest->student->id, day: $day, time: $time,
                durationHours: $hours, mode: $mode, latitude: $latitude, longitude: $longitude,
            )->first();

            if ($studentClash) {
                return $studentClash->messageForStudent($tutorRequest->student->name);
            }
        }

        return null;
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

        // The tutor chooses the schedule here, so this — not the earlier
        // match — is the first moment there is a time to check. Anything
        // validated at matching was validated against a different, often
        // empty, schedule.
        if ($clash = $this->firstClash($tutorRequest, $validated)) {
            return redirect()->back()->with('error', $clash);
        }

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
