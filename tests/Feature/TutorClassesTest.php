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

class TutorClassesTest extends TestCase
{
    use RefreshDatabase;

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

    private function classFor(User $tutor, array $overrides = []): ClassSession
    {
        return ClassSession::create(array_merge([
            'tutor_id' => $tutor->id,
            'subject_id' => Subject::create(['name' => 'S'.uniqid(), 'category' => 'academic',
                'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true])->id,
            'delivery_mode' => DeliveryMode::OnlineGroup->value, 'title' => 'Saturday Maths',
            'schedule_day' => 'saturday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'total_sessions' => 1, 'capacity' => 8, 'price_per_student' => 30,
            'payout_model' => GroupPayoutModel::PerStudent->value, 'status' => 'open',
        ], $overrides));
    }

    public function test_a_tutor_sees_their_own_classes_and_what_they_earn(): void
    {
        $tutor = $this->tutor();
        $class = $this->classFor($tutor);

        $parent = User::factory()->parent()->create();
        $enroller = new ClassEnroller;

        foreach (range(1, 3) as $i) {
            $enroller->enrol($class, Student::create([
                'parent_id' => $parent->id, 'name' => "Kid {$i}", 'age' => 14,
            ]));
        }

        $this->actingAs($tutor)->get('/tutor/classes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tutor/Classes/Index')
                ->has('classes', 1)
                ->where('classes.0.seats_taken', 3)
                // 3 x RM30 = RM90, tutor on 20% keeps RM72.
                ->where('classes.0.earns', fn ($earns) => abs((float) $earns - 72.0) < 0.01)
                ->has('classes.0.students', 3)
            );
    }

    public function test_a_tutor_does_not_see_another_tutors_classes(): void
    {
        $mine = $this->tutor();
        $theirs = $this->tutor();
        $this->classFor($theirs);

        $this->actingAs($mine)->get('/tutor/classes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('classes', 0));
    }

    public function test_cancelled_classes_are_hidden(): void
    {
        $tutor = $this->tutor();
        $this->classFor($tutor, ['status' => 'cancelled']);

        $this->actingAs($tutor)->get('/tutor/classes')
            ->assertInertia(fn ($page) => $page->has('classes', 0));
    }

    public function test_an_unpaid_seat_is_shown_as_unconfirmed(): void
    {
        $tutor = $this->tutor();
        $class = $this->classFor($tutor);
        $parent = User::factory()->parent()->create();

        (new ClassEnroller)->enrol($class, Student::create([
            'parent_id' => $parent->id, 'name' => 'Unpaid Kid', 'age' => 14,
        ]));

        $this->actingAs($tutor)->get('/tutor/classes')
            ->assertInertia(fn ($page) => $page->where('classes.0.students.0.confirmed', false));
    }

    public function test_a_fixed_arrangement_reports_its_own_label(): void
    {
        $tutor = $this->tutor();
        $this->classFor($tutor, [
            'payout_model' => GroupPayoutModel::Flat->value, 'payout_base' => 80,
        ]);

        $this->actingAs($tutor)->get('/tutor/classes')
            ->assertInertia(fn ($page) => $page
                ->where('classes.0.payout_model', 'flat')
                ->where('classes.0.payout_label', 'Flat rate per class')
            );
    }

    public function test_a_parent_cannot_reach_the_tutor_view(): void
    {
        $this->actingAs(User::factory()->parent()->create())
            ->get('/tutor/classes')
            ->assertForbidden();
    }
}
