<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use App\Enums\GroupPayoutModel;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    protected $fillable = [
        'tutor_id', 'subject_id', 'centre_id', 'delivery_mode', 'title',
        'schedule_day', 'schedule_time', 'duration_hours', 'total_sessions', 'starts_on',
        'capacity', 'price_per_student',
        'payout_model', 'payout_base', 'payout_per_head', 'payout_head_threshold',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'delivery_mode' => DeliveryMode::class,
            'payout_model' => GroupPayoutModel::class,
            'duration_hours' => 'decimal:1',
            'price_per_student' => 'decimal:2',
            'payout_base' => 'decimal:2',
            'payout_per_head' => 'decimal:2',
            'starts_on' => 'date',
        ];
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    public function enrolments()
    {
        return $this->hasMany(ClassEnrolment::class);
    }

    public function activeEnrolments()
    {
        return $this->enrolments()->whereIn('status', ['pending', 'active']);
    }

    // ---- seats ----

    public function seatsTaken(): int
    {
        return $this->relationLoaded('enrolments')
            ? $this->enrolments->whereIn('status', ['pending', 'active'])->count()
            : $this->activeEnrolments()->count();
    }

    public function seatsLeft(): int
    {
        // Capacity is also bounded by the venue: a centre cannot seat more
        // people than it holds, however the class was configured.
        $limit = min($this->capacity, $this->centre?->capacity ?? $this->capacity);

        return max(0, $limit - $this->seatsTaken());
    }

    public function hasSeat(): bool
    {
        return $this->seatsLeft() > 0;
    }

    // ---- money ----

    /** What one student pays for the whole class. */
    public function priceForStudent(): float
    {
        return round((float) $this->price_per_student * (int) $this->total_sessions, 2);
    }

    /** What every enrolled student pays together. */
    public function revenue(): float
    {
        return round($this->priceForStudent() * $this->seatsTaken(), 2);
    }

    /**
     * What the tutor is owed for this class in total, per its payout model.
     *
     * Only the total is decided here. It is then divided across the enrolled
     * students' bookings, so however the tutor is paid, the money still moves
     * through the same per-booking ledger and its invariants continue to hold.
     */
    public function tutorPayoutTotal(?int $headcount = null): float
    {
        $heads = $headcount ?? $this->seatsTaken();

        if ($heads === 0) {
            return 0.0;
        }

        return match ($this->payout_model) {
            GroupPayoutModel::Flat => round((float) $this->payout_base, 2),

            GroupPayoutModel::FlatPlusHead => round(
                (float) $this->payout_base
                + max(0, $heads - (int) ($this->payout_head_threshold ?? 0)) * (float) $this->payout_per_head,
                2
            ),

            // Per student: the tutor's share of what each student paid, at the
            // tutor's own commission rate.
            default => round(
                $this->revenue() * (1 - $this->commissionRate() / 100),
                2
            ),
        };
    }

    /** What the platform keeps once the tutor is paid. */
    public function platformShare(?int $headcount = null): float
    {
        return round($this->revenue() - $this->tutorPayoutTotal($headcount), 2);
    }

    public function commissionRate(): float
    {
        return (float) ($this->tutor?->tutorProfile?->commission_rate ?? Setting::defaultCommissionRate());
    }

    /**
     * Whether a fixed payout currently exceeds what the students are paying.
     *
     * A flat rate promised against an under-filled class loses money, and that
     * should be visible before the class runs rather than at payout time.
     */
    public function isUnderwater(): bool
    {
        return ! $this->payout_model->followsEnrolmentRevenue()
            && $this->seatsTaken() > 0
            && $this->tutorPayoutTotal() > $this->revenue();
    }
}
