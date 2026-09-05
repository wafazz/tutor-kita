<?php

namespace App\Http\Controllers;

use App\Models\Postcode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Resolves a postcode to its city and state for address forms.
 *
 * Returns the directory's answer or nothing — never a guess — so a postcode
 * the directory does not know leaves the fields for the user to fill.
 */
class PostcodeLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'postcode' => 'required|string|max:10',
        ]);

        $matches = Postcode::where('postcode', trim($validated['postcode']))
            ->orderBy('city')
            ->get(['city', 'state']);

        if ($matches->isEmpty()) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'city' => $matches->first()->city,
            'state' => $matches->first()->state,
            // A postcode can serve more than one city; the form fills the first
            // and offers the rest rather than silently choosing.
            'cities' => $matches->pluck('city'),
        ]);
    }
}
