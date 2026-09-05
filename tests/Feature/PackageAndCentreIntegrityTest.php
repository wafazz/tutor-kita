<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Models\Centre;
use App\Models\Package;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\User;
use App\Support\TutorMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageAndCentreIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private const PJ = [3.1073, 101.6067];

    private const KLANG = [3.0449, 101.4455];   // ~19 km from PJ

    private User $parent;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parent = User::factory()->parent()->create();
        $this->student = Student::create([
            'parent_id' => $this->parent->id, 'name' => 'Ali', 'age' => 14,
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1],
        ]);
    }

    private function subject(string $name): Subject
    {
        return Subject::create(['name' => $name, 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);
    }

    private function specificPackage(array $subjects): Package
    {
        $package = Package::create(['name' => 'Science Bundle', 'package_type' => 'specific',
            'total_sessions' => 4, 'duration_hours' => 2, 'price' => 0,
            'is_active' => true, 'sort_order' => 1]);

        $package->subjects()->sync(collect($subjects)->pluck('id'));

        return $package->fresh();
    }

    // ---- 4. a package may only be requested for the subjects it covers ----

    public function test_a_subject_outside_the_package_is_rejected(): void
    {
        $maths = $this->subject('Maths');
        $history = $this->subject('History');

        // One subject, so this takes the single-subject path where a subject
        // is chosen rather than derived from the package.
        $package = $this->specificPackage([$maths]);

        $this->actingAs($this->parent)->post('/parent/requests', [
            'student_id' => $this->student->id,
            'subject_id' => $history->id,
            'package_id' => $package->id,
            'preferred_location' => 'online',
            'preferred_time' => 'evening',
            'preferred_tutor_gender' => 'both',
        ])->assertSessionHasErrors('subject_id');

        $this->assertSame(0, TutorRequest::count());
    }

    public function test_a_subject_inside_the_package_is_accepted(): void
    {
        $maths = $this->subject('Maths');
        $package = $this->specificPackage([$maths]);

        $this->actingAs($this->parent)->post('/parent/requests', [
            'student_id' => $this->student->id,
            'subject_id' => $maths->id,
            'package_id' => $package->id,
            'preferred_location' => 'online',
            'preferred_time' => 'evening',
            'preferred_tutor_gender' => 'both',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, TutorRequest::count());
    }

    public function test_an_all_subjects_package_covers_anything(): void
    {
        $history = $this->subject('History');
        $package = Package::create(['name' => 'Open', 'package_type' => 'all',
            'total_sessions' => 4, 'duration_hours' => 2, 'price' => 0,
            'is_active' => true, 'sort_order' => 1]);

        $this->actingAs($this->parent)->post('/parent/requests', [
            'student_id' => $this->student->id,
            'subject_id' => $history->id,
            'package_id' => $package->id,
            'preferred_location' => 'online',
            'preferred_time' => 'evening',
            'preferred_tutor_gender' => 'both',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, TutorRequest::count());
    }

    // ---- 5. distance for a centre group is measured from that centre ----

    private function tutorAt(array $point): User
    {
        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => ['Maths'], 'hourly_rate' => 50,
            'location_area' => 'X', 'location_state' => 'Y', 'verification_status' => 'verified',
            'commission_rate' => 20, 'latitude' => $point[0], 'longitude' => $point[1],
            'travel_radius_km' => 100,
        ]);

        return $tutor->fresh();
    }

    private function centreRequest(?Centre $centre): TutorRequest
    {
        $maths = $this->subject('Maths');
        $package = Package::create(['name' => 'P', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1]);

        return TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $this->student->id,
            'subject_id' => $maths->id, 'package_id' => $package->id, 'centre_id' => $centre?->id,
            'preferred_area' => 'PJ', 'delivery_mode' => DeliveryMode::CentreGroup->value,
            'status' => 'open',
        ]);
    }

    public function test_distance_is_measured_from_the_centre_the_request_is_for(): void
    {
        $klangCentre = Centre::create(['name' => 'Klang', 'address' => 'a', 'capacity' => 20,
            'latitude' => self::KLANG[0], 'longitude' => self::KLANG[1]]);

        // A tutor next to the Klang centre, far from the student in PJ.
        $tutor = $this->tutorAt(self::KLANG);

        $assessed = app(TutorMatcher::class)->assessFor($this->centreRequest($klangCentre));
        $row = $assessed->first(fn (array $r) => $r['tutor']->id === $tutor->id);

        // Measured centre-to-tutor, so this is near zero rather than ~19 km.
        $this->assertLessThan(2.0, $row['distance_km']);
    }

    public function test_another_centre_is_never_used_as_the_origin(): void
    {
        // An unrelated centre exists and would previously have been picked.
        Centre::create(['name' => 'PJ Centre', 'address' => 'a', 'capacity' => 20,
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1]]);

        $tutor = $this->tutorAt(self::KLANG);

        // The request names no centre, so there is nowhere to measure from.
        $assessed = app(TutorMatcher::class)->assessFor($this->centreRequest(null));
        $row = $assessed->first(fn (array $r) => $r['tutor']->id === $tutor->id);

        // Not knowing is reported as not knowing, rather than as a real
        // distance to somewhere the lesson is not.
        $this->assertNull($row['distance_km']);
    }

    public function test_candidates_for_a_centre_group_are_measured_from_that_centre(): void
    {
        $klang = Centre::create(['name' => 'Klang', 'address' => 'a', 'capacity' => 20,
            'latitude' => self::KLANG[0], 'longitude' => self::KLANG[1]]);

        $near = $this->tutorAt(self::KLANG);
        $this->tutorAt([2.2, 102.2]);   // far from everything

        $candidates = app(TutorMatcher::class)->candidatesFor($this->centreRequest($klang), studentRadiusKm: 20);

        $this->assertCount(1, $candidates);
        $this->assertSame($near->id, $candidates->first()['tutor']->id);
    }
}
