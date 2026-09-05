<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use App\Support\Geocoding\GeocoderManager;
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
            // Where this tutor's payouts are sent. Optional so an incomplete
            // profile can still be saved, but all three are needed to be paid.
            'address' => 'nullable|string|max:500',
            'postcode' => 'nullable|string|max:10',
            // How far this tutor will travel to a student's home. Null means
            // unanswered, not "will not travel".
            'travel_radius_km' => 'nullable|integer|min:0|max:200',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:34|regex:/^[A-Za-z0-9]+$/',
            'bank_account_name' => 'nullable|string|max:255',
        ]);

        if (! empty($validated['subjects'])) {
            $validated['subjects'] = Subject::whereIn('id', $validated['subjects'])
                ->pluck('name')
                ->toArray();
        }

        $profile = $user->tutorProfile;
        $profile->update($validated);

        // Place the tutor so distance matching can reach them; failure leaves
        // them unplaced for geocode:backfill rather than blocking the save.
        if (app(GeocoderManager::class)->applyTo($profile)) {
            $profile->save();
        }

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}
