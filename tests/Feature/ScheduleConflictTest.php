<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Enums\GroupPayoutModel;
use App\Models\Booking;
use App\Models\Centre;
use App\Models\ClassSession;
use App\Models\Package;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\User;
use App\Support\ScheduleConflictDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A tutor cannot be in two places at once — nor in two places so far apart
 * that they could not get between them.
 */
class ScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    private const PJ = [3.1073, 101.6067];

    private const KLANG = [3.0449, 101.4455];    // ~19 km from PJ

    private const NEXT_DOOR = [3.1074, 101.6068];

    private User $tutor;

    private Subject $subject;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->tutor()->create(['name' => 'Cikgu A']);
        TutorProfile::create([
            'user_id' => $this->tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1],
        ]);

        $this->subject = Subject::create(['name' => 'Maths', 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);

        $this->parent = User::factory()->parent()->create();
    }

    private function existingBooking(string $time, float $hours, array $at, DeliveryMode $mode = DeliveryMode::HomeStudent): Booking
    {
        $student = Student::create([
            'parent_id' => $this->parent->id, 'name' => 'Ali', 'age' => 14,
            'latitude' => $at[0] ?? null, 'longitude' => $at[1] ?? null,
        ]);

        return Booking::create([
            'tutor_id' => $this->tutor->id, 'parent_id' => $this->parent->id,
            'student_id' => $student->id, 'subject_id' => $this->subject->id,
            'schedule_day' => 'saturday', 'schedule_time' => $time, 'duration_hours' => $hours,
            'location_type' => 'home', 'delivery_mode' => $mode->value,
            'hourly_rate' => 60, 'commission_rate' => 20, 'status' => 'confirmed',
        ]);
    }

    private function check(string $time, float $hours, array $at, DeliveryMode $mode = DeliveryMode::HomeStudent)
    {
        return app(ScheduleConflictDetector::class)->check(
            tutorId: $this->tutor->id, day: 'saturday', time: $time,
            durationHours: $hours, mode: $mode,
            latitude: $at[0] ?? null, longitude: $at[1] ?? null,
        );
    }

    public function test_a_directly_overlapping_lesson_is_a_clash(): void
    {
        $this->existingBooking('10:00', 2, self::PJ);

        $conflicts = $this->check('11:00', 2, self::PJ);

        $this->assertCount(1, $conflicts);
        $this->assertSame('overlap', $conflicts->first()->kind);
        $this->assertStringContainsString('already teaches', $conflicts->first()->message('Cikgu A'));
    }

    public function test_an_identical_slot_is_a_clash(): void
    {
        $this->existingBooking('10:00', 2, self::PJ);

        $this->assertCount(1, $this->check('10:00', 2, self::PJ));
    }

    public function test_a_lesson_that_finishes_before_the_next_begins_nearby_is_fine(): void
    {
        $this->existingBooking('10:00', 2, self::PJ);

        // Next door, so travel is negligible.
        $this->assertCount(0, $this->check('12:00', 2, self::NEXT_DOOR));
    }

    public function test_back_to_back_across_town_is_a_clash_even_though_the_times_do_not_overlap(): void
    {
        $this->existingBooking('10:00', 2, self::PJ);

        $conflicts = $this->check('12:00', 2, self::KLANG);

        $this->assertCount(1, $conflicts);
        $this->assertSame('travel', $conflicts->first()->kind);
        $this->assertGreaterThanOrEqual(30, $conflicts->first()->travelMinutes);
        $this->assertStringContainsString('needs about', $conflicts->first()->message('Cikgu A'));
    }

    public function test_enough_of_a_gap_to_cross_town_is_allowed(): void
    {
        $this->existingBooking('10:00', 2, self::PJ);

        // Two hours later is ample for a 19 km hop.
        $this->assertCount(0, $this->check('14:00', 2, self::KLANG));
    }

    public function test_online_lessons_never_clash_on_travel(): void
    {
        $this->existingBooking('10:00', 2, self::PJ, DeliveryMode::OnlineSolo);

        // Immediately after, far away, but neither requires being anywhere.
        $this->assertCount(0, $this->check('12:00', 2, self::KLANG, DeliveryMode::OnlineSolo));
    }

    public function test_online_lessons_still_clash_when_the_times_overlap(): void
    {
        $this->existingBooking('10:00', 2, self::PJ, DeliveryMode::OnlineSolo);

        $this->assertCount(1, $this->check('11:00', 1, self::KLANG, DeliveryMode::OnlineSolo));
    }

    public function test_a_group_class_clashes_with_a_one_to_one(): void
    {
        ClassSession::create([
            'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'delivery_mode' => DeliveryMode::OnlineGroup->value, 'title' => 'Saturday Maths',
            'schedule_day' => 'saturday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'total_sessions' => 1, 'capacity' => 8, 'price_per_student' => 30,
            'payout_model' => GroupPayoutModel::PerStudent->value, 'status' => 'open',
        ]);

        $conflicts = $this->check('11:00', 2, self::PJ);

        $this->assertCount(1, $conflicts);
        $this->assertStringContainsString('Saturday Maths', $conflicts->first()->message('Cikgu A'));
    }

    public function test_a_cancelled_commitment_does_not_block_the_slot(): void
    {
        $this->existingBooking('10:00', 2, self::PJ)->update(['status' => 'cancelled']);

        $this->assertCount(0, $this->check('10:00', 2, self::PJ));
    }

    public function test_a_different_day_never_clashes(): void
    {
        $this->existingBooking('10:00', 2, self::PJ);

        $conflicts = app(ScheduleConflictDetector::class)->check(
            tutorId: $this->tutor->id, day: 'sunday', time: '10:00',
            durationHours: 2, mode: DeliveryMode::HomeStudent,
            latitude: self::PJ[0], longitude: self::PJ[1],
        );

        $this->assertCount(0, $conflicts);
    }

    public function test_an_unscheduled_request_is_not_treated_as_a_clash(): void
    {
        $this->existingBooking('10:00', 2, self::PJ);

        $this->assertCount(0, $this->check('', 2, self::PJ));
    }

    // ---- enforced where tutors are actually assigned ----

    public function test_assigning_a_tutor_who_is_already_busy_is_refused(): void
    {
        $this->existingBooking('10:00', 2, self::PJ);

        $student = Student::create(['parent_id' => $this->parent->id, 'name' => 'Siti', 'age' => 15,
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1]]);
        $package = Package::create(['name' => 'P', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1]);

        $request = TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $student->id,
            'subject_id' => $this->subject->id, 'package_id' => $package->id,
            'preferred_area' => 'PJ', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'schedule_day' => 'saturday', 'schedule_time' => '11:00', 'duration_hours' => 2,
            'status' => 'open',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/requests/{$request->id}/match", ['matched_tutor_id' => $this->tutor->id])
            ->assertSessionHas('error');

        $this->assertSame('open', $request->fresh()->status);
        $this->assertSame(1, Booking::count());
    }

    public function test_creating_a_class_that_clashes_is_refused(): void
    {
        $this->existingBooking('10:00', 2, self::PJ);

        $this->actingAs(User::factory()->admin()->create())->post('/admin/classes', [
            'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'delivery_mode' => 'online_group', 'schedule_day' => 'saturday', 'schedule_time' => '11:00',
            'duration_hours' => 2, 'total_sessions' => 1, 'capacity' => 8,
            'price_per_student' => 30, 'payout_model' => 'per_student', 'status' => 'open',
        ])->assertSessionHas('error');

        $this->assertSame(0, ClassSession::count());
    }

    public function test_editing_a_class_does_not_clash_with_itself(): void
    {
        $centre = Centre::create(['name' => 'PJ Centre', 'address' => 'a', 'capacity' => 20,
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1]]);

        $class = ClassSession::create([
            'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'delivery_mode' => DeliveryMode::CentreGroup->value, 'centre_id' => $centre->id,
            'title' => 'Saturday Maths', 'schedule_day' => 'saturday', 'schedule_time' => '10:00',
            'duration_hours' => 2, 'total_sessions' => 1, 'capacity' => 8,
            'price_per_student' => 30, 'payout_model' => GroupPayoutModel::PerStudent->value, 'status' => 'open',
        ]);

        $this->actingAs(User::factory()->admin()->create())->put("/admin/classes/{$class->id}", [
            'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'delivery_mode' => 'centre_group', 'centre_id' => $centre->id,
            'title' => 'Saturday Maths (renamed)', 'schedule_day' => 'saturday', 'schedule_time' => '10:00',
            'duration_hours' => 2, 'total_sessions' => 1, 'capacity' => 10,
            'price_per_student' => 35, 'payout_model' => 'per_student', 'status' => 'open',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Saturday Maths (renamed)', $class->fresh()->title);
    }

    public function test_two_classes_at_the_same_centre_can_run_back_to_back(): void
    {
        $centre = Centre::create(['name' => 'PJ Centre', 'address' => 'a', 'capacity' => 20,
            'latitude' => self::PJ[0], 'longitude' => self::PJ[1]]);

        ClassSession::create([
            'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'delivery_mode' => DeliveryMode::CentreGroup->value, 'centre_id' => $centre->id,
            'title' => 'Morning', 'schedule_day' => 'saturday', 'schedule_time' => '10:00',
            'duration_hours' => 2, 'total_sessions' => 1, 'capacity' => 8,
            'price_per_student' => 30, 'payout_model' => GroupPayoutModel::PerStudent->value, 'status' => 'open',
        ]);

        // Same room, so no travel is needed at all.
        $this->actingAs(User::factory()->admin()->create())->post('/admin/classes', [
            'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'delivery_mode' => 'centre_group', 'centre_id' => $centre->id, 'title' => 'Afternoon',
            'schedule_day' => 'saturday', 'schedule_time' => '12:00', 'duration_hours' => 2,
            'total_sessions' => 1, 'capacity' => 8, 'price_per_student' => 30,
            'payout_model' => 'per_student', 'status' => 'open',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, ClassSession::count());
    }
}
