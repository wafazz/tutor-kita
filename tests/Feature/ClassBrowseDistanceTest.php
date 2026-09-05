<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Enums\GroupPayoutModel;
use App\Models\Centre;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A centre class is only useful if the student can get to it, so browsing is
 * measured from the student's home. Online classes have nowhere to be and are
 * always shown.
 */
class ClassBrowseDistanceTest extends TestCase
{
    use RefreshDatabase;

    private const KL = [3.1390, 101.6869];

    private const PJ = [3.1073, 101.6067];        // ~9 km from KL

    private const SEREMBAN = [2.7297, 101.9381];  // ~53 km from KL

    private User $parent;

    private function tutor(): User
    {
        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);

        return $tutor->fresh();
    }

    private function centreClass(string $name, array $at, ?string $day = 'saturday'): ClassSession
    {
        $centre = Centre::create(['name' => $name, 'address' => 'a', 'capacity' => 20,
            'latitude' => $at[0] ?? null, 'longitude' => $at[1] ?? null]);

        return $this->makeClass(DeliveryMode::CentreGroup, $centre, $day);
    }

    private function onlineClass(): ClassSession
    {
        return $this->makeClass(DeliveryMode::OnlineGroup, null, 'sunday');
    }

    private function makeClass(DeliveryMode $mode, ?Centre $centre, ?string $day): ClassSession
    {
        return ClassSession::create([
            'tutor_id' => $this->tutor()->id,
            'subject_id' => Subject::create(['name' => 'S'.uniqid(), 'category' => 'academic',
                'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true])->id,
            'delivery_mode' => $mode->value, 'centre_id' => $centre?->id,
            'title' => 'C'.uniqid(), 'schedule_day' => $day, 'schedule_time' => '10:00',
            'duration_hours' => 2, 'total_sessions' => 1, 'capacity' => 8,
            'price_per_student' => 30, 'payout_model' => GroupPayoutModel::PerStudent->value,
            'status' => 'open',
        ]);
    }

    private function studentAt(?array $point): Student
    {
        $this->parent ??= User::factory()->parent()->create();

        return Student::create([
            'parent_id' => $this->parent->id, 'name' => 'Kid'.uniqid(), 'age' => 14,
            'latitude' => $point[0] ?? null, 'longitude' => $point[1] ?? null,
        ]);
    }

    public function test_a_distant_centre_class_is_hidden(): void
    {
        $this->studentAt(self::KL);
        $this->centreClass('PJ Centre', self::PJ);
        $this->centreClass('Seremban Centre', self::SEREMBAN);

        $this->actingAs($this->parent)->get('/parent/classes?radius=25')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('classes', 1)
                ->where('classes.0.centre_name', 'PJ Centre')
                ->where('filters.hidden', 1)
            );
    }

    public function test_widening_the_radius_brings_it_back(): void
    {
        $this->studentAt(self::KL);
        $this->centreClass('PJ Centre', self::PJ);
        $this->centreClass('Seremban Centre', self::SEREMBAN);

        $this->actingAs($this->parent)->get('/parent/classes?radius=100')
            ->assertInertia(fn ($page) => $page->has('classes', 2)->where('filters.hidden', 0));
    }

    public function test_online_classes_are_never_filtered_out(): void
    {
        $this->studentAt(self::KL);
        $this->centreClass('Seremban Centre', self::SEREMBAN);
        $this->onlineClass();

        $this->actingAs($this->parent)->get('/parent/classes?radius=5')
            ->assertInertia(fn ($page) => $page
                ->has('classes', 1)
                ->where('classes.0.is_online', true)
            );
    }

    public function test_the_nearest_of_several_children_decides_reach(): void
    {
        $this->studentAt(self::SEREMBAN);
        $this->studentAt(self::KL);   // this one is near PJ
        $this->centreClass('PJ Centre', self::PJ);

        $this->actingAs($this->parent)->get('/parent/classes?radius=25')
            ->assertInertia(fn ($page) => $page->has('classes', 1));
    }

    public function test_a_parent_with_no_address_still_sees_everything(): void
    {
        $this->studentAt(null);
        $this->centreClass('Seremban Centre', self::SEREMBAN);
        $this->onlineClass();

        // Filtering on nothing would look like an empty platform.
        $this->actingAs($this->parent)->get('/parent/classes?radius=5')
            ->assertInertia(fn ($page) => $page
                ->has('classes', 2)
                ->where('filters.hasLocation', false)
            );
    }

    public function test_a_centre_with_no_coordinates_is_shown_but_marked_unknown(): void
    {
        $this->studentAt(self::KL);
        $this->centreClass('Unplaced Centre', [null, null]);

        $this->actingAs($this->parent)->get('/parent/classes?radius=5')
            ->assertInertia(fn ($page) => $page
                ->has('classes', 1)
                ->where('classes.0.distance_km', null)
                ->where('classes.0.distance_known', false)
            );
    }

    public function test_classes_are_ordered_nearest_first(): void
    {
        $this->studentAt(self::KL);
        $this->centreClass('Seremban Centre', self::SEREMBAN);
        $this->centreClass('PJ Centre', self::PJ);

        $this->actingAs($this->parent)->get('/parent/classes?radius=100')
            ->assertInertia(fn ($page) => $page
                ->where('classes.0.centre_name', 'PJ Centre')
                ->where('classes.1.centre_name', 'Seremban Centre')
            );
    }

    public function test_the_reported_distance_is_measured_not_guessed(): void
    {
        $this->studentAt(self::KL);
        $this->centreClass('PJ Centre', self::PJ);

        $this->actingAs($this->parent)->get('/parent/classes')
            ->assertInertia(fn ($page) => $page
                ->where('classes.0.distance_km', fn ($km) => abs((float) $km - 9.0) < 2.0)
            );
    }
}
