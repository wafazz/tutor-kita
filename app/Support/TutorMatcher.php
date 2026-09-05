<?php

namespace App\Support;

use App\Enums\DeliveryMode;
use App\Models\Centre;
use App\Models\TutorRequest;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Candidate tutors for a request, narrowed by geography where the mode needs it.
 *
 * Which address the radius is measured from depends on who travels, so the
 * mode is asked rather than branched on:
 *
 *   home_student  the tutor travels — their own travel_radius_km must reach
 *                 the student's home
 *   home_tutor    the student travels — the tutor must lie inside the radius
 *                 the student is willing to cover
 *   centre_group  the student travels to a centre — measured centre to student
 *   online_*      distance is irrelevant
 */
class TutorMatcher
{
    public const DEFAULT_RADIUS_KM = 25.0;

    /** @return Collection<int, array{tutor: User, distance_km: float|null}> */
    public function candidatesFor(TutorRequest $request, ?float $studentRadiusKm = null): Collection
    {
        $mode = $request->deliveryMode();

        $verified = User::where('role', 'tutor')
            ->whereHas('tutorProfile', fn ($q) => $q->where('verification_status', 'verified'))
            ->with('tutorProfile')
            ->get();

        if (! $mode->needsGeo()) {
            return $verified->map(fn (User $t) => ['tutor' => $t, 'distance_km' => null])->values();
        }

        [$latitude, $longitude] = $this->originFor($request);

        // Unplaced request: return everyone undistanced rather than silently
        // filtering to nothing, so matching degrades to today's manual pick.
        if ($latitude === null || $longitude === null) {
            return $verified->map(fn (User $t) => ['tutor' => $t, 'distance_km' => null])->values();
        }

        $radius = $studentRadiusKm ?? self::DEFAULT_RADIUS_KM;

        return $verified
            ->map(function (User $tutor) use ($latitude, $longitude) {
                return [
                    'tutor' => $tutor,
                    'distance_km' => $tutor->tutorProfile?->distanceTo($latitude, $longitude),
                ];
            })
            ->filter(function (array $row) use ($mode, $radius) {
                $distance = $row['distance_km'];

                if ($distance === null) {
                    return false;
                }

                // When the tutor travels, their own willingness governs.
                if ($mode === DeliveryMode::HomeStudent) {
                    $limit = $row['tutor']->tutorProfile?->travel_radius_km ?? self::DEFAULT_RADIUS_KM;

                    return $distance <= $limit;
                }

                return $distance <= $radius;
            })
            ->sortBy('distance_km')
            ->values();
    }

    /** The point a radius is measured from, per the mode. */
    private function originFor(TutorRequest $request): array
    {
        $mode = $request->deliveryMode();

        if ($mode === DeliveryMode::CentreGroup) {
            // The centre this request is actually for. Falling back to whichever
            // centre came first would measure a real distance to the wrong
            // place, which reads as precision and is worse than admitting we
            // do not know where the lesson is.
            $centre = $request->centre;

            return [$centre?->latitude, $centre?->longitude];
        }

        $student = $request->student;

        return [$student?->latitude, $student?->longitude];
    }

    /** Active centres a student can reach. */
    public function centresNear(?float $latitude, ?float $longitude, float $km = self::DEFAULT_RADIUS_KM): Collection
    {
        return Centre::where('is_active', true)
            ->withinRadius($latitude, $longitude, $km)
            ->get()
            ->map(fn (Centre $c) => ['centre' => $c, 'distance_km' => $c->distanceTo($latitude, $longitude)])
            ->filter(fn (array $row) => $row['distance_km'] !== null && $row['distance_km'] <= $km)
            ->sortBy('distance_km')
            ->values();
    }

    /**
     * Assess every verified tutor against a request.
     *
     * Eligibility first — a tutor who fails a mandatory requirement is excluded
     * rather than ranked lower — then schedule and travel, then ranking among
     * whoever is left. Ineligible tutors are still returned, carrying the
     * reason, so an admin sees why someone is missing instead of wondering.
     *
     * @return Collection<int, array{
     *     tutor: User, eligible: bool, blockers: array, warnings: array,
     *     distance_km: float|null, score: float
     * }>
     */
    public function assessFor(TutorRequest $request): Collection
    {
        $eligibility = app(TutorEligibility::class);
        $detector = app(ScheduleConflictDetector::class);
        $mode = $request->deliveryMode();

        $request->loadMissing(['subject', 'student', 'centre', 'package']);

        // The same origin rule as candidatesFor: whichever address the
        // traveller is heading to, which for a centre group is that centre.
        [$latitude, $longitude] = $this->originFor($request);

        return User::where('role', 'tutor')
            ->with(['tutorProfile'])
            ->orderBy('name')
            ->get()
            ->map(function (User $tutor) use ($request, $eligibility, $detector, $mode, $latitude, $longitude) {
                $assessment = $eligibility->assess($tutor, $request);
                $profile = $tutor->tutorProfile;

                $distance = $mode->needsGeo()
                    ? $profile?->distanceTo($latitude, $longitude)
                    : null;

                // A tutor who cannot make the slot is not a candidate, however
                // well they match on paper.
                $clash = $detector->check(
                    tutorId: $tutor->id,
                    day: $request->schedule_day,
                    time: $request->schedule_time,
                    durationHours: (float) ($request->duration_hours ?? $request->package?->duration_hours ?? 1),
                    mode: $mode,
                    latitude: $latitude,
                    longitude: $longitude,
                )->first();

                if ($clash) {
                    $assessment['blockers'][] = strtolower($clash->kind === 'travel'
                        ? 'cannot travel between this and another lesson that day'
                        : 'already teaching at that time');
                    $assessment['eligible'] = false;
                }

                // Travel is only a bar when the tutor is the one travelling.
                if ($mode === DeliveryMode::HomeStudent && $distance !== null) {
                    $limit = $profile?->travel_radius_km ?? self::DEFAULT_RADIUS_KM;

                    if ($distance > $limit) {
                        $assessment['blockers'][] = sprintf('%.0f km away, beyond the %d km they travel', $distance, $limit);
                        $assessment['eligible'] = false;
                    }
                }

                return [
                    'tutor' => $tutor,
                    'eligible' => $assessment['eligible'],
                    'blockers' => $assessment['blockers'],
                    'warnings' => $assessment['warnings'],
                    'distance_km' => $distance,
                    'score' => $this->score($tutor, $distance, $assessment['warnings']),
                ];
            })
            // Eligible first, then best score, then nearest.
            ->sortBy([
                fn ($a, $b) => ($b['eligible'] <=> $a['eligible']),
                fn ($a, $b) => ($b['score'] <=> $a['score']),
            ])
            ->values();
    }

    /**
     * How good a match an eligible tutor is.
     *
     * Only ever separates tutors who are all allowed to take the work —
     * ranking never rescues someone who failed a mandatory requirement.
     */
    private function score(User $tutor, ?float $distance, array $warnings): float
    {
        $profile = $tutor->tutorProfile;

        $rating = (float) ($profile?->rating_avg ?? 0);
        $experience = min(10, (int) ($profile?->experience_years ?? 0));

        $score = ($rating * 10) + ($experience * 2);

        // Nearer is better, but never worth more than being well rated.
        if ($distance !== null) {
            $score += max(0, 20 - $distance);
        }

        // A soft concern costs a little, so an otherwise equal tutor without
        // one comes first.
        return round($score - (count($warnings) * 5), 2);
    }
}
