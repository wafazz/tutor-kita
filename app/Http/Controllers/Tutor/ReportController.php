<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\StudentReport;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $reports = StudentReport::where('tutor_id', auth()->id())
            ->with(['student', 'subject', 'booking'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Tutor/Reports/Index', [
            'reports' => $reports,
        ]);
    }

    public function create()
    {
        $bookings = Booking::where('tutor_id', auth()->id())
            ->whereIn('status', ['confirmed', 'active', 'completed'])
            ->with(['student', 'subject'])
            ->get();

        $subjects = Subject::where('is_active', true)->get();

        return Inertia::render('Tutor/Reports/Create', [
            'bookings' => $bookings,
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'exam_type' => 'required|string|max:255',
            'term' => 'nullable|string|max:255',
            'score' => 'required|numeric|min:0|max:999',
            'total_marks' => 'required|numeric|min:1|max:999',
            'grade' => 'nullable|string|max:10',
            'exam_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:2000',
            'strengths' => 'nullable|string|max:2000',
            'improvements' => 'nullable|string|max:2000',
        ]);

        $booking = Booking::where('id', $validated['booking_id'])
            ->where('tutor_id', auth()->id())
            ->firstOrFail();

        StudentReport::create([
            'student_id' => $booking->student_id,
            'subject_id' => $booking->subject_id,
            'tutor_id' => auth()->id(),
            'booking_id' => $booking->id,
            'exam_type' => $validated['exam_type'],
            'term' => $validated['term'] ?? null,
            'score' => $validated['score'],
            'total_marks' => $validated['total_marks'],
            'grade' => $validated['grade'] ?? null,
            'exam_date' => $validated['exam_date'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'strengths' => $validated['strengths'] ?? null,
            'improvements' => $validated['improvements'] ?? null,
        ]);

        return redirect()->route('tutor.reports.index')->with('success', 'Report added successfully.');
    }
}
