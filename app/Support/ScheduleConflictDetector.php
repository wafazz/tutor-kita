<?php

namespace App\Support;

use App\Enums\DeliveryMode;
use App\Models\Booking;
use App\Models\ClassSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Whether a tutor can actually take a proposed slot.
 *
 * Two things stop them: another commitment at the same time, and not enough
 * time to get between two places. The second matters as much as the first —
 * back-to-back lessons across town do not overlap on a clock but cannot both
 * be taught.
 */
class ScheduleConflictDetector
{
    /** Roughly urban driving, and a floor for parking and getting inside. */
    private const MINUTES_PER_KM = 2.0;

    private const MINIMUM_TRAVEL_MINUTES = 15;

    /** Used when a place is known to differ but one end has no coordinates. */
    private const UNKNOWN_DISTANCE_MINUTES = 30;

    /**
     * @return Collection<int, ScheduleConflict>
     */
    public function check(
        int $tutorId,
        ?string $day,
        ?string $time,
        float $durationHours,
        DeliveryMode $mode,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $ignoreClassId = null,
        ?int $ignoreBookingId = null,
    ): Collection {
        // Without a day and time there is nothing to compare; an unscheduled
        // request is not a clash, it is simply not yet placed.
        if (blank($day) || blank($time)) {
            return collect();
        }

        $start = $this->minutes($time);
        $end = $start + (int) round($durationHours * 60);

        return $this->compare(
            $this->tutorCommitments($tutorId, $day, $ignoreClassId, $ignoreBookingId),
            $start, $end, $mode, $latitude, $longitude
        );
    }

    /**
     * Whether a student can take a proposed slot.
     *
     * A student can be double-booked exactly as a tutor can — and a seat in a
     * group class is one of their bookings, so both kinds of commitment are
     * already here.
     */
    public function checkStudent(
        int $studentId,
        ?string $day,
        ?string $time,
        float $durationHours,
        DeliveryMode $mode,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $ignoreBookingId = null,
    ): Collection {
        if (blank($day) || blank($time)) {
            return collect();
        }

        $start = $this->minutes($time);
        $end = $start + (int) round($durationHours * 60);

        return $this->compare(
            $this->studentCommitments($studentId, $day, $ignoreBookingId),
            $start, $end, $mode, $latitude, $longitude
        );
    }

    /** @param  Collection<int, array>  $commitments */
    private function compare(
        Collection $commitments,
        int $start,
        int $end,
        DeliveryMode $mode,
        ?float $latitude,
        ?float $longitude
    ): Collection {
        return $commitments
            ->map(function (array $other) use ($start, $end, $mode, $latitude, $longitude) {
                $range = $this->range($other['start'], $other['end']);

                if ($start < $other['end'] && $other['start'] < $end) {
                    return new ScheduleConflict('overlap', $other['what'], $range);
                }

                // Neither in-person? Then only the clock matters.
                if (! $mode->needsGeo() || ! $other['mode']->needsGeo()) {
                    return null;
                }

                $gap = $start >= $other['end']
                    ? $start - $other['end']
                    : $other['start'] - $end;

                $needed = $this->travelMinutes($latitude, $longitude, $other['latitude'], $other['longitude']);

                if ($needed === 0 || $gap >= $needed) {
                    return null;
                }

                // Only the commitment that comes first can push this one later.
                $earliest = $start >= $other['end']
                    ? $this->clock($other['end'] + $needed)
                    : $this->clock($start);

                return new ScheduleConflict('travel', $other['what'], $range, $needed, $earliest);
            })
            ->filter()
            ->values();
    }

    /** Everything the student is already committed to on that day. */
    private function studentCommitments(int $studentId, string $day, ?int $ignoreBookingId): Collection
    {
        return Booking::where('student_id', $studentId)
            ->where('schedule_day', $day)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->when($ignoreBookingId, fn ($q) => $q->whereKeyNot($ignoreBookingId))
            ->with(['student:id,name,latitude,longitude', 'subject:id,name', 'tutor.tutorProfile', 'classEnrolment.classSession.centre'])
            ->get()
            ->map(fn (Booking $b) => $this->describe($b))
            ->sortBy('start')
            ->values();
    }

