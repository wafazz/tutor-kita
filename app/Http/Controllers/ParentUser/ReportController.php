<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentReport;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $studentIds = Student::where('parent_id', auth()->id())->pluck('id');

        $reports = StudentReport::whereIn('student_id', $studentIds)
            ->with(['student', 'subject', 'tutor'])
            ->latest()
            ->paginate(20);

        $students = Student::where('parent_id', auth()->id())->get();

        return Inertia::render('Parent/Reports/Index', [
            'reports' => $reports,
            'students' => $students,
        ]);
    }

    public function show(Student $student)
    {
        abort_unless($student->parent_id === auth()->id(), 403);

        $reports = StudentReport::where('student_id', $student->id)
            ->with(['subject', 'tutor'])
            ->orderBy('exam_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by subject for performance chart
        $bySubject = $reports->groupBy('subject_id')->map(function ($items) {
            return [
                'subject' => $items->first()->subject->name,
                'reports' => $items->sortBy('exam_date')->values()->map(fn ($r) => [
                    'id' => $r->id,
                    'exam_type' => $r->exam_type,
                    'term' => $r->term,
                    'score' => $r->score,
                    'total_marks' => $r->total_marks,
                    'percentage' => round(($r->score / $r->total_marks) * 100, 1),
                    'grade' => $r->grade,
                    'exam_date' => $r->exam_date?->format('Y-m-d'),
                    'remarks' => $r->remarks,
                    'strengths' => $r->strengths,
                    'improvements' => $r->improvements,
                    'tutor' => $r->tutor->name,
                    'created_at' => $r->created_at->format('M d, Y'),
                ]),
            ];
        })->values();

        return Inertia::render('Parent/Reports/Show', [
            'student' => $student,
            'bySubject' => $bySubject,
        ]);
    }
}
