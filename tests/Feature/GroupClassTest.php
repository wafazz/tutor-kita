<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Enums\GroupPayoutModel;
use App\Models\Booking;
use App\Models\Centre;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use App\Support\ClassEnroller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Group teaching, where one tutor serves several students. Each student keeps
 * their own booking and payment, so the existing ledger continues to apply;
 * the class decides only what the tutor is owed in total.
 */
class GroupClassTest extends TestCase
{
    use RefreshDatabase;

    private function tutor(float $commission = 20): User
    {
        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => $commission,
        ]);

        return $tutor->fresh();
    }

    private function classSession(array $overrides = []): ClassSession
    {
        return ClassSession::create(array_merge([
            'tutor_id' => $this->tutor()->id,
            'subject_id' => Subject::create([
                'name' => 'S'.uniqid(), 'category' => 'academic',
                'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true,
            ])->id,
            'delivery_mode' => DeliveryMode::CentreGroup->value,
            'title' => 'Saturday Maths',
            'schedule_day' => 'saturday', 'schedule_time' => '10:00',
            'duration_hours' => 1.5, 'total_sessions' => 1,
            'capacity' => 8, 'price_per_student' => 30,
            'payout_model' => GroupPayoutModel::PerStudent->value,
            'status' => 'open',
        ], $overrides));
    }

    private function students(int $n): array
    {
        $parent = User::factory()->parent()->create();

        return collect(range(1, $n))
            ->map(fn ($i) => Student::create(['parent_id' => $parent->id, 'name' => "Kid {$i}", 'age' => 14]))
            ->all();
    }

    public function test_enrolling_gives_each_student_their_own_booking_and_payment(): void
    {
        $class = $this->classSession();
        $enroller = new ClassEnroller;

        foreach ($this->students(3) as $student) {
            $enroller->enrol($class, $student);
        }

        $class->refresh();

        $this->assertSame(3, $class->seatsTaken());
        $this->assertSame(5, $class->seatsLeft());
        $this->assertSame(3, Booking::count());
        $this->assertEquals(90.00, $class->revenue());
    }

    public function test_per_student_pays_the_tutor_their_commission_share(): void
    {
        $class = $this->classSession();
        $enroller = new ClassEnroller;

        foreach ($this->students(8) as $student) {
            $enroller->enrol($class, $student);
        }

        $class->refresh();

        // 8 x RM30 = RM240, tutor on 20% commission keeps RM192.
        $this->assertEquals(240.00, $class->revenue());
        $this->assertEquals(192.00, $class->tutorPayoutTotal());
        $this->assertEquals(48.00, $class->platformShare());
    }

    public function test_a_flat_rate_pays_the_same_however_full_the_class_is(): void
    {
        $class = $this->classSession([
            'payout_model' => GroupPayoutModel::Flat->value,
            'payout_base' => 80,
        ]);
        $enroller = new ClassEnroller;
        $students = $this->students(8);

        $enroller->enrol($class, $students[0]);
        $this->assertEquals(80.00, $class->fresh()->tutorPayoutTotal());

        foreach (array_slice($students, 1) as $student) {
            $enroller->enrol($class, $student);
        }

        $class->refresh();

        $this->assertEquals(80.00, $class->tutorPayoutTotal());
        $this->assertEquals(160.00, $class->platformShare());
    }

    public function test_flat_plus_head_adds_a_bonus_past_the_threshold(): void
    {
        $class = $this->classSession([
            'payout_model' => GroupPayoutModel::FlatPlusHead->value,
            'payout_base' => 60, 'payout_per_head' => 10, 'payout_head_threshold' => 4,
        ]);
        $enroller = new ClassEnroller;
        $students = $this->students(8);

        foreach (array_slice($students, 0, 4) as $student) {
            $enroller->enrol($class, $student);
        }

        // At the threshold, only the floor applies.
        $this->assertEquals(60.00, $class->fresh()->tutorPayoutTotal());

        foreach (array_slice($students, 4) as $student) {
            $enroller->enrol($class, $student);
        }

        // 8 students: RM60 + 4 x RM10.
        $this->assertEquals(100.00, $class->fresh()->tutorPayoutTotal());
    }

    public function test_the_shares_always_sum_back_to_the_class_total(): void
    {
        // RM100 across 3 students does not divide cleanly.
        $class = $this->classSession([
            'payout_model' => GroupPayoutModel::Flat->value,
            'payout_base' => 100,
        ]);
        $enroller = new ClassEnroller;

        foreach ($this->students(3) as $student) {
            $enroller->enrol($class, $student);
        }

        $class->refresh();
        $bookings = Booking::whereIn('id', $class->activeEnrolments()->pluck('booking_id'))->get();

        $this->assertEqualsWithDelta(100.00, $bookings->sum(fn ($b) => (float) $b->tutor_payout), 0.001);
    }

    public function test_each_booking_still_splits_into_commission_plus_payout(): void
    {
        $class = $this->classSession([
            'payout_model' => GroupPayoutModel::Flat->value, 'payout_base' => 100,
        ]);
        $enroller = new ClassEnroller;

        foreach ($this->students(3) as $student) {
            $enroller->enrol($class, $student);
        }

        foreach (Booking::all() as $booking) {
            $this->assertEqualsWithDelta(
                (float) $booking->amount,
                (float) $booking->commission_amount + (float) $booking->tutor_payout,
                0.001
            );
        }
    }

    public function test_a_class_cannot_be_filled_past_capacity(): void
    {
        $class = $this->classSession(['capacity' => 2]);
        $enroller = new ClassEnroller;
        $students = $this->students(3);

        $enroller->enrol($class, $students[0]);
        $enroller->enrol($class, $students[1]);

        $this->expectException(\RuntimeException::class);
        $enroller->enrol($class->fresh(), $students[2]);
    }

    public function test_a_centre_smaller_than_the_class_caps_the_seats(): void
    {
        $centre = Centre::create(['name' => 'Small room', 'address' => 'a', 'capacity' => 3]);
        $class = $this->classSession(['capacity' => 20, 'centre_id' => $centre->id]);

        $this->assertSame(3, $class->load('centre')->seatsLeft());
    }

    public function test_the_same_student_cannot_take_two_seats(): void
    {
        $class = $this->classSession();
        $student = $this->students(1)[0];
        $enroller = new ClassEnroller;

        $enroller->enrol($class, $student);

        $this->expectException(\RuntimeException::class);
        $enroller->enrol($class->fresh(), $student);
    }

    public function test_a_fixed_payout_on_an_underfilled_class_is_flagged(): void
    {
        $class = $this->classSession([
            'payout_model' => GroupPayoutModel::Flat->value, 'payout_base' => 200,
        ]);
        $enroller = new ClassEnroller;

        // Two students paying RM30 cannot cover a RM200 promise.
        foreach (array_slice($this->students(2), 0) as $student) {
            $enroller->enrol($class, $student);
        }

        $this->assertTrue($class->fresh()->isUnderwater());
    }

    public function test_per_student_classes_are_never_underwater(): void
    {
        $class = $this->classSession();
        (new ClassEnroller)->enrol($class, $this->students(1)[0]);

        $this->assertFalse($class->fresh()->isUnderwater());
    }

    public function test_cancelling_an_enrolment_reshares_the_remaining_seats(): void
    {
        $class = $this->classSession([
            'payout_model' => GroupPayoutModel::Flat->value, 'payout_base' => 90,
        ]);
        $enroller = new ClassEnroller;
        $enrolments = [];

        foreach ($this->students(3) as $student) {
            $enrolments[] = $enroller->enrol($class, $student);
        }

        $enroller->cancel($enrolments[0]);

        $class->refresh();
        $remaining = Booking::whereIn('id', $class->activeEnrolments()->pluck('booking_id'))->get();

        $this->assertSame(2, $class->seatsTaken());
        $this->assertEqualsWithDelta(90.00, $remaining->sum(fn ($b) => (float) $b->tutor_payout), 0.001);
    }
}
