<?php

namespace App\Support\Geocoding\Drivers;

use App\Support\Geocoding\Coordinates;
use App\Support\Geocoding\Geocoder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Geocoding API. Best Malaysian coverage, billed per request.
 *
 * Returns null on any failure rather than throwing: a geocoding outage should
 * leave a record unplaced for a later backfill, not block someone from saving
 * their address.
 */
class GoogleGeocoder implements Geocoder
{
    public function __construct(private readonly ?string $apiKey) {}

    public function geocode(string $address, ?string $postcode = null): ?Coordinates
    {
        if (blank($this->apiKey)) {
            return null;
        }

        try {
            $response = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'region' => 'my',
                'key' => $this->apiKey,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $result = $response->json('results.0.geometry.location');

            if (! isset($result['lat'], $result['lng'])) {
                return null;
            }

            return new Coordinates((float) $result['lat'], (float) $result['lng'], source: 'google', accuracyKm: 0.05);
        } catch (\Throwable $e) {
            Log::warning('Geocoding failed', ['driver' => 'google', 'message' => $e->getMessage()]);

            return null;
        }
    }

    public function name(): string
    {
        return 'google';
    }
}
