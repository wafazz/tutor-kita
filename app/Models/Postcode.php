<?php

namespace App\Models;

use App\Models\Concerns\HasCoordinates;
use Illuminate\Database\Eloquent\Model;

/**
 * The Malaysian postcode directory: postcode to city and state.
 *
 * Coordinates are nullable and currently unset — the directory does not carry
 * them. Anything that resolves a location must treat a missing coordinate as
 * "unknown" rather than filling in a guess.
 */
class Postcode extends Model
{
    use HasCoordinates;

    protected $fillable = ['postcode', 'city', 'state', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }

    /**
     * Where a postcode is, as far as the directory knows.
     *
     * A postcode can span more than one city — 40160 covers both Shah Alam and
     * Sungai Buloh — so this returns the first alphabetically and callers that
     * need every match should query directly.
     */
    public static function lookup(?string $postcode): ?self
    {
        if (blank($postcode)) {
            return null;
        }

        return static::where('postcode', trim($postcode))->orderBy('city')->first();
    }
}
