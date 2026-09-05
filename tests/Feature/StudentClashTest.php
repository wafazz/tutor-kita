<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Enums\GroupPayoutModel;
use App\Models\Booking;
use App\Models\Centre;
use App\Models\ClassEnrolment;
use App\Models\ClassSession;
use App\Models\Package;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\User;
use App\Support\ClassEnroller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A student can be double-booked exactly as a tutor can. A free tutor is not
 * enough — the student has to be free too.
 */
class StudentClashTest extends TestCase
{
    use RefreshDatabase;

    private const PJ = [3.1073, 101.6067];

    private const KLANG = [3.0449, 101.4455];   // ~19 km away

    private User $parent;

    private Student $student;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parent = User::factory()->parent()->create();
        $this->student = Student::create([
            'parent_id' => $this->parent->id, 'name' => 'Ali', 'age' => 14,
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1],
        ]);
        $this->subject = Subject::create(['name' => 'Maths', 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);
    }

    private function tutor(array $at = self::PJ): User
    {
        $tutor = User::factory()->tutor()->create(['name' => 'Cikgu '.uniqid()]);
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
            'latitude' => $at[0], 'longitude' => $at[1],
        ]);

        return $tutor->fresh();
    }

    private function existingLesson(string $time, float $hours, ?User $tutor = null): Booking
    {
        return Booking::create([
            'tutor_id' => ($tutor ?? $this->tutor())->id, 'parent_id' => $this->parent->id,
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id,
            'schedule_day' => 'saturday', 'schedule_time' => $time, 'duration_hours' => $hours,
            'location_type' => 'home', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'hourly_rate' => 60, 'commission_rate' => 20, 'status' => 'confirmed',
        ]);
    }

    private function openClass(string $time, float $hours, ?Centre $centre = null): ClassSession
    {
        return ClassSession::create([
            'tutor_id' => $this->tutor()->id, 'subject_id' => $this->subject->id,
            'delivery_mode' => $centre ? DeliveryMode::CentreGroup->value : DeliveryMode::OnlineGroup->value,
            'centre_id' => $centre?->id, 'title' => 'Saturday Group',
            'schedule_day' => 'saturday', 'schedule_time' => $time, 'duration_hours' => $hours,
            'total_sessions' => 1, 'capacity' => 8, 'price_per_student' => 30,
            'payout_model' => GroupPayoutModel::PerStudent->value, 'status' => 'open',
        ]);
    }

    public function test_a_student_cannot_join_a_class_that_overlaps_their_own_lesson(): void
    {
        $this->existingLesson('10:00', 2);
        $class = $this->openClass('11:00', 2);

        $this->actingAs($this->parent)
            ->post("/parent/classes/{$class->id}/enrol", ['student_id' => $this->student->id])
            ->assertSessionHas('error');

        $this->assertSame(0, ClassEnrolment::count());
    }

    public function test_the_message_names_the_student_and_the_clashing_commitment(): void
    {
        $this->existingLesson('10:00', 2);
        $class = $this->openClass('11:00', 2);

        $this->actingAs($this->parent)
            ->post("/parent/classes/{$class->id}/enrol", ['student_id' => $this->student->id]);

        $error = session('error');

        $this->assertStringContainsString('Ali', $error);
        $this->assertStringContainsString('Maths lesson', $error);
        // A student attends a lesson; they do not teach it.
        $this->assertStringContainsString('already has', $error);
        $this->assertStringNotContainsString('teaches', $error);
    }

    public function test_a_student_cannot_join_two_classes_at_the_same_time(): void
    {
        $first = $this->openClass('10:00', 2);
        $second = $this->openClass('11:00', 2);

        (new ClassEnroller)->enrol($first, $this->student);

        $this->actingAs($this->parent)
            ->post("/parent/classes/{$second->id}/enrol", ['student_id' => $this->student->id])
            ->assertSessionHas('error');

        $this->assertSame(1, ClassEnrolment::count());
    }

    public function test_a_student_can_join_a_class_that_does_not_clash(): void
    {
        $this->existingLesson('10:00', 2);
        $class = $this->openClass('14:00', 2);

        $this->actingAs($this->parent)
            ->post("/parent/classes/{$class->id}/enrol", ['student_id' => $this->student->id])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ClassEnrolment::count());
    }

    public function test_travel_between_a_lesson_and_a_centre_counts_for_the_student_too(): void
    {
        $centre = Centre::create(['name' => 'Klang Centre', 'address' => 'a', 'capacity' => 20,
            'latitude' => self::KLANG[0], 'longitude' => self::KLANG[1]]);

        // Lesson at home in PJ ends at 12:00; class starts at 12:00 in Klang.
        $this->existingLesson('10:00', 2);
        $class = $this->openClass('12:00', 2, $centre);

        $this->actingAs($this->parent)
            ->post("/parent/classes/{$class->id}/enrol", ['student_id' => $this->student->id])
            ->assertSessionHas('error');

        $this->assertStringContainsString('needs about', session('error'));
    }

    public function test_an_online_class_after_a_lesson_needs_no_travel(): void
    {
        $this->existingLesson('10:00', 2);
        $class = $this->openClass('12:00', 2);

        $this->actingAs($this->parent)
            ->post("/parent/classes/{$class->id}/enrol", ['student_id' => $this->student->id])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ClassEnrolment::count());
    }

    public function test_another_students_lesson_does_not_block_this_one(): void
    {
        $sibling = Student::create(['parent_id' => $this->parent->id, 'name' => 'Siti', 'age' => 12,
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1]]);

        Booking::create([
            'tutor_id' => $this->tutor()->id, 'parent_id' => $this->parent->id,
            'student_id' => $sibling->id, 'subject_id' => $this->subject->id,
            'schedule_day' => 'saturday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'location_type' => 'home', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'hourly_rate' => 60, 'commission_rate' => 20, 'status' => 'confirmed',
        ]);

        $class = $this->openClass('10:00', 2);

        $this->actingAs($this->parent)
            ->post("/parent/classes/{$class->id}/enrol", ['student_id' => $this->student->id])
            ->assertSessionHasNoErrors();
    }

    public function test_assigning_a_tutor_when_the_student_is_busy_is_refused(): void
    {
        // The tutor is free; the student is not.
        $this->existingLesson('10:00', 2);
        $freeTutor = $this->tutor();

        $package = Package::create(['name' => 'P', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1]);

        $request = TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $this->student->id,
            'subject_id' => $this->subject->id, 'package_id' => $package->id,
            'preferred_area' => 'PJ', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'schedule_day' => 'saturday', 'schedule_time' => '11:00', 'duration_hours' => 2,
            'status' => 'open',
        ]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/requests/{$request->id}/match", ['matched_tutor_id' => $freeTutor->id]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Ali', session('error'));
        $this->assertSame('open', $request->fresh()->status);
    }

    public function test_a_cancelled_lesson_does_not_block_the_student(): void
    {
        $this->existingLesson('10:00', 2)->update(['status' => 'cancelled']);
        $class = $this->openClass('10:00', 2);

        $this->actingAs($this->parent)
            ->post("/parent/classes/{$class->id}/enrol", ['student_id' => $this->student->id])
            ->assertSessionHasNoErrors();
    }
}
