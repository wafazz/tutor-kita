<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'parent')
            ->withCount('students', 'parentBookings');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Admin/Parents/Index', [
            'parents' => $query->latest()->paginate(15),
            'filters' => $request->only('search'),
        ]);
    }

    public function show(User $parent)
    {
        abort_unless($parent->role === 'parent', 404);

        $parent->load(['students', 'parentBookings' => function ($q) {
            $q->with(['tutor', 'student', 'subject'])->latest()->limit(10);
        }]);

        return Inertia::render('Admin/Parents/Show', [
            'parent' => $parent,
        ]);
    }
}
