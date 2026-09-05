<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name', 'category', 'education_level', 'hourly_rate_home', 'hourly_rate_online', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'hourly_rate_home' => 'decimal:2',
            'hourly_rate_online' => 'decimal:2',
        ];
    }

    public function rates()
    {
        return $this->hasMany(SubjectRate::class);
    }

    /**
     * Hourly rate for a delivery mode.
     *
     * Falls back down a chain rather than to zero: a mode's own rate, then a
     * related mode's (a centre group priced like home tuition until it is given
     * its own price), then the legacy two-column rates for subjects that
     * predate the rate table. Returns null when nothing is configured, which
     * callers must treat as "cannot price" rather than free.
     */
    public function rateFor(DeliveryMode $mode): ?float
    {
        // Explicit rates win over the legacy two-column pricing they replace,
        // including an explicit rate inherited from a related mode: the legacy
        // hourly_rate_home is shared by both home modes, so it says nothing
        // about which of them was actually priced.
        for ($m = $mode; $m !== null; $m = $m->pricingFallback()) {
            $rate = $this->relationLoaded('rates')
                ? $this->rates->first(fn ($r) => $r->delivery_mode === $m && $r->is_active)
                : $this->rates()->where('delivery_mode', $m->value)->where('is_active', true)->first();

            if ($rate && (float) $rate->hourly_rate > 0) {
                return (float) $rate->hourly_rate;
            }
        }

        // Then the legacy columns, down the same chain, so a subject that
        // predates the rate table still prices.
        for ($m = $mode; $m !== null; $m = $m->pricingFallback()) {
            $legacy = (float) ($this->{$m->legacyRateColumn()} ?? 0);

            if ($legacy > 0) {
                return $legacy;
            }
        }

        return null;
    }

    /**
     * Whether this mode is priced in its own right, rather than inheriting.
     *
     * An inherited rate keeps a booking from pricing at zero, but a group class
     * charged at the one-to-one rate is almost certainly not intended — screens
     * should surface this so it gets set deliberately.
     */
    public function hasOwnRateFor(DeliveryMode $mode): bool
    {
        return $this->rates()
            ->where('delivery_mode', $mode->value)
            ->where('is_active', true)
            ->where('hourly_rate', '>', 0)
            ->exists();
    }

    /** Seats available for a group mode, when configured. */
    public function maxStudentsFor(DeliveryMode $mode): ?int
    {
        if (! $mode->isGroup()) {
            return null;
        }

        return $this->rates()
            ->where('delivery_mode', $mode->value)
            ->where('is_active', true)
            ->value('max_students');
    }
}
