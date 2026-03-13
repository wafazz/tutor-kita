<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'tutor_request_id', 'tutor_id', 'parent_id', 'student_id', 'subject_id',
        'schedule_day', 'schedule_time', 'duration_hours', 'hourly_rate', 'commission_rate',
        'location_type', 'location_address', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'duration_hours' => 'decimal:1',
        ];
    }

    public function tutorRequest()
    {
        return $this->belongsTo(TutorRequest::class);
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function sessions()
    {
        return $this->hasMany(TutorSession::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
