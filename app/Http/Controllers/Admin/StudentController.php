<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('parent');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('parent', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        return Inertia::render('Admin/Students/Index', [
            'students' => $query->latest()->paginate(15),
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Student $student)
    {
        $student->load(['parent', 'bookings' => function ($q) {
            $q->with(['tutor', 'subject'])->latest()->limit(10);
        }]);

        return Inertia::render('Admin/Students/Show', [
            'student' => $student,
        ]);
    }
}
