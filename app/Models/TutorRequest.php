<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorRequest extends Model
{
    protected $fillable = [
        'request_group', 'parent_id', 'student_id', 'subject_id', 'package_id', 'preferred_area', 'preferred_location',
        'preferred_schedule', 'preferred_time', 'preferred_tutor_gender',
        'budget_min', 'budget_max', 'notes', 'status', 'matched_tutor_id', 'matched_at',
        'tutor_accepted', 'schedule_day', 'schedule_time', 'duration_hours', 'location_type', 'location_address',
    ];

    protected function casts(): array
    {
        return [
            'matched_at' => 'datetime',
            'tutor_accepted' => 'boolean',
            'duration_hours' => 'decimal:1',
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
        ];
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

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function matchedTutor()
    {
        return $this->belongsTo(User::class, 'matched_tutor_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function groupRequests()
    {
        if (!$this->request_group) {
            return collect([$this]);
        }
        return static::where('request_group', $this->request_group)->get();
    }
}
