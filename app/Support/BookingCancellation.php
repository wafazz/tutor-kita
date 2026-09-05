<?php

namespace App\Support;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

/**
 * Ends a booking across every record it touches.
 *
 * Setting a status to cancelled is not a cancellation. After payment there is a
 * booking, its sessions, the parent's money and the tutor's entitlement, and
 * each has to be decided — otherwise a "cancelled" request leaves a confirmed
 * booking, scheduled sessions, and a tutor still earning for lessons that will
 * never happen.
 *
 * The policy applied here:
 *
 *   Delivered sessions are kept. The tutor taught them and has earned them, and
 *   the accrual model already reflects that.
 *
 *   Undelivered sessions are cancelled, which stops further accrual by itself.
 *
 *   The parent is owed back the undelivered share, less anything already paid
 *   out to the tutor — the platform cannot refund money it has already sent on.
 */
class BookingCancellation
{
    /**
     * @return array{refundable: float, delivered: int, cancelled_sessions: int, tutor_keeps: float}
     */
    public function cancel(Booking $booking, ?string $reason = null): array
    {
        return DB::transaction(function () use ($booking, $reason) {
            $booking = Booking::whereKey($booking->getKey())
                ->lockForUpdate()
                ->with(['sessions', 'payment', 'tutorRequest.package', 'classEnrolment.classSession'])
                ->firstOrFail();

            $total = max(1, $this->sessionsExpected($booking));
            $delivered = $booking->sessions->where('status', 'completed')->count();

            // Anything not yet taught stops here, which also stops accrual.
            $cancelled = $booking->sessions
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->each(fn ($session) => $session->update(['status' => 'cancelled']))
                ->count();

            $payout = round((float) $booking->tutor_payout, 2);
            $amount = round((float) $booking->amount, 2);

            // What the tutor has earned by teaching, and keeps.
            $tutorKeeps = round($payout * (min($delivered, $total) / $total), 2);

            // Already sent on, and therefore not refundable however the rest
            // is decided.
            $alreadyPaidOut = round((float) $booking->paid_out_amount, 2);

            $deliveredValue = round($amount * (min($delivered, $total) / $total), 2);
            $refundable = round(max(0, $amount - $deliveredValue - max(0, $alreadyPaidOut - $tutorKeeps)), 2);

            // tutor_payout is deliberately left at the contract value.
            // Cancelling the undelivered sessions already caps accrual at what
            // was taught — overwriting it would apply that proportion twice and
            // pay the tutor less than they earned.
            $booking->forceFill([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ])->save();

            if ($payment = $booking->payment) {
                $payment->forceFill([
                    'refunded_amount' => round((float) $payment->refunded_amount + $refundable, 2),
                    'refunded_at' => $refundable > 0 ? now() : $payment->refunded_at,
                    // Only a payment refunded in full changes state; a partial
                    // refund is still a successful payment that was reduced.
                    'status' => $refundable > 0 && $refundable + 0.001 >= (float) $payment->amount
                        ? 'refunded'
                        : $payment->status,
                ])->save();
            }

            if ($enrolment = $booking->classEnrolment) {
                $enrolment->update(['status' => 'cancelled']);
            }

            return [
                'refundable' => $refundable,
                'delivered' => $delivered,
                'cancelled_sessions' => $cancelled,
                'tutor_keeps' => max($tutorKeeps, $alreadyPaidOut),
            ];
        });
    }

    /** How many sessions this booking was meant to run. */
    private function sessionsExpected(Booking $booking): int
    {
        $package = $booking->tutorRequest?->package;
        $class = $package ? null : $booking->classEnrolment?->classSession;

        return max(1, (int) ($package->total_sessions ?? $class?->total_sessions ?? $booking->sessions->count() ?: 1));
    }

    /**
     * Whether this booking has money attached that a cancellation would have
     * to decide, rather than simply dropping.
     */
    public function hasSettledMoney(Booking $booking): bool
    {
        return $booking->payment?->status === 'success';
    }
}
