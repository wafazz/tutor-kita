<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'tutor_request_id', 'tutor_id', 'parent_id', 'student_id', 'subject_id',
        'schedule_day', 'schedule_time', 'duration_hours', 'hourly_rate', 'commission_rate',
        'amount', 'commission_amount', 'tutor_payout', 'payment_id', 'paid_out_amount',
        'cancellation_reason', 'cancelled_at',
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
            'paid_out_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
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
     * Payout runs that have paid some slice of this booking. Under per_session
     * accrual a booking is settled across several runs.
     */
    public function tutorPayouts()
    {
        return $this->belongsToMany(TutorPayout::class, 'booking_tutor_payout')
            ->withPivot('amount')
            ->withTimestamps();
    }

    /** Set when this booking is a seat in a group class. */
    public function classEnrolment()
    {
        return $this->hasOne(ClassEnrolment::class);
    }

    public function completedSessionsCount(): int
    {
        return $this->relationLoaded('sessions')
            ? $this->sessions->where('status', 'completed')->count()
            : $this->sessions()->where('status', 'completed')->count();
    }

    /**
     * How much of this booking's payout the tutor has earned so far, per the
     * package's payout policy. Independent of what has already been paid.
     */
    public function accruedPayout(): float
    {
        // Nothing accrues until the parent's payment has actually succeeded.
        if ($this->payment?->status !== 'success') {
            return 0.0;
        }

        $total = round((float) $this->tutor_payout, 2);
        $package = $this->tutorRequest?->package;

        // A seat in a group class has no package — the class itself says how
        // many sessions it runs. Without this the booking looks like a
        // one-session package with nothing delivered, and accrues nothing
        // however much the tutor is owed.
        $class = $package ? null : $this->classEnrolment?->classSession;

        $policy = $package->payout_policy ?? 'per_session';

        if ($policy === 'upfront') {
            return $total;
        }

        $totalSessions = max(1, (int) ($package->total_sessions ?? $class?->total_sessions ?? 1));
        $completed = $this->completedSessionsCount();

        if ($policy === 'on_completion') {
            return $completed >= $totalSessions ? $total : 0.0;
        }

        // per_session: accrue a share per delivered session, never past the whole.
        return round($total * (min($completed, $totalSessions) / $totalSessions), 2);
    }

    /**
     * Earned but not yet committed to a payout run.
     */
    public function payableNow(): float
    {
        return round(max(0.0, $this->accruedPayout() - (float) $this->paid_out_amount), 2);
    }
}
