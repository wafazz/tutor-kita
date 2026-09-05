<?php

namespace App\Support\Geocoding;

final class Coordinates
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        /** How the point was obtained, for auditing an imprecise source. */
        public readonly string $source = 'unknown',
        /** Rough accuracy in kilometres; postcode centroids are not doors. */
        public readonly ?float $accuracyKm = null,
    ) {}

    public function toArray(): array
    {
        return ['latitude' => $this->latitude, 'longitude' => $this->longitude];
    }
}
