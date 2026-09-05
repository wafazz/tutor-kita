<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Models\Centre;
use App\Models\Package;
use App\Models\Postcode;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\User;
use App\Support\Geocoding\GeocoderManager;
use App\Support\TutorMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeoMatchingTest extends TestCase
{
    use RefreshDatabase;

    // Reference points around Klang Valley.
    private const KL = [3.1390, 101.6869];

    private const PJ = [3.1073, 101.6067];        // ~9 km from KL

    private const SEREMBAN = [2.7297, 101.9381];  // ~53 km from KL

    private function tutorAt(array $point, ?int $radiusKm = null): User
    {
        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'X', 'location_state' => 'Y', 'verification_status' => 'verified',
            'latitude' => $point[0], 'longitude' => $point[1], 'travel_radius_km' => $radiusKm,
        ]);

        return $tutor->fresh();
    }

    private function requestFrom(?array $studentPoint, DeliveryMode $mode): TutorRequest
    {
        $parent = User::factory()->parent()->create();
        $student = Student::create([
            'parent_id' => $parent->id, 'name' => 'Kid', 'age' => 15,
            'latitude' => $studentPoint[0] ?? null, 'longitude' => $studentPoint[1] ?? null,
        ]);
        $subject = Subject::create([
            'name' => 'S'.uniqid(), 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true,
        ]);
        $package = Package::create([
            'name' => 'P', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
        ]);

        return TutorRequest::create([
            'parent_id' => $parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'package_id' => $package->id, 'preferred_area' => 'PJ',
            'delivery_mode' => $mode->value, 'status' => 'open',
        ]);
    }

    public function test_distance_between_two_points_is_measured_correctly(): void
    {
        $tutor = $this->tutorAt(self::KL);

        // KL to PJ is roughly 9 km.
        $this->assertEqualsWithDelta(9.0, $tutor->tutorProfile->distanceTo(...self::PJ), 1.5);
        $this->assertEqualsWithDelta(53.0, $tutor->tutorProfile->distanceTo(...self::SEREMBAN), 3.0);
    }

    public function test_a_travelling_tutor_is_matched_only_within_their_own_radius(): void
    {
        $willing = $this->tutorAt(self::PJ, radiusKm: 20);      // 9 km away, will travel 20
        $unwilling = $this->tutorAt(self::PJ, radiusKm: 5);     // 9 km away, will travel 5
        $this->tutorAt(self::SEREMBAN, radiusKm: 20);           // far beyond

        $request = $this->requestFrom(self::KL, DeliveryMode::HomeStudent);
        $ids = (new TutorMatcher)->candidatesFor($request)->pluck('tutor.id');

        $this->assertTrue($ids->contains($willing->id));
        $this->assertFalse($ids->contains($unwilling->id), 'tutor matched beyond the distance they will travel');
        $this->assertCount(1, $ids);
    }

    public function test_when_the_student_travels_the_tutors_own_radius_is_irrelevant(): void
    {
        // Will not travel at all, but the student is coming to them.
        $nearby = $this->tutorAt(self::PJ, radiusKm: 1);
        $this->tutorAt(self::SEREMBAN, radiusKm: 100);

        $request = $this->requestFrom(self::KL, DeliveryMode::HomeTutor);
        $ids = (new TutorMatcher)->candidatesFor($request, studentRadiusKm: 15)->pluck('tutor.id');

        $this->assertTrue($ids->contains($nearby->id));
        $this->assertCount(1, $ids);
    }

    public function test_online_modes_ignore_distance_entirely(): void
    {
        $this->tutorAt(self::PJ, radiusKm: 1);
        $this->tutorAt(self::SEREMBAN, radiusKm: 1);

        $request = $this->requestFrom(self::KL, DeliveryMode::OnlineSolo);
        $matches = (new TutorMatcher)->candidatesFor($request);

        $this->assertCount(2, $matches);
        $this->assertNull($matches->first()['distance_km']);
    }

    public function test_an_unplaced_student_falls_back_to_every_tutor_rather_than_none(): void
    {
        $this->tutorAt(self::PJ, radiusKm: 20);

        $request = $this->requestFrom([null, null], DeliveryMode::HomeStudent);

        // Degrades to today's manual pick instead of silently matching nothing.
        $this->assertCount(1, (new TutorMatcher)->candidatesFor($request));
    }

    public function test_a_tutor_without_coordinates_is_not_matched_on_distance(): void
    {
        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'X', 'location_state' => 'Y', 'verification_status' => 'verified',
        ]);

        $request = $this->requestFrom(self::KL, DeliveryMode::HomeStudent);

        $this->assertCount(0, (new TutorMatcher)->candidatesFor($request));
    }

    public function test_centres_are_found_by_distance_and_sorted_nearest_first(): void
    {
        $near = Centre::create(['name' => 'PJ Centre', 'address' => 'a', 'latitude' => self::PJ[0], 'longitude' => self::PJ[1], 'capacity' => 12]);
        Centre::create(['name' => 'Seremban Centre', 'address' => 'b', 'latitude' => self::SEREMBAN[0], 'longitude' => self::SEREMBAN[1], 'capacity' => 12]);
        Centre::create(['name' => 'Unplaced', 'address' => 'c', 'capacity' => 12]);

        $found = (new TutorMatcher)->centresNear(...self::KL, km: 20);

        $this->assertCount(1, $found);
        $this->assertSame($near->id, $found->first()['centre']->id);
    }

    public function test_a_centre_records_whether_the_platform_or_a_tutor_runs_it(): void
    {
        $platform = Centre::create(['name' => 'HQ', 'address' => 'a', 'capacity' => 20]);
        $tutorOwned = Centre::create(['name' => 'Home studio', 'address' => 'b', 'capacity' => 6,
            'owner_user_id' => User::factory()->tutor()->create()->id]);

        $this->assertTrue($platform->isPlatformOwned());
        $this->assertFalse($tutorOwned->isPlatformOwned());
    }

    // ---- geocoding is a configured choice ----

    public function test_geocoding_is_off_by_default_so_nothing_depends_on_an_external_service(): void
    {
        $manager = new GeocoderManager;

        $this->assertSame('manual', $manager->name());
        $this->assertNull($manager->geocode('1 Jalan Ampang, 50450 Kuala Lumpur'));
    }

    public function test_the_postcode_driver_resolves_from_the_local_table(): void
    {
        Setting::set('geocoding_driver', 'postcode');
        Postcode::create(['postcode' => '46000', 'city' => 'PJ', 'state' => 'Selangor',
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1]]);

        $result = (new GeocoderManager)->geocode('12 Jalan Something, 46000 Petaling Jaya');

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(self::PJ[0], $result->latitude, 0.001);
        $this->assertSame('postcode', $result->source);
        // Carries its own imprecision rather than looking exact.
        $this->assertSame(3.0, $result->accuracyKm);
    }

    public function test_an_unknown_postcode_resolves_to_nothing_rather_than_somewhere_wrong(): void
    {
        Setting::set('geocoding_driver', 'postcode');

        $this->assertNull((new GeocoderManager)->geocode('12 Jalan Something, 99999 Nowhere'));
    }
}
