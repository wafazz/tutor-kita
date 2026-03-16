<?php

namespace App\Models;

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
}
