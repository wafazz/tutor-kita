<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Tutor/Profile/Edit', [
            'profile' => $user->tutorProfile,
            'subjects' => Subject::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'subjects' => 'nullable|array',
            'education_level' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0',
            'bio' => 'nullable|string|max:2000',
            'hourly_rate' => 'nullable|numeric|min:0',
            'location_area' => 'nullable|string|max:255',
            'location_state' => 'nullable|string|max:255',
            'ic_number' => 'nullable|string|max:20',
            'availability' => 'nullable|array',
        ]);

        if (!empty($validated['subjects'])) {
            $validated['subjects'] = Subject::whereIn('id', $validated['subjects'])
                ->pluck('name')
                ->toArray();
        }

        $user->tutorProfile->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}
