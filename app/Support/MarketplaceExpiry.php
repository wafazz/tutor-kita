<?php

namespace App\Support;

use App\Models\ClassEnrolment;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\TutorRequest;
use Illuminate\Support\Facades\DB;

/**
 * Releases work that has been held too long.
 *
 * A matched tutor is unavailable to everyone else while they decide, and a
 * held seat is unavailable while its payment is outstanding. Without a time
 * limit that hold is indefinite, so one parent who never pays can keep a tutor
 * or a seat out of the market forever.
 *
 * Expiring never touches anything settled: a paid payment, an accepted job in
 * progress, or a booking that exists.
 */
class MarketplaceExpiry
{
    public function acceptanceHours(): int
    {
        return max(1, (int) Setting::get('acceptance_expiry_hours', 24));
    }

    public function paymentHours(): int
    {
        return max(1, (int) Setting::get('payment_expiry_hours', 48));
    }

    /**
     * @return array{acceptances: int, payments: int, seats: int}
     */
    public function sweep(): array
    {
        return [
            'acceptances' => $this->expireUnacceptedMatches(),
            'payments' => $this->expireUnpaidPayments(),
            'seats' => $this->releaseUnpaidSeats(),
        ];
    }

    /**
     * A tutor who has not answered releases the request back to the market.
     *
     * The request returns to open with no tutor attached, which is what makes
     * it re-matchable — the audit's recovery flow: release, reopen, re-match.
     */
    public function expireUnacceptedMatches(): int
    {
        $cutoff = now()->subHours($this->acceptanceHours());
        $released = 0;

        TutorRequest::where('status', 'matched')
            ->where('tutor_accepted', false)
            ->whereNotNull('matched_at')
            ->where('matched_at', '<', $cutoff)
            // Never reopen something a parent has already paid for.
            ->whereDoesntHave('payment', fn ($q) => $q->whereIn('status', ['success', 'refunded']))
            ->chunkById(100, function ($requests) use (&$released) {
                foreach ($requests as $request) {
                    DB::transaction(function () use ($request, &$released) {
                        $request->update([
                            'status' => 'open',
                            'matched_tutor_id' => null,
                            'matched_at' => null,
                            'tutor_accepted' => false,
                        ]);

                        // The unpaid invoice raised for that match goes with it.
                        Payment::where('tutor_request_id', $request->id)
                            ->where('status', 'pending')
                            ->update(['status' => 'expired']);

                        $released++;
                    });
                }
            });

        return $released;
    }

    /**
     * An invoice nobody paid stops holding the tutor.
     *
     * Measured from when the payment was raised, since that is when the parent
     * was first able to pay.
     */
    public function expireUnpaidPayments(): int
    {
        $cutoff = now()->subHours($this->paymentHours());
        $expired = 0;

        Payment::where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->whereNull('booking_id')
            ->chunkById(100, function ($payments) use (&$expired) {
                foreach ($payments as $payment) {
                    DB::transaction(function () use ($payment, &$expired) {
                        $payment->update(['status' => 'expired']);

                        // Reopen the request so another tutor can be matched.
                        if ($request = $payment->tutorRequest) {
                            if (in_array($request->status, ['matched'], true)) {
                                $request->update([
                                    'status' => 'open',
                                    'matched_tutor_id' => null,
                                    'matched_at' => null,
                                    'tutor_accepted' => false,
                                ]);
                            }
                        }

                        $expired++;
                    });
                }
            });

        return $expired;
    }

    /**
     * A held seat nobody paid for goes back to the class.
     *
     * Same problem in the group flow: an unpaid enrolment occupies capacity
     * another student could have used.
     */
    public function releaseUnpaidSeats(): int
    {
        $cutoff = now()->subHours($this->paymentHours());
        $released = 0;

        ClassEnrolment::where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->whereHas('payment', fn ($q) => $q->whereIn('status', ['pending', 'expired', 'failed']))
            ->with('classSession')
            ->chunkById(100, function ($enrolments) use (&$released) {
                foreach ($enrolments as $enrolment) {
                    DB::transaction(function () use ($enrolment, &$released) {
                        $enrolment->update(['status' => 'cancelled']);

                        if ($enrolment->payment && $enrolment->payment->status === 'pending') {
                            $enrolment->payment->update(['status' => 'expired']);
                        }

                        // The remaining students' shares move when the
                        // headcount does under a fixed payout.
                        if ($class = $enrolment->classSession) {
                            app(ClassEnroller::class)->settle($class->fresh());
                        }

                        $released++;
                    });
                }
            });

        return $released;
    }
}
