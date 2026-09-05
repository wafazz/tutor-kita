<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\TutorSession;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Lays a booking's sessions out across the weeks it runs.
 *
 * Sessions are what delivery is recorded against, so under per-session accrual
 * a booking with fewer sessions than its package promises can never be fully
 * paid. Creating them is therefore not an administrative nicety — it is what
 * makes the money reachable.
 */
class SessionScheduler
{
    private const DAYS = [
        'sunday' => Carbon::SUNDAY, 'monday' => Carbon::MONDAY, 'tuesday' => Carbon::TUESDAY,
        'wednesday' => Carbon::WEDNESDAY, 'thursday' => Carbon::THURSDAY,
        'friday' => Carbon::FRIDAY, 'saturday' => Carbon::SATURDAY,
    ];

    /**
     * Create weekly sessions until the booking has $target of them.
     *
     * Counts what already exists rather than adding blindly, and skips dates
     * already taken, so calling this twice does not double up — the tutor's
     * own "generate sessions" button and this can both run safely.
     *
     * @return int how many were created
     */
    public function ensure(Booking $booking, int $target, ?Carbon $from = null, ?float $durationHours = null): int
    {
        $existing = $booking->sessions()->get();

        if ($existing->count() >= $target) {
            return 0;
        }

        $start = $from ?? $this->firstDate($booking->schedule_day);
        $taken = $existing->pluck('session_date')
            ->filter()
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        $duration = (float) ($durationHours ?? $booking->duration_hours ?? 1);
        $startTime = substr((string) ($booking->schedule_time ?? '10:00'), 0, 5);
        $endTime = Carbon::parse($startTime)->addMinutes((int) round($duration * 60))->format('H:i');

        $created = 0;
        $week = 0;

        // Bounded so a bad target cannot spin: a booking is not scheduled
        // years out by accident.
        while ($existing->count() + $created < $target && $week < 260) {
            $date = $start->copy()->addWeeks($week);
            $week++;

            if (in_array($date->toDateString(), $taken, true)) {
                continue;
            }

            TutorSession::create([
                'booking_id' => $booking->id,
                'session_date' => $date->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'check_in_token' => Str::uuid()->toString(),
                'duration_minutes' => (int) round($duration * 60),
                'status' => 'scheduled',
            ]);

            $taken[] = $date->toDateString();
            $created++;
        }

        return $created;
    }

    /** How many sessions this booking is meant to run. */
    public function targetFor(Booking $booking): int
    {
        $package = $booking->tutorRequest?->package;
        $class = $package ? null : $booking->classEnrolment?->classSession;

        return max(1, (int) ($package->total_sessions ?? $class?->total_sessions ?? 1));
    }

    /** The next date falling on the booking's weekday, today included. */
    private function firstDate(?string $day): Carbon
    {
        $target = self::DAYS[strtolower((string) $day)] ?? null;

        if ($target === null) {
            return Carbon::today();
        }

        $date = Carbon::today();

        while ($date->dayOfWeek !== $target) {
            $date->addDay();
        }

        return $date;
    }
}
