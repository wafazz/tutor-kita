<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorPayout extends Model
{
    protected $fillable = [
        'tutor_id', 'amount', 'sessions_count', 'period_start', 'period_end',
        'status', 'paid_at', 'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