    /** Everything the tutor is already committed to on that day. */
    private function tutorCommitments(int $tutorId, string $day, ?int $ignoreClassId, ?int $ignoreBookingId): Collection
    {
        $bookings = Booking::where('tutor_id', $tutorId)
            ->where('schedule_day', $day)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->when($ignoreBookingId, fn ($q) => $q->whereKeyNot($ignoreBookingId))
            ->with(['student:id,name,latitude,longitude', 'subject:id,name', 'tutor.tutorProfile', 'classEnrolment.classSession.centre'])
            ->get()
            ->map(fn (Booking $b) => $this->describe($b));

        $classes = ClassSession::where('tutor_id', $tutorId)
            ->where('schedule_day', $day)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->when($ignoreClassId, fn ($q) => $q->whereKeyNot($ignoreClassId))
            ->with(['centre', 'subject:id,name'])
            ->get()
            ->map(function (ClassSession $c) {
                $start = $this->minutes((string) $c->schedule_time);

                return [
                    'what' => "'".($c->title ?? $c->subject?->name ?? 'a group class')."'",
                    'start' => $start,
                    'end' => $start + (int) round((float) $c->duration_hours * 60),
                    'mode' => $c->delivery_mode,
                    'latitude' => $c->centre?->latitude,
                    'longitude' => $c->centre?->longitude,
                ];
            });

        return $bookings->concat($classes)->sortBy('start')->values();
    }

    /** One booking, as a commitment to compare against. */
    private function describe(Booking $b): array
    {
        $start = $this->minutes((string) $b->schedule_time);
        $mode = $b->delivery_mode ? DeliveryMode::from($b->delivery_mode) : DeliveryMode::HomeStudent;
        $class = $b->classEnrolment?->classSession;

        [$lat, $lng] = $this->placeOf($mode, $b->student, $b->tutor?->tutorProfile, $class?->centre);

        return [
            'what' => $class
                ? "'".($class->title ?? $class->subject?->name ?? 'a group class')."'"
                : ($b->subject?->name ? "a {$b->subject->name} lesson" : 'a lesson'),
            'start' => $start,
            'end' => $start + (int) round((float) $b->duration_hours * 60),
            'mode' => $mode,
            'latitude' => $lat,
            'longitude' => $lng,
        ];
    }

    /** Where a commitment physically happens, per who travels. */
    private function placeOf(DeliveryMode $mode, $student, $tutorProfile, $centre): array
    {
        return match ($mode->traveller()) {
            'tutor' => [$student?->latitude, $student?->longitude],
            'student' => $centre
                ? [$centre->latitude, $centre->longitude]
                : [$tutorProfile?->latitude, $tutorProfile?->longitude],
            default => [null, null],
        };
    }

    /**
     * Minutes needed to get between two places.
     *
     * Same place costs nothing — two classes at one centre are back to back by
     * design. When a place is unknown a flat allowance applies rather than
     * assuming the tutor teleports.
     */
    private function travelMinutes(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): int
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return self::UNKNOWN_DISTANCE_MINUTES;
        }

        $distance = $this->haversineKm($lat1, $lng1, $lat2, $lng2);

        if ($distance < 0.2) {
            return 0;
        }

        return max(self::MINIMUM_TRAVEL_MINUTES, (int) ceil($distance * self::MINUTES_PER_KM));
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $from = deg2rad($lat1);
        $to = deg2rad($lat2);
        $a = sin(($to - $from) / 2) ** 2
            + cos($from) * cos($to) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;

        return 6371.0088 * 2 * asin(min(1.0, sqrt($a)));
    }

    private function minutes(string $time): int
    {
        $parsed = Carbon::createFromFormat('H:i:s', str_pad(substr($time, 0, 8), 8, ':00'));

        return $parsed->hour * 60 + $parsed->minute;
    }

    private function clock(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60) % 24, $minutes % 60);
    }

    private function range(int $start, int $end): string
    {
        return $this->clock($start).'–'.$this->clock($end);
    }
}
