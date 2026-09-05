<?php

namespace Tests\Feature;

use App\Models\Postcode;
use App\Models\Setting;
use App\Models\Student;
use App\Models\TutorProfile;
use App\Models\User;
use App\Support\Geocoding\GeocoderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * People can place themselves, either by letting the browser report where they
 * are or by typing the coordinates. That is what makes distance matching work
 * without a paid geocoding service.
 */
class SelfSetCoordinatesTest extends TestCase
{
    use RefreshDatabase;

    private const PJ = [3.1073, 101.6067];

    private function tutorWithProfile(): User
    {
        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);

        return $tutor->fresh();
    }

    public function test_a_parent_can_place_a_student(): void
    {
        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->post('/parent/students', [
            'name' => 'Ali', 'age' => 14,
            'address' => '1 Jalan Test', 'postcode' => '46000',
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1],
        ])->assertSessionHasNoErrors();

        $student = Student::sole();

        $this->assertTrue($student->hasCoordinates());
        $this->assertEqualsWithDelta(self::PJ[0], $student->latitude, 0.0001);
    }

    public function test_a_tutor_can_place_themselves(): void
    {
        $tutor = $this->tutorWithProfile();

        $this->actingAs($tutor)->put('/tutor/profile', [
            'address' => '2 Jalan Test', 'postcode' => '47300', 'travel_radius_km' => 15,
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($tutor->tutorProfile->fresh()->hasCoordinates());
    }

    public function test_a_position_someone_set_is_never_replaced_by_geocoding(): void
    {
        // Geocoding would resolve this postcode to the middle of the area.
        Setting::set('geocoding_driver', 'postcode');
        Postcode::create([
            'postcode' => '46000', 'city' => 'PJ', 'state' => 'Selangor',
            'latitude' => 3.9999, 'longitude' => 101.9999,
        ]);

        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->post('/parent/students', [
            'name' => 'Ali', 'age' => 14,
            'address' => '1 Jalan Test', 'postcode' => '46000',
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1],
        ]);

        // Their own pin is more precise than a postcode centroid.
        $this->assertEqualsWithDelta(self::PJ[0], Student::sole()->latitude, 0.0001);
    }

    public function test_geocoding_still_applies_when_no_position_was_given(): void
    {
        Setting::set('geocoding_driver', 'postcode');
        Postcode::create([
            'postcode' => '46000', 'city' => 'PJ', 'state' => 'Selangor',
            'latitude' => 3.1073, 'longitude' => 101.6067,
        ]);

        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->post('/parent/students', [
            'name' => 'Ali', 'age' => 14, 'address' => '1 Jalan Test', 'postcode' => '46000',
        ]);

        $this->assertTrue(Student::sole()->hasCoordinates());
    }

    public function test_an_impossible_coordinate_is_rejected(): void
    {
        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->from('/parent/students/create')->post('/parent/students', [
            'name' => 'Ali', 'age' => 14, 'latitude' => 999, 'longitude' => 101.6,
        ])->assertSessionHasErrors('latitude');

        $this->assertSame(0, Student::count());
    }

    public function test_a_position_can_be_cleared(): void
    {
        $parent = User::factory()->parent()->create();
        $student = Student::create([
            'parent_id' => $parent->id, 'name' => 'Ali', 'age' => 14,
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1],
        ]);

        $this->actingAs($parent)->put("/parent/students/{$student->id}", [
            'name' => 'Ali', 'age' => 14, 'latitude' => null, 'longitude' => null,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($student->fresh()->hasCoordinates());
    }

    public function test_a_placed_student_becomes_reachable_by_distance(): void
    {
        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->post('/parent/students', [
            'name' => 'Ali', 'age' => 14, 'latitude' => self::PJ[0], 'longitude' => self::PJ[1],
        ]);

        // The whole point: with no geocoding service configured, a self-set
        // position is what puts someone on the map.
        $this->assertSame('manual', app(GeocoderManager::class)->name());
        $this->assertTrue(Student::sole()->hasCoordinates());
    }
}
