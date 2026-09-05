<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use Illuminate\Database\Eloquent\Model;

class SubjectRate extends Model
{
    protected $fillable = [
        'subject_id', 'delivery_mode', 'hourly_rate', 'max_students', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'delivery_mode' => DeliveryMode::class,
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
