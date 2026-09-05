<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Latitude/longitude behaviour shared by anything a radius is measured to or
 * from — a student's home, a tutor's home, a centre.
 */
trait HasCoordinates
{
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Great-circle distance in kilometres, or null if either end is unplaced.
     *
     * Computed in PHP rather than SQL: SQLite is not guaranteed to have the
     * trigonometric functions this needs, and the tests run on it.
     */
    public function distanceTo(?float $latitude, ?float $longitude): ?float
    {
        if (! $this->hasCoordinates() || $latitude === null || $longitude === null) {
            return null;
        }

        $earthRadiusKm = 6371.0088;

        $latFrom = deg2rad((float) $this->latitude);
        $latTo = deg2rad($latitude);
        $latDelta = $latTo - $latFrom;
        $lngDelta = deg2rad($longitude - (float) $this->longitude);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return round($earthRadiusKm * 2 * asin(min(1.0, sqrt($a))), 3);
    }

    /**
     * Narrow to rows inside the bounding box around a point.
     *
     * A box, not a circle: plain arithmetic runs on any database and uses the
     * lat/lng index, where trigonometry in SQL would do neither. It admits a
     * few rows just outside the radius at the corners, so callers that need an
     * exact circle filter the result with distanceTo().
     */
    public function scopeWithinRadius(Builder $query, ?float $latitude, ?float $longitude, float $km): Builder
    {
        if ($latitude === null || $longitude === null) {
            // Nothing to measure from — match nothing rather than everything.
            return $query->whereRaw('1 = 0');
        }

        $latDelta = $km / 111.0;
        $lngDelta = $km / max(0.000001, 111.0 * cos(deg2rad($latitude)));

        return $query
            ->whereNotNull($this->getTable().'.latitude')
            ->whereNotNull($this->getTable().'.longitude')
            ->whereBetween($this->getTable().'.latitude', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween($this->getTable().'.longitude', [$longitude - $lngDelta, $longitude + $lngDelta]);
    }

    /** Rows still needing coordinates, for a geocoding backfill. */
    public function scopeNeedsGeocoding(Builder $query): Builder
    {
        return $query->whereNull($this->getTable().'.latitude')
            ->orWhereNull($this->getTable().'.longitude');
    }
}
