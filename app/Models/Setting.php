<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get($key, $default = null)
    {
        return Cache::remember("setting.{$key}", 60, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set($key, $value)
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    /**
     * Platform default commission percentage.
     *
     * Used when a tutor has no rate of their own — including as the seed for
     * a newly created tutor profile. Existing tutors keep the rate already on
     * their profile; changing this setting does not repriceable them.
     */
    public static function defaultCommissionRate(): float
    {
        return (float) static::get('commission_rate', 20);
    }
}
