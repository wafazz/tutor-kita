<?php

namespace App\Support\Geocoding\Drivers;

use App\Models\PostcodeCentroid;
use App\Support\Geocoding\Coordinates;
use App\Support\Geocoding\Geocoder;

/**
 * Resolves a Malaysian postcode to the centre of its area.
 *
 * Free and offline, accurate to a few kilometres — enough to answer "tutors
 * within 10km", not enough for door-to-door distance. accuracyKm carries that
 * caveat so callers are not misled by a precise-looking number.
 */
class PostcodeGeocoder implements Geocoder
{
    public function geocode(string $address, ?string $postcode = null): ?Coordinates
    {
        $postcode ??= $this->extractPostcode($address);

        if ($postcode === null) {
            return null;
        }

        $centroid = PostcodeCentroid::where('postcode', $postcode)->first();

        if (! $centroid) {
            return null;
        }

        return new Coordinates(
            (float) $centroid->latitude,
            (float) $centroid->longitude,
            source: 'postcode',
            accuracyKm: 3.0,
        );
    }

    /** Malaysian postcodes are five digits. */
    private function extractPostcode(string $address): ?string
    {
        return preg_match('/\b(\d{5})\b/', $address, $m) ? $m[1] : null;
    }

    public function name(): string
    {
        return 'postcode';
    }
}
