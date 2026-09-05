<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassEnrolment extends Model
{
    protected $fillable = [
        'class_session_id', 'student_id', 'parent_id', 'booking_id', 'payment_id', 'status',
    ];

    public function classSession()
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
