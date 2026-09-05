<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Payment extends Model
{
    protected $fillable = [
        'tutor_request_id', 'booking_id', 'session_id', 'parent_id', 'amount', 'commission_amount',
        'tutor_payout', 'payment_method', 'gateway', 'transaction_id', 'status', 'paid_at',
        'refunded_amount', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'tutor_payout' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_amount' => 'decimal:2',
            'refunded_at' => 'datetime',
        ];
    }

    public function tutorRequest()
    {
        return $this->belongsTo(TutorRequest::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function session()
    {
        return $this->belongsTo(TutorSession::class, 'session_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Every booking this payment settles.
     *
     * A grouped request raises one payment covering the whole group, so this
     * spans each subject's booking — and therefore each matched tutor.
     */
    public function relatedBookings(): Collection
    {
        $request = $this->tutorRequest;

        if (! $request) {
            return Booking::whereKey($this->booking_id)->with('tutorRequest')->get();
        }

        $requestIds = $request->request_group
            ? TutorRequest::where('request_group', $request->request_group)->pluck('id')
            : collect([$request->id]);

        return Booking::whereIn('tutor_request_id', $requestIds)
            ->with('tutorRequest')
            ->orderBy('id')
            ->get();
    }

    /**
     * Distribute this payment's recorded totals across its bookings.
     *
     * The payment is authoritative — it is what the parent was actually
     * charged — so shares are weighted by each request's price rather than
     * recomputed from current rates, which can drift. Rounding remainder
     * lands on the largest share so the parts always sum back to the whole.
     */
    public function allocateToBookings(?Collection $bookings = null): void
    {
        $bookings = ($bookings ?? $this->relatedBookings())->values();

        if ($bookings->isEmpty()) {
            return;
        }

        $weights = $bookings->map(fn ($b) => max(0.0, (float) ($b->tutorRequest?->calculateAmount() ?? 0.0)));
        $totalWeight = (float) $weights->sum();

        // No usable prices (deleted subject or package) — fall back to an even split.
        if ($totalWeight <= 0.0) {
            $weights = $bookings->map(fn () => 1.0);
            $totalWeight = (float) $bookings->count();
        }

        $largest = $weights->search($weights->max());

        $shares = [];
        foreach (['amount', 'commission_amount', 'tutor_payout'] as $field) {
            $total = round((float) $this->{$field}, 2);
            $running = 0.0;

            foreach ($weights as $i => $weight) {
                $shares[$field][$i] = round($total * ($weight / $totalWeight), 2);
                $running += $shares[$field][$i];
            }

            $drift = round($total - $running, 2);
            if (abs($drift) >= 0.01) {
                $shares[$field][$largest] = round($shares[$field][$largest] + $drift, 2);
            }
        }

        foreach ($bookings as $i => $booking) {
            $booking->forceFill([
                'payment_id' => $this->getKey(),
                'amount' => $shares['amount'][$i],
                'commission_amount' => $shares['commission_amount'][$i],
                'tutor_payout' => $shares['tutor_payout'][$i],
            ])->save();
        }
    }

    /** Set when this payment buys a seat in a group class. */
    public function enrolment()
    {
        return $this->hasOne(ClassEnrolment::class);
    }

    public function isForClassEnrolment(): bool
    {
        return $this->enrolment()->exists();
    }
}
