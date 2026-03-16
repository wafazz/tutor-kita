<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use App\Mail\TutorVerificationStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class TutorController extends Controller
{
    public function index()
    {
        $tutors = User::where('role', 'tutor')
            ->with('tutorProfile')
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Tutors/Index', [
            'tutors' => $tutors,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Tutors/Create', [
            'subjects' => Subject::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'ic_number' => 'nullable|string|max:20',
            'subjects' => 'nullable|array',
            'education_level' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0',
            'bio' => 'nullable|string|max:2000',
            'hourly_rate' => 'required|numeric|min:0',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'location_area' => 'nullable|string|max:255',
            'location_state' => 'nullable|string|max:255',
        ]);

        $tutor = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'tutor',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        TutorProfile::create([
            'user_id' => $tutor->id,
            'ic_number' => $validated['ic_number'] ?? null,
            'subjects' => $validated['subjects'] ?? [],
            'education_level' => $validated['education_level'] ?? null,
            'experience_years' => $validated['experience_years'] ?? 0,
            'bio' => $validated['bio'] ?? null,
            'hourly_rate' => $validated['hourly_rate'],
            'commission_rate' => $validated['commission_rate'],
            'location_area' => $validated['location_area'] ?? null,
            'location_state' => $validated['location_state'] ?? null,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        return redirect()->route('admin.tutors.index')->with('success', 'Tutor added successfully.');
    }

    public function show(User $tutor)
    {
        $tutor->load('tutorProfile');

        $reviews = \App\Models\Review::where('tutor_id', $tutor->id)
            ->with(['parent', 'booking.subject'])
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Admin/Tutors/Show', [
            'tutor' => $tutor,
            'reviews' => $reviews,
        ]);
    }

    public function updateCommission(Request $request, User $tutor)
    {
        $validated = $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        $tutor->tutorProfile->update([
            'commission_rate' => $validated['commission_rate'],
        ]);

        return redirect()->back()->with('success', 'Commission rate updated.');
    }

    public function verify(Request $request, User $tutor)
    {
        $validated = $request->validate([
            'status' => 'required|in:verified,rejected',
        ]);

        $tutor->tutorProfile->update([
            'verification_status' => $validated['status'],
            'verified_at' => $validated['status'] === 'verified' ? now() : null,
        ]);

        Mail::to($tutor->email)->send(new TutorVerificationStatus($tutor, $validated['status']));

        return redirect()->back()->with('success', 'Tutor verification status updated.');
    }
}
