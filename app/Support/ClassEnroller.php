<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\ClassEnrolment;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\Student;
use App\Models\TutorSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Puts a student into a group class.
 *
 * Each enrolment creates that student's own booking and payment, so group
 * revenue moves through exactly the same ledger as one-to-one work — the same
 * allocation, accrual, claiming and invariants. What differs is only how much
 * of it the tutor is owed, which the class decides in total and this divides
 * across the bookings.
 */
class ClassEnroller
{
    public function enrol(ClassSession $class, Student $student): ClassEnrolment
    {
        return DB::transaction(function () use ($class, $student) {
            // Re-read under lock: two parents can take the last seat at once.
            $class = ClassSession::whereKey($class->getKey())->lockForUpdate()->firstOrFail();
            $class->load('centre');

            if (! $class->hasSeat()) {
                throw new \RuntimeException("'{$class->title}' is full.");
            }

            if ($class->enrolments()->where('student_id', $student->id)->exists()) {
                throw new \RuntimeException("{$student->name} is already enrolled in this class.");
            }

            // A student can be double-booked exactly as a tutor can, and one of
            // their own lessons is no less of a clash than someone else's.
            $clash = app(ScheduleConflictDetector::class)->checkStudent(
                studentId: $student->id,
                day: $class->schedule_day,
                time: $class->schedule_time,
                durationHours: (float) $class->duration_hours,
                mode: $class->delivery_mode,
                latitude: $class->centre?->latitude,
                longitude: $class->centre?->longitude,
            )->first();

            if ($clash) {
                throw new \RuntimeException($clash->message($student->name));
            }

            $price = $class->priceForStudent();

            $payment = Payment::create([
                'parent_id' => $student->parent_id,
                'amount' => $price,
                // Provisional: the tutor's real share is settled across the
                // class once the headcount is known.
                'commission_amount' => $price,
                'tutor_payout' => 0,
                'payment_method' => 'fpx',
                'status' => 'pending',
            ]);

            $booking = Booking::create([
                'tutor_id' => $class->tutor_id,
                'parent_id' => $student->parent_id,
                'student_id' => $student->id,
                'subject_id' => $class->subject_id,
                'schedule_day' => $class->schedule_day ?? 'monday',
                'schedule_time' => $class->schedule_time ?? '10:00',
                'duration_hours' => $class->duration_hours,
                'location_type' => $class->delivery_mode->isOnline() ? 'online' : 'center',
                'delivery_mode' => $class->delivery_mode->value,
                'hourly_rate' => 0,
                'commission_rate' => $class->commissionRate(),
                'amount' => $price,
                'commission_amount' => $price,
                'tutor_payout' => 0,
                'payment_id' => $payment->id,
                'status' => 'confirmed',
            ]);

            $payment->update(['booking_id' => $booking->id]);

            $enrolment = ClassEnrolment::create([
                'class_session_id' => $class->id,
                'student_id' => $student->id,
                'parent_id' => $student->parent_id,
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'status' => 'pending',
            ]);

            // A class runs on a schedule, so the sessions it implies exist from
            // the moment a seat is taken. Without them the booking looks like
            // nothing was ever delivered, and the tutor accrues nothing however
            // much the class owes them.
            $this->scheduleSessions($class, $booking);

            $this->settle($class->fresh());

            return $enrolment->fresh();
        });
    }

    /**
     * Divide the class's total tutor payout across the enrolled bookings.
     *
     * Re-run whenever the headcount changes, because under a flat or
     * flat-plus-head model every student's share moves when one more joins.
     * Money already committed to a payout is never disturbed: a booking's share
     * cannot fall below what has been paid out for it.
     */
    public function settle(ClassSession $class): void
    {
        $bookings = Booking::whereIn(
            'id',
            $class->activeEnrolments()->pluck('booking_id')->filter()
        )->orderBy('id')->get();

        if ($bookings->isEmpty()) {
            return;
        }

        $total = $class->tutorPayoutTotal($bookings->count());
        $each = round($total / $bookings->count(), 2);

        $running = 0.0;
        $last = $bookings->count() - 1;

        foreach ($bookings->values() as $i => $booking) {
            // The remainder lands on the final booking so the shares always sum
            // back to the class total.
            $share = $i === $last ? round($total - $running, 2) : $each;
            $running += $share;

            $share = max($share, (float) $booking->paid_out_amount);

            $booking->forceFill([
                'tutor_payout' => $share,
                'commission_amount' => round((float) $booking->amount - $share, 2),
            ])->save();

            if ($booking->payment) {
                $booking->payment->forceFill([
                    'tutor_payout' => $share,
                    'commission_amount' => round((float) $booking->payment->amount - $share, 2),
                ])->save();
            }
        }
    }

    public function cancel(ClassEnrolment $enrolment): void
    {
        DB::transaction(function () use ($enrolment) {
            $enrolment->update(['status' => 'cancelled']);
            $this->settle($enrolment->classSession->fresh());
        });
    }

    /**
     * Lay out this student's sessions across the run of the class.
     *
     * One per week from the start date, which is what "10 weekly sessions"
     * means in practice. They are created as scheduled, not completed —
     * delivery is still recorded by the tutor as normal.
     */
    private function scheduleSessions(ClassSession $class, Booking $booking): void
    {
        $start = $class->starts_on
            ? Carbon::parse($class->starts_on)
            : $this->nextOccurrence($class->schedule_day);

        $startTime = substr((string) ($class->schedule_time ?? '10:00'), 0, 5);
        $endTime = Carbon::parse($startTime)->addMinutes((int) round((float) $class->duration_hours * 60))->format('H:i');

        for ($i = 0; $i < max(1, (int) $class->total_sessions); $i++) {
            TutorSession::create([
                'booking_id' => $booking->id,
                'session_date' => $start->copy()->addWeeks($i)->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'check_in_token' => bin2hex(random_bytes(8)),
                'duration_minutes' => (int) round((float) $class->duration_hours * 60),
                'status' => 'scheduled',
            ]);
        }
    }

    /** The next date falling on the class's weekday, today included. */
    private function nextOccurrence(?string $day): Carbon
    {
        if (blank($day)) {
            return Carbon::today();
        }

        $target = Carbon::parse($day)->dayOfWeek;
        $date = Carbon::today();

        while ($date->dayOfWeek !== $target) {
            $date->addDay();
        }

        return $date;
    }
}
