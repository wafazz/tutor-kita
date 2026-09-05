<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\Geocoding\GeocoderManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index()
    {
        return Inertia::render('Parent/Students/Index', [
            'students' => Student::where('parent_id', auth()->id())->latest()->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Parent/Students/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:1|max:100',
            'school' => 'nullable|string|max:255',
            'education_level' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            // Where lessons happen when the tutor travels to the student.
            'address' => 'nullable|string|max:500',
            'area' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:10',
        ]);

        $validated['parent_id'] = auth()->id();

        $student = Student::create($validated);

        // Resolve the address so the student can be matched by distance; a
        // failure leaves them unplaced for the geocode:backfill command rather
        // than blocking the save.
        if (app(GeocoderManager::class)->applyTo($student)) {
            $student->save();
        }

        return redirect()->back()->with('success', 'Student added successfully.');
    }

    public function edit(Student $student)
    {
        abort_unless($student->parent_id === auth()->id(), 403);

        return Inertia::render('Parent/Students/Edit', [
            'student' => $student,
        ]);
    }

    public function update(Request $request, Student $student)
    {
        abort_unless($student->parent_id === auth()->id(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:1|max:100',
            'school' => 'nullable|string|max:255',
            'education_level' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            // Where lessons happen when the tutor travels to the student.
            'address' => 'nullable|string|max:500',
            'area' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:10',
        ]);

        $student->update($validated);

        if (app(GeocoderManager::class)->applyTo($student)) {
            $student->save();
        }

        return redirect()->back()->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        abort_unless($student->parent_id === auth()->id(), 403);

        $student->delete();

        return redirect()->back()->with('success', 'Student deleted successfully.');
    }
}
