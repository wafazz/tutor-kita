<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorSession extends Model
{
    protected $table = 'tutor_sessions';

    protected $fillable = [
        'booking_id', 'session_date', 'start_time', 'end_time', 'check_in_token',
        'checked_in_at', 'checked_out_at', 'check_in_lat', 'check_in_lng',
        'check_out_lat', 'check_out_lng', 'check_in_method', 'duration_minutes',
        'status', 'tutor_notes', 'parent_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'parent_confirmed' => 'boolean',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'session_id');
    }
}
