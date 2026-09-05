<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Enums\GroupPayoutModel;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use App\Support\ClassEnroller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laravel's decimal cast serialises as a string, a plain float as a number, and
 * the frontend types have to say which. Getting it wrong is not caught by the
 * compiler — it produced three blank pages, because .toFixed() on a string
 * throws and React unmounts the whole screen.
 *
 * Model attributes are typed as strings on the frontend. These pin the handful
 * of values that genuinely are numbers, so a controller quietly returning a
 * model attribute instead of a computed one is caught here rather than by a
 * parent finding an empty page.
 */
class SerialisedNumberTypesTest extends TestCase
{
    use RefreshDatabase;

    private function filledClass(): ClassSession
    {
        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);

        $class = ClassSession::create([
            'tutor_id' => $tutor->id,
            'subject_id' => Subject::create(['name' => 'S'.uniqid(), 'category' => 'academic',
                'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true])->id,
            'delivery_mode' => DeliveryMode::OnlineGroup->value, 'title' => 'C',
            'schedule_day' => 'saturday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'total_sessions' => 1, 'capacity' => 8, 'price_per_student' => 30,
            'payout_model' => GroupPayoutModel::PerStudent->value, 'status' => 'open',
        ]);

        $parent = User::factory()->parent()->create();
        (new ClassEnroller)->enrol($class, Student::create([
            'parent_id' => $parent->id, 'name' => 'Kid', 'age' => 14,
        ]));

        return $class->fresh();
    }

    public function test_the_admin_class_economics_are_numbers_not_strings(): void
    {
        $this->filledClass();

        $this->actingAs(User::factory()->admin()->create())->get('/admin/classes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                // These are called with .toFixed() directly, which throws on a string.
                ->where('classes.0.revenue', fn ($v) => is_int($v) || is_float($v))
                ->where('classes.0.tutor_payout', fn ($v) => is_int($v) || is_float($v))
                ->where('classes.0.platform_share', fn ($v) => is_int($v) || is_float($v))
                // ...while a model attribute on the same payload is a string.
                ->where('classes.0.price_per_student', fn ($v) => is_string($v))
            );
    }

    public function test_the_parent_class_price_is_a_number(): void
    {
        $class = $this->filledClass();
        $parent = User::factory()->parent()->create();

        $this->actingAs($parent)->get('/parent/classes')
            ->assertInertia(fn ($page) => $page
                ->where('classes.0.price', fn ($v) => is_int($v) || is_float($v)));

        $this->actingAs($parent)->get("/parent/classes/{$class->id}")
            ->assertInertia(fn ($page) => $page
                ->where('classSession.price', fn ($v) => is_int($v) || is_float($v)));
    }

    public function test_the_tutor_class_earnings_are_a_number(): void
    {
        $class = $this->filledClass();

        $this->actingAs($class->tutor)->get('/tutor/classes')
            ->assertInertia(fn ($page) => $page
                ->where('classes.0.earns', fn ($v) => is_int($v) || is_float($v))
                // duration_hours is a decimal cast, so it stays a string.
                ->where('classes.0.duration_hours', fn ($v) => is_string($v))
            );
    }

    public function test_a_decimal_model_attribute_serialises_as_a_string(): void
    {
        $class = $this->filledClass();
        $json = json_decode($class->toJson(), true);

        // The reason every money field is typed as a string on the frontend.
        $this->assertIsString($json['price_per_student']);
        $this->assertIsString($json['duration_hours']);
    }
}
