<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Enums\GroupPayoutModel;
use App\Models\Booking;
use App\Models\ClassEnrolment;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassEnrolmentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function openClass(array $overrides = []): ClassSession
    {
        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);

        return ClassSession::create(array_merge([
            'tutor_id' => $tutor->id,
            'subject_id' => Subject::create([
                'name' => 'S'.uniqid(), 'category' => 'academic',
                'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true,
            ])->id,
            'delivery_mode' => DeliveryMode::OnlineGroup->value,
            'title' => 'Saturday Maths', 'schedule_day' => 'saturday', 'schedule_time' => '10:00',
            'duration_hours' => 1.5, 'total_sessions' => 2, 'capacity' => 4,
            'price_per_student' => 30, 'payout_model' => GroupPayoutModel::PerStudent->value,
            'status' => 'open',
        ], $overrides));
    }

    private function parentWithStudent(): array
    {
        $parent = User::factory()->parent()->create();
        $student = Student::create(['parent_id' => $parent->id, 'name' => 'Kid', 'age' => 14]);

        return [$parent, $student];
    }

    public function test_a_parent_can_browse_open_classes(): void
    {
        $this->openClass();
        $this->openClass(['status' => 'draft']);
        [$parent] = $this->parentWithStudent();

        $this->actingAs($parent)->get('/parent/classes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Parent/Classes/Index')->has('classes', 1));
    }

    public function test_enrolling_holds_a_seat_and_sends_the_parent_to_pay(): void
    {
        $class = $this->openClass();
        [$parent, $student] = $this->parentWithStudent();

        $response = $this->actingAs($parent)
            ->post("/parent/classes/{$class->id}/enrol", ['student_id' => $student->id]);

        $enrolment = ClassEnrolment::sole();

        $this->assertSame('pending', $enrolment->status);
        $response->assertRedirect("/parent/payments/{$enrolment->payment_id}");

        // 2 sessions at RM30.
        $this->assertEquals(60.00, (float) $enrolment->payment->amount);
    }

    public function test_paying_confirms_the_seat_without_inventing_a_booking(): void
    {
        $class = $this->openClass();
        [$parent, $student] = $this->parentWithStudent();

        $this->actingAs($parent)->post("/parent/classes/{$class->id}/enrol", ['student_id' => $student->id]);
        $enrolment = ClassEnrolment::sole();

        $bookingsBefore = Booking::count();

        // No gateway keys configured, so this takes the manual path.
        $this->actingAs($parent)->post("/parent/payments/{$enrolment->payment_id}/pay")
            ->assertRedirect("/parent/classes/{$class->id}");

        $this->assertSame('active', $enrolment->fresh()->status);
        $this->assertSame('success', Payment::sole()->status);

        // The booking already existed from enrolment; paying must not add another.
        $this->assertSame($bookingsBefore, Booking::count());
    }

    public function test_a_parent_cannot_enrol_someone_elses_student(): void
    {
        $class = $this->openClass();
        [$parent] = $this->parentWithStudent();
        [, $otherStudent] = $this->parentWithStudent();

        $this->actingAs($parent)
            ->post("/parent/classes/{$class->id}/enrol", ['student_id' => $otherStudent->id])
            ->assertNotFound();

        $this->assertSame(0, ClassEnrolment::count());
    }

    public function test_a_class_that_is_not_open_cannot_be_enrolled_in(): void
    {
        $class = $this->openClass(['status' => 'draft']);
        [$parent, $student] = $this->parentWithStudent();

        $this->actingAs($parent)
            ->post("/parent/classes/{$class->id}/enrol", ['student_id' => $student->id])
            ->assertForbidden();
    }

    public function test_a_full_class_reports_it_rather_than_failing(): void
    {
        $class = $this->openClass(['capacity' => 1]);
        [$parentA, $studentA] = $this->parentWithStudent();
        [$parentB, $studentB] = $this->parentWithStudent();

        $this->actingAs($parentA)->post("/parent/classes/{$class->id}/enrol", ['student_id' => $studentA->id]);

        $this->actingAs($parentB)
            ->post("/parent/classes/{$class->id}/enrol", ['student_id' => $studentB->id])
            ->assertSessionHas('error');

        $this->assertSame(1, ClassEnrolment::count());
    }

    public function test_an_admin_can_create_a_class_and_sees_its_economics(): void
    {
        $class = $this->openClass();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/classes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Classes/Index')
                ->has('classes', 1)
                ->where('classes.0.is_underwater', false)
            );

        $this->actingAs($admin)->post('/admin/classes', [
            'tutor_id' => $class->tutor_id, 'subject_id' => $class->subject_id,
            'delivery_mode' => 'online_group', 'duration_hours' => 1.5, 'total_sessions' => 1,
            'capacity' => 6, 'price_per_student' => 25,
            'payout_model' => 'flat_plus_head', 'payout_base' => 60,
            'payout_per_head' => 10, 'payout_head_threshold' => 4, 'status' => 'open',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, ClassSession::count());
    }

    public function test_a_fixed_payout_class_requires_its_base_amount(): void
    {
        $class = $this->openClass();

        $this->actingAs(User::factory()->admin()->create())->post('/admin/classes', [
            'tutor_id' => $class->tutor_id, 'subject_id' => $class->subject_id,
            'delivery_mode' => 'online_group', 'duration_hours' => 1.5, 'total_sessions' => 1,
            'capacity' => 6, 'price_per_student' => 25,
            'payout_model' => 'flat', 'status' => 'open',
        ])->assertSessionHasErrors('payout_base');
    }

    public function test_a_class_with_students_cannot_be_deleted(): void
    {
        $class = $this->openClass();
        [$parent, $student] = $this->parentWithStudent();
        $this->actingAs($parent)->post("/parent/classes/{$class->id}/enrol", ['student_id' => $student->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->delete("/admin/classes/{$class->id}")
            ->assertSessionHas('error');

        $this->assertSame(1, ClassSession::count());
    }
}
