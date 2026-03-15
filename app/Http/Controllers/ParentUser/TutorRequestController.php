<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TutorRequestController extends Controller
{
    public function index()
    {
        $requests = TutorRequest::where('parent_id', auth()->id())
            ->with(['student', 'subject', 'package', 'matchedTutor', 'payment'])
            ->latest()
            ->get();

        // Group requests by request_group for display
        $grouped = [];
        $standalone = [];
        foreach ($requests as $req) {
            if ($req->request_group) {
                $grouped[$req->request_group][] = $req;
            } else {
                $standalone[] = $req;
            }
        }

        return Inertia::render('Parent/Requests/Index', [
            'requests' => $requests,
            'groupedRequests' => $grouped,
        ]);
    }

    public function create()
    {
        return Inertia::render('Parent/Requests/Create', [
            'students' => Student::where('parent_id', auth()->id())->get(),
            'subjects' => Subject::where('is_active', true)->get(),
            'packages' => Package::with('subjects')->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'package_id' => 'required|exists:packages,id',
            'preferred_area' => 'nullable|string|max:255',
            'preferred_schedule' => 'nullable|string|max:255',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $student = Student::where('id', $validated['student_id'])
            ->where('parent_id', auth()->id())
            ->firstOrFail();

        $package = Package::with('subjects')->findOrFail($validated['package_id']);

        $baseData = [
            'parent_id' => auth()->id(),
            'student_id' => $validated['student_id'],
            'package_id' => $validated['package_id'],
            'preferred_area' => $validated['preferred_area'] ?? null,
            'preferred_schedule' => $validated['preferred_schedule'] ?? null,
            'budget_min' => $validated['budget_min'] ?? null,
            'budget_max' => $validated['budget_max'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        // Multi-subject package: create one request per subject
        if ($package->package_type === 'specific' && $package->subjects->count() > 1) {
            $groupId = Str::uuid()->toString();

            foreach ($package->subjects as $subject) {
                TutorRequest::create(array_merge($baseData, [
                    'request_group' => $groupId,
                    'subject_id' => $subject->id,
                ]));
            }

            return redirect()->back()->with('success', 'Tutor requests created for ' . $package->subjects->count() . ' subjects.');
        }

        // Single subject: use selected subject_id or the package's only subject
        $subjectId = $validated['subject_id'];
        if (!$subjectId && $package->package_type === 'specific' && $package->subjects->count() === 1) {
            $subjectId = $package->subjects->first()->id;
        }

        if (!$subjectId) {
            return redirect()->back()->withErrors(['subject_id' => 'Please select a subject.']);
        }

        TutorRequest::create(array_merge($baseData, [
            'subject_id' => $subjectId,
        ]));

        return redirect()->back()->with('success', 'Tutor request submitted successfully.');
    }

    public function show(TutorRequest $tutorRequest)
    {
        abort_unless($tutorRequest->parent_id === auth()->id(), 403);

        $tutorRequest->load(['student', 'subject', 'package', 'matchedTutor', 'payment']);

        // Load sibling requests if part of a group
        $groupRequests = [];
        if ($tutorRequest->request_group) {
            $groupRequests = TutorRequest::where('request_group', $tutorRequest->request_group)
                ->with(['subject', 'matchedTutor'])
                ->get();
        }

        return Inertia::render('Parent/Requests/Show', [
            'tutorRequest' => $tutorRequest,
            'groupRequests' => $groupRequests,
        ]);
    }

    public function cancel(TutorRequest $tutorRequest)
    {
        abort_unless($tutorRequest->parent_id === auth()->id(), 403);

        // Cancel all requests in the group
        if ($tutorRequest->request_group) {
            TutorRequest::where('request_group', $tutorRequest->request_group)
                ->update(['status' => 'cancelled']);
            return redirect()->back()->with('success', 'All grouped requests cancelled.');
        }

        $tutorRequest->update(['status' => 'cancelled']);
        return redirect()->back()->with('success', 'Request cancelled.');
    }
}
