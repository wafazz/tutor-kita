<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Student;
use App\Support\ClassEnroller;
use App\Support\TutorMatcher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassBrowseController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::where('parent_id', auth()->id())->orderBy('name')->get();

        // Measured from the student's home, since that is who travels to a
        // centre. With several children the nearest one wins, so a class in
        // reach of any of them still appears.
        $placed = $students->filter(fn (Student $s) => $s->hasCoordinates());

        $radiusKm = (float) $request->input('radius', TutorMatcher::DEFAULT_RADIUS_KM);

        $classes = ClassSession::with(['tutor:id,name', 'subject:id,name', 'centre:id,name,area,capacity,latitude,longitude', 'enrolments'])
            ->where('status', 'open')
            ->orderBy('schedule_day')
            ->get()
            ->map(function (ClassSession $c) use ($placed) {
                // Online classes have nowhere to be, so distance does not apply.
                $distance = $c->delivery_mode->isOnline() || ! $c->centre?->hasCoordinates()
                    ? null
                    : $placed->map(fn (Student $s) => $c->centre->distanceTo((float) $s->latitude, (float) $s->longitude))
                        ->filter()
                        ->min();

                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'tutor_name' => $c->tutor?->name,
                    'subject_name' => $c->subject?->name,
                    'centre_name' => $c->centre?->name,
                    'centre_area' => $c->centre?->area,
                    'is_online' => $c->delivery_mode->isOnline(),
                    'schedule_day' => $c->schedule_day,
                    'schedule_time' => $c->schedule_time,
                    'total_sessions' => $c->total_sessions,
                    'seats_left' => $c->seatsLeft(),
                    'price' => $c->priceForStudent(),
                    'distance_km' => $distance,
                    // An unplaced centre cannot be measured, and saying nothing
                    // would read as "nearby" rather than "unknown".
                    'distance_known' => $c->delivery_mode->isOnline() || $distance !== null,
                ];
            });

        $total = $classes->count();

        // Only filter once there is somewhere to measure from — otherwise a
        // parent with no address would see nothing at all rather than
        // everything, which looks like the platform is empty.
        $filtered = $placed->isEmpty()
            ? $classes
            : $classes->filter(fn (array $c) => $c['is_online']
                || $c['distance_km'] === null
                || $c['distance_km'] <= $radiusKm);

        return Inertia::render('Parent/Classes/Index', [
            'classes' => $filtered
                ->sortBy(fn (array $c) => $c['distance_km'] ?? -1)
                ->values(),
            'students' => $students->map(fn (Student $s) => ['id' => $s->id, 'name' => $s->name]),
            'filters' => [
                'radius' => $radiusKm,
                'hasLocation' => $placed->isNotEmpty(),
                'hidden' => $total - $filtered->count(),
            ],
        ]);
    }

    public function show(ClassSession $class)
    {
        $class->load(['tutor:id,name', 'subject:id,name', 'centre', 'enrolments']);

        $mine = $class->enrolments()
            ->whereIn('student_id', Student::where('parent_id', auth()->id())->pluck('id'))
            ->with(['student:id,name', 'payment:id,status,amount'])
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'student_name' => $e->student?->name,
                'status' => $e->status,
                'payment_id' => $e->payment_id,
                'payment_status' => $e->payment?->status,
                'amount' => $e->payment?->amount,
            ]);

        return Inertia::render('Parent/Classes/Show', [
            'classSession' => [
                'id' => $class->id,
                'title' => $class->title,
                'tutor_name' => $class->tutor?->name,
                'subject_name' => $class->subject?->name,
                'centre_name' => $class->centre?->name,
                'is_online' => $class->delivery_mode->isOnline(),
                'schedule_day' => $class->schedule_day,
                'schedule_time' => $class->schedule_time,
                'total_sessions' => $class->total_sessions,
                'seats_left' => $class->seatsLeft(),
                'price' => $class->priceForStudent(),
                'status' => $class->status,
            ],
            'myEnrolments' => $mine,
            'students' => Student::where('parent_id', auth()->id())->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function enrol(Request $request, ClassSession $class, ClassEnroller $enroller)
    {
        $validated = $request->validate(['student_id' => 'required|exists:students,id']);

        $student = Student::where('parent_id', auth()->id())->findOrFail($validated['student_id']);

        abort_unless($class->status === 'open', 403, 'This class is not open for enrolment.');

        try {
            $enrolment = $enroller->enrol($class, $student);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        // The seat is held, but only paying confirms it.
        return redirect()->route('parent.payments.show', $enrolment->payment_id)
            ->with('success', "Seat held for {$student->name}. Complete payment to confirm it.");
    }
}
