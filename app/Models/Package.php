<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name', 'package_type', 'description', 'total_sessions', 'duration_hours',
        'price', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_hours' => 'decimal:1',
            'is_active' => 'boolean',
        ];
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'package_subject');
    }
}
