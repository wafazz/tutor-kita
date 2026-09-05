<?php

namespace App\Support\Geocoding;

/**
 * Turns an address into coordinates.
 *
 * Kept behind a contract so the choice between a paid API, a free postcode
 * table and a map pin stays a configuration decision rather than something
 * baked into every caller.
 */
interface Geocoder
{
    public function geocode(string $address, ?string $postcode = null): ?Coordinates;

    public function name(): string;
}
