<?php

namespace Tests\Feature;

use App\Models\Centre;
use App\Models\Postcode;
use App\Models\Setting;
use App\Models\Student;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentreAdminTest extends TestCase
{
    use RefreshDatabase;

    private function tutorWithProfile(): User
    {
        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => ['Maths'], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);

        return $tutor->fresh();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_an_admin_can_create_a_platform_centre(): void
    {
        $this->actingAs($this->admin())->post('/admin/centres', [
            'name' => 'PJ Centre', 'address' => '1 Jalan A', 'area' => 'PJ',
            'state' => 'Selangor', 'postcode' => '46000', 'capacity' => 20, 'is_active' => true,
        ])->assertSessionHasNoErrors();

        $centre = Centre::sole();

        $this->assertSame('PJ Centre', $centre->name);
        $this->assertTrue($centre->isPlatformOwned());
    }

    public function test_a_centre_can_belong_to_a_tutor_instead(): void
    {
        $tutor = User::factory()->tutor()->create();

        $this->actingAs($this->admin())->post('/admin/centres', [
            'name' => 'Home studio', 'address' => '2 Jalan B',
            'capacity' => 6, 'is_active' => true, 'owner_user_id' => $tutor->id,
        ])->assertSessionHasNoErrors();

        $this->assertFalse(Centre::sole()->isPlatformOwned());
    }

    public function test_coordinates_may_be_entered_directly_when_geocoding_is_manual(): void
    {
        $this->actingAs($this->admin())->post('/admin/centres', [
            'name' => 'Pinned', 'address' => '3 Jalan C', 'capacity' => 10,
            'is_active' => true, 'latitude' => 3.139, 'longitude' => 101.6869,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Centre::sole()->hasCoordinates());
    }

    public function test_saving_with_an_unresolvable_address_still_succeeds_but_says_it_is_unplaced(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/centres', [
            'name' => 'Nowhere', 'address' => 'unknown', 'capacity' => 10, 'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertFalse(Centre::sole()->hasCoordinates());
        $this->assertStringContainsString('no map position', session('success'));
    }

    public function test_a_centre_is_geocoded_on_save_when_a_driver_is_configured(): void
    {
        Setting::set('geocoding_driver', 'postcode');
        Postcode::create(['postcode' => '46000', 'city' => 'Test City', 'state' => 'Selangor', 'latitude' => 3.1073, 'longitude' => 101.6067]);

        $this->actingAs($this->admin())->post('/admin/centres', [
            'name' => 'Auto', 'address' => '4 Jalan D', 'postcode' => '46000',
            'capacity' => 10, 'is_active' => true,
        ])->assertSessionHasNoErrors();

        $centre = Centre::sole();

        $this->assertTrue($centre->hasCoordinates());
        $this->assertEqualsWithDelta(3.1073, $centre->latitude, 0.001);
        $this->assertNotNull($centre->geocoded_at);
    }

    public function test_the_index_flags_centres_with_no_map_position(): void
    {
        Centre::create(['name' => 'Placed', 'address' => 'a', 'capacity' => 5, 'latitude' => 3.1, 'longitude' => 101.6]);
        Centre::create(['name' => 'Unplaced', 'address' => 'b', 'capacity' => 5]);

        $this->actingAs($this->admin())->get('/admin/centres')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Centres/Index')
                ->has('centres', 2)
                ->where('geocodingDriver', 'manual')
            );
    }

    public function test_a_parent_can_save_a_student_address_and_it_is_geocoded(): void
    {
        Setting::set('geocoding_driver', 'postcode');
        Postcode::create(['postcode' => '46000', 'city' => 'Test City', 'state' => 'Selangor', 'latitude' => 3.1073, 'longitude' => 101.6067]);

        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->post('/parent/students', [
            'name' => 'Kid', 'age' => 14,
            'address' => '5 Jalan E', 'area' => 'PJ', 'state' => 'Selangor', 'postcode' => '46000',
        ])->assertSessionHasNoErrors();

        $student = Student::sole();

        $this->assertSame('5 Jalan E', $student->address);
        $this->assertTrue($student->hasCoordinates());
    }

    public function test_a_tutor_can_save_their_address_and_travel_radius(): void
    {
        Setting::set('geocoding_driver', 'postcode');
        Postcode::create(['postcode' => '47300', 'city' => 'Test City', 'state' => 'Selangor', 'latitude' => 3.1073, 'longitude' => 101.6067]);

        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel', 'verification_status' => 'verified',
        ]);

        $this->actingAs($tutor)->put('/tutor/profile', [
            'address' => '6 Jalan F', 'postcode' => '47300', 'travel_radius_km' => 15,
        ])->assertSessionHasNoErrors();

        $profile = $tutor->tutorProfile->fresh();

        $this->assertSame(15, $profile->travel_radius_km);
        $this->assertTrue($profile->hasCoordinates());
    }

    public function test_a_travel_radius_beyond_the_allowed_range_is_rejected(): void
    {
        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel', 'verification_status' => 'verified',
        ]);

        $this->actingAs($tutor)->from('/tutor/profile')
            ->put('/tutor/profile', ['travel_radius_km' => 5000])
            ->assertSessionHasErrors('travel_radius_km');
    }

    public function test_a_tutor_can_clear_optional_profile_fields_without_a_500(): void
    {
        $tutor = $this->tutorWithProfile();

        // These columns are NOT NULL while the form treats them as optional,
        // so an emptied field used to lose the whole save.
        $this->actingAs($tutor)->put('/tutor/profile', [
            'location_area' => '', 'location_state' => '', 'hourly_rate' => '',
            'bio' => 'Still here',
        ])->assertSessionHasNoErrors();

        $profile = $tutor->tutorProfile->fresh();

        $this->assertSame('Still here', $profile->bio);
        $this->assertSame('', $profile->location_area);
        $this->assertEquals(0, (float) $profile->hourly_rate);
    }
}
