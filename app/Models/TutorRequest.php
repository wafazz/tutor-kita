<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use Illuminate\Database\Eloquent\Model;

class TutorRequest extends Model
{
    protected $fillable = [
        'request_group', 'parent_id', 'student_id', 'subject_id', 'package_id', 'preferred_area', 'preferred_location',
        'preferred_schedule', 'preferred_time', 'preferred_tutor_gender',
        'budget_min', 'budget_max', 'notes', 'status', 'matched_tutor_id', 'matched_at',
        'tutor_accepted', 'schedule_day', 'schedule_time', 'duration_hours', 'location_type', 'location_address',
        'delivery_mode',
    ];

    protected function casts(): array
    {
        return [
            'delivery_mode' => DeliveryMode::class,
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

        // Rate is resolved per delivery mode, falling back down a chain rather
        // than to zero — an unpriced mode would charge the parent nothing and
        // earn the tutor nothing.
        $rate = $subject->rateFor($this->deliveryMode());

        if ($rate === null) {
            return 0.0;
        }

        return $rate * (float) $package->duration_hours * (int) $package->total_sessions;
    }

    /**
     * How this lesson is delivered.
     *
     * Falls back to the legacy preferred_location for rows written before
     * delivery modes existed, so pricing never depends on the backfill having
     * reached a given row.
     */
    public function deliveryMode(): DeliveryMode
    {
        if ($this->delivery_mode instanceof DeliveryMode) {
            return $this->delivery_mode;
        }

        return ($this->preferred_location ?? 'home') === 'online'
            ? DeliveryMode::OnlineSolo
            : DeliveryMode::HomeStudent;
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
        $rate = (float) ($tutor?->tutorProfile?->commission_rate ?? Setting::defaultCommissionRate());
        $commission = round($amount * ($rate / 100), 2);

        return [
            'amount' => round($amount, 2),
            'commission_amount' => $commission,
            'tutor_payout' => round($amount - $commission, 2),
            'commission_rate' => $rate,
        ];
    }
}
