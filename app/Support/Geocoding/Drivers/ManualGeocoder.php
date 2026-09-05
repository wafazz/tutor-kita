<?php

namespace App\Support\Geocoding\Drivers;

use App\Support\Geocoding\Coordinates;
use App\Support\Geocoding\Geocoder;

/**
 * Resolves nothing: coordinates are expected to be supplied directly, by a map
 * pin or by an import.
 *
 * The default, so the system never depends on an external service or a billing
 * account it has not been given.
 */
class ManualGeocoder implements Geocoder
{
    public function geocode(string $address, ?string $postcode = null): ?Coordinates
    {
        return null;
    }

    public function name(): string
    {
        return 'manual';
    }
}
