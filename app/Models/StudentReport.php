<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentReport extends Model
{
    protected $fillable = [
        'student_id', 'subject_id', 'tutor_id', 'booking_id',
        'exam_type', 'term', 'score', 'total_marks', 'grade',
        'exam_date', 'remarks', 'strengths', 'improvements',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'total_marks' => 'decimal:2',
            'exam_date' => 'date',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
