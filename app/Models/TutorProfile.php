<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorProfile extends Model
{
    protected $fillable = [
        'user_id', 'ic_number', 'subjects', 'education_level', 'experience_years',
        'bio', 'hourly_rate', 'location_area', 'location_state', 'latitude', 'longitude',
        'availability', 'verification_status', 'verified_at', 'documents', 'rating_avg', 'total_sessions',
    ];

    protected function casts(): array
    {
        return [
            'subjects' => 'array',
            'availability' => 'array',
            'documents' => 'array',
            'verified_at' => 'datetime',
            'hourly_rate' => 'decimal:2',
            'rating_avg' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
