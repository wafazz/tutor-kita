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
        if (! $this->request_group) {
            return collect([$this]);
        }

        return static::where('request_group', $this->request_group)->get();
    }

    /**
     * Gross price for this request: subject rate x package duration x sessions.
     *
     * Single source of truth for request pricing — used when the payment is
     * raised at approval and again when the booking is created on payment.
     */
    public function calculateAmount(): float
    {
        $this->loadMissing(['subject', 'package']);

        $subject = $this->subject;
        $package = $this->package;

        if (! $subject || ! $package) {
            return 0.0;
        }

        $rate = ($this->preferred_location ?? 'home') === 'online'
            ? (float) $subject->hourly_rate_online
            : (float) $subject->hourly_rate_home;

        return $rate * (float) $package->duration_hours * (int) $package->total_sessions;
    }

    /**
     * Money split for this request against its matched tutor's commission rate.
     *
     * @return array{amount: float, commission_amount: float, tutor_payout: float, commission_rate: float}
     */
    public function calculateSplit(?User $tutor = null): array
    {
        $tutor ??= $this->matchedTutor;

        $amount = $this->calculateAmount();
        $rate = (float) ($tutor?->tutorProfile?->commission_rate ?? 20);
        $commission = round($amount * ($rate / 100), 2);

        return [
            'amount' => round($amount, 2),
            'commission_amount' => $commission,
            'tutor_payout' => round($amount - $commission, 2),
            'commission_rate' => $rate,
        ];
    }
}
