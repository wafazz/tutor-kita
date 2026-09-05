<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Student;
use App\Support\ClassEnroller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassBrowseController extends Controller
{
    public function index()
    {
        $classes = ClassSession::with(['tutor:id,name', 'subject:id,name', 'centre:id,name,area,capacity', 'enrolments'])
            ->where('status', 'open')
            ->orderBy('schedule_day')
            ->get()
            ->map(fn (ClassSession $c) => [
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
            ]);

        return Inertia::render('Parent/Classes/Index', [
            'classes' => $classes,
            'students' => Student::where('parent_id', auth()->id())->orderBy('name')->get(['id', 'name']),
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
