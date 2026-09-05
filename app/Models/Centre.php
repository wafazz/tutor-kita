<?php

namespace App\Models;

use App\Models\Concerns\HasCoordinates;
use Illuminate\Database\Eloquent\Model;

class Centre extends Model
{
    use HasCoordinates;

    protected $fillable = [
        'owner_user_id', 'name', 'address', 'area', 'state', 'postcode',
        'latitude', 'longitude', 'geocoded_at', 'capacity', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'geocoded_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** Null for a platform centre; a tutor when it is their own venue. */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function isPlatformOwned(): bool
    {
        return $this->owner_user_id === null;
    }

    /** The address a geocoder should resolve. */
    public function geocodableAddress(): string
    {
        return collect([$this->address, $this->area, $this->postcode, $this->state, 'Malaysia'])
            ->filter()->implode(', ');
    }
}
