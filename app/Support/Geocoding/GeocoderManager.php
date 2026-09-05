<?php

namespace App\Support\Geocoding;

use App\Models\Setting;
use App\Support\Geocoding\Drivers\GoogleGeocoder;
use App\Support\Geocoding\Drivers\ManualGeocoder;
use App\Support\Geocoding\Drivers\PostcodeGeocoder;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves the configured geocoder and caches its answers.
 *
 * The driver is a setting, so moving from map pins to postcodes to a paid API
 * is an admin change rather than a deployment. Defaults to manual: nothing
 * depends on an external service until one is deliberately chosen.
 */
class GeocoderManager implements Geocoder
{
    public function driver(): Geocoder
    {
        return match (Setting::get('geocoding_driver', 'manual')) {
            'google' => new GoogleGeocoder(Setting::get('google_maps_api_key')),
            'postcode' => new PostcodeGeocoder,
            default => new ManualGeocoder,
        };
    }

    public function geocode(string $address, ?string $postcode = null): ?Coordinates
    {
        $driver = $this->driver();

        if ($driver instanceof ManualGeocoder) {
            return null;
        }

        // The same address resolves to the same point; paid lookups especially
        // should happen once.
        $key = 'geocode.'.$driver->name().'.'.md5($address.'|'.$postcode);

        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }

        $result = $driver->geocode($address, $postcode);

        // Cache misses too, briefly, so a bad address is not retried on every save.
        Cache::put($key, $result ?? false, $result ? now()->addDays(30) : now()->addHours(6));

        return $result;
    }

    public function name(): string
    {
        return $this->driver()->name();
    }
}
