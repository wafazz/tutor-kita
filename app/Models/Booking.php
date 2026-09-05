<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'tutor_request_id', 'tutor_id', 'parent_id', 'student_id', 'subject_id',
        'schedule_day', 'schedule_time', 'duration_hours', 'hourly_rate', 'commission_rate',
        'amount', 'commission_amount', 'tutor_payout', 'payment_id', 'tutor_payout_id',
        'location_type', 'location_address', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'tutor_payout' => 'decimal:2',
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

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    /**
     * The payment that settled this booking. For a grouped request every
     * booking in the group points at the same payment.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * The payout run that has committed to paying this booking, if any.
     */
    public function tutorPayout()
    {
        return $this->belongsTo(TutorPayout::class, 'tutor_payout_id');
    }
}
