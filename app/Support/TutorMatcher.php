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
            $centre = Centre::where('is_active', true)
                ->whereNotNull('latitude')
                ->first();

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
}
