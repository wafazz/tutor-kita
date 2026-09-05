<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Centre;
use App\Models\User;
use App\Support\Geocoding\GeocoderManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CentreController extends Controller
{
    public function index()
    {
        $centres = Centre::with('owner:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Centre $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'address' => $c->address,
                'area' => $c->area,
                'state' => $c->state,
                'postcode' => $c->postcode,
                'capacity' => $c->capacity,
                'is_active' => $c->is_active,
                'owner_user_id' => $c->owner_user_id,
                'owner_name' => $c->owner?->name,
                'latitude' => $c->latitude,
                'longitude' => $c->longitude,
                // Surfaced so an unplaced centre is visibly excluded from
                // radius results rather than quietly missing from them.
                'is_placed' => $c->hasCoordinates(),
            ]);

        return Inertia::render('Admin/Centres/Index', [
            'centres' => $centres,
            'tutors' => User::where('role', 'tutor')->orderBy('name')->get(['id', 'name']),
            'geocodingDriver' => app(GeocoderManager::class)->name(),
        ]);
    }

    public function store(Request $request, GeocoderManager $geocoder)
    {
        $centre = Centre::create($this->validated($request));

        if ($geocoder->applyTo($centre)) {
            $centre->save();
        }

        return redirect()->back()->with('success', $this->savedMessage($centre));
    }

    public function update(Request $request, Centre $centre, GeocoderManager $geocoder)
    {
        $centre->update($this->validated($request));

        if ($geocoder->applyTo($centre)) {
            $centre->save();
        }

        return redirect()->back()->with('success', $this->savedMessage($centre));
    }

    public function destroy(Centre $centre)
    {
        $centre->delete();

        return redirect()->back()->with('success', 'Centre removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'area' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:10',
            'capacity' => 'required|integer|min:1|max:500',
            'is_active' => 'boolean',
            // Null keeps it a platform centre; a tutor makes it their venue.
            'owner_user_id' => 'nullable|exists:users,id',
            // Accepted directly so a map pin or a known point can be supplied
            // without depending on a geocoding service.
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
    }

    private function savedMessage(Centre $centre): string
    {
        return $centre->hasCoordinates()
            ? "Centre '{$centre->name}' saved."
            : "Centre '{$centre->name}' saved, but it has no map position yet, so it will not appear in distance searches.";
    }
}
