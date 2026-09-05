<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Enums\GroupPayoutModel;
use App\Models\Booking;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use App\Support\ClassEnroller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Commercial terms agreed at the time have to stay agreed. Renegotiating a
 * tutor's commission must not reach back into work that was already sold.
 */
class HistoricalTermsTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $this->tutor->id, 'subjects' => ['Maths'], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);

        $this->parent = User::factory()->parent()->create();
    }

    private function openClass(): ClassSession
    {
        $subject = Subject::create(['name' => 'Maths', 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);

        return ClassSession::create([
            'tutor_id' => $this->tutor->id, 'subject_id' => $subject->id,
            'delivery_mode' => DeliveryMode::OnlineGroup->value, 'title' => 'C',
            'schedule_day' => 'saturday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'total_sessions' => 1, 'capacity' => 8, 'price_per_student' => 100,
            'payout_model' => GroupPayoutModel::PerStudent->value, 'status' => 'open',
            'commission_rate' => 20,
        ]);
    }

    private function enrol(ClassSession $class, string $name): void
    {
        (new ClassEnroller)->enrol($class, Student::create([
            'parent_id' => $this->parent->id, 'name' => $name, 'age' => 14,
        ]));
    }

    public function test_renegotiating_commission_does_not_reprice_a_class_already_sold(): void
    {
        $class = $this->openClass();
        $this->enrol($class, 'A');

        $first = Booking::first();
        $this->assertEqualsWithDelta(80.00, (float) $first->tutor_payout, 0.01);

        // The platform renegotiates the tutor's commission upward.
        $this->tutor->tutorProfile->update(['commission_rate' => 50]);

        // Another student joins, which recalculates the class's shares.
        $this->enrol($class->fresh(), 'B');

        // The first student's payout must be untouched: RM80, not RM50.
        $this->assertEqualsWithDelta(80.00, (float) $first->fresh()->tutor_payout, 0.01);
        $this->assertEqualsWithDelta(20.0, $class->fresh()->commissionRate(), 0.01);
    }

    public function test_a_class_created_later_uses_the_new_rate(): void
    {
        $this->tutor->tutorProfile->update(['commission_rate' => 50]);

        $admin = User::factory()->admin()->create();
        $subject = Subject::create(['name' => 'Physics', 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);

        $this->actingAs($admin)->post('/admin/classes', [
            'tutor_id' => $this->tutor->id, 'subject_id' => $subject->id,
            'delivery_mode' => 'online_group', 'duration_hours' => 2, 'total_sessions' => 1,
            'capacity' => 6, 'price_per_student' => 100,
            'payout_model' => 'per_student', 'status' => 'open',
        ])->assertSessionHasNoErrors();

        // New agreement, new terms.
        $this->assertEqualsWithDelta(50.0, ClassSession::sole()->commissionRate(), 0.01);
    }

    public function test_a_one_to_one_booking_already_snapshots_its_terms(): void
    {
        $class = $this->openClass();
        $this->enrol($class, 'A');

        $booking = Booking::first();

        // The rate is written onto the booking, not looked up later.
        $this->assertEqualsWithDelta(20.0, (float) $booking->commission_rate, 0.01);

        $this->tutor->tutorProfile->update(['commission_rate' => 50]);

        $this->assertEqualsWithDelta(20.0, (float) $booking->fresh()->commission_rate, 0.01);
    }
}
