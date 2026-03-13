<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorRequest extends Model
{
    protected $fillable = [
        'parent_id', 'student_id', 'subject_id', 'preferred_area', 'preferred_schedule',
        'budget_min', 'budget_max', 'notes', 'status', 'matched_tutor_id', 'matched_at',
    ];

    protected function casts(): array
    {
        return [
            'matched_at' => 'datetime',
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

    public function matchedTutor()
    {
        return $this->belongsTo(User::class, 'matched_tutor_id');
    }
}
