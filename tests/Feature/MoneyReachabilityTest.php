<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Enums\GroupPayoutModel;
use App\Models\Booking;
use App\Models\ClassSession;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\TutorSession;
use App\Models\User;
use App\Support\ClassEnroller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Liveness, as opposed to the safety properties in MoneyInvariantsTest.
 *
 * Those ask whether too much was paid, and every one of them held while group
 * classes accrued nothing at all — "paid never exceeds accrued" is trivially
 * true when accrued is zero. Nothing asked the opposite question: once the work
 * is done, does the money the tutor is owed actually become payable?
 *
 * These assert that it does, across every combination of delivery mode and
 * payout arrangement, so a whole category of tutor cannot silently become
 * unpayable again.
 */
class MoneyReachabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $this->tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);

        $this->parent = User::factory()->parent()->create();
    }

    private function subject(): Subject
    {
        return Subject::create(['name' => 'S'.uniqid(), 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);
    }

    /** @return array<string, array{string, string, int}> */
    public static function oneToOneProvider(): array
    {
        $cases = [];

        foreach (['upfront', 'per_session', 'on_completion'] as $policy) {
            foreach ([DeliveryMode::HomeStudent, DeliveryMode::HomeTutor, DeliveryMode::OnlineSolo] as $mode) {
                $cases["{$policy} / {$mode->value}"] = [$policy, $mode->value, 4];
            }
        }

        return $cases;
    }

    #[DataProvider('oneToOneProvider')]
    public function test_a_delivered_one_to_one_booking_accrues_everything_it_owes(
        string $policy,
        string $mode,
        int $sessions
    ): void {
        $package = Package::create([
            'name' => 'P', 'package_type' => 'all', 'total_sessions' => $sessions,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
            'payout_policy' => $policy,
        ]);
        $subject = $this->subject();
        $student = Student::create(['parent_id' => $this->parent->id, 'name' => 'Kid', 'age' => 14]);

        $request = TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'package_id' => $package->id, 'preferred_area' => 'PJ', 'delivery_mode' => $mode,
            'status' => 'matched', 'matched_tutor_id' => $this->tutor->id,
        ]);

        $payment = Payment::create([
            'tutor_request_id' => $request->id, 'parent_id' => $this->parent->id,
            'amount' => 500, 'commission_amount' => 100, 'tutor_payout' => 400,
            'payment_method' => 'fpx', 'status' => 'success', 'paid_at' => now(),
        ]);

        $booking = Booking::create([
            'tutor_request_id' => $request->id, 'tutor_id' => $this->tutor->id,
            'parent_id' => $this->parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'schedule_day' => 'monday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'location_type' => 'home', 'delivery_mode' => $mode,
            'hourly_rate' => 50, 'commission_rate' => 20, 'status' => 'confirmed',
            'payment_id' => $payment->id, 'amount' => 500, 'commission_amount' => 100, 'tutor_payout' => 400,
        ]);

        // Deliver every session the package promises.
        for ($i = 0; $i < $sessions; $i++) {
            TutorSession::create([
                'booking_id' => $booking->id, 'session_date' => now()->addWeeks($i)->toDateString(),
                'start_time' => '10:00', 'end_time' => '12:00', 'status' => 'completed',
                'check_in_token' => bin2hex(random_bytes(8)),
            ]);
        }

        $this->assertEqualsWithDelta(
            400.00,
            $booking->fresh()->accruedPayout(),
            0.01,
            "fully delivered work under '{$policy}' did not accrue what it owes"
        );
    }

    /** @return array<string, array{string, array<string, mixed>}> */
    public static function groupProvider(): array
    {
        return [
            'per_student' => [GroupPayoutModel::PerStudent->value, []],
            'flat' => [GroupPayoutModel::Flat->value, ['payout_base' => 80]],
            'flat_plus_head' => [GroupPayoutModel::FlatPlusHead->value,
                ['payout_base' => 60, 'payout_per_head' => 10, 'payout_head_threshold' => 2]],
        ];
    }

    #[DataProvider('groupProvider')]
    public function test_a_delivered_group_class_accrues_everything_it_owes(string $model, array $config): void
    {
        foreach ([DeliveryMode::CentreGroup, DeliveryMode::OnlineGroup] as $mode) {
            $class = ClassSession::create(array_merge([
                'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject()->id,
                'delivery_mode' => $mode->value, 'title' => 'C'.uniqid(),
                'schedule_day' => 'saturday', 'schedule_time' => '10:00', 'duration_hours' => 2,
                'total_sessions' => 3, 'capacity' => 10, 'price_per_student' => 30,
                'payout_model' => $model, 'status' => 'open',
            ], $config));

            $enroller = new ClassEnroller;

            foreach (range(1, 5) as $i) {
                $student = Student::create(['parent_id' => $this->parent->id, 'name' => "K{$i}", 'age' => 14]);
                $enroller->enrol($class, $student)->payment->update(['status' => 'success', 'paid_at' => now()]);
            }

            $class->refresh();
            $owed = $class->tutorPayoutTotal();
            $this->assertGreaterThan(0, $owed, 'the class should owe the tutor something');

            $bookings = Booking::whereIn('id', $class->activeEnrolments()->pluck('booking_id'))->get();
            TutorSession::whereIn('booking_id', $bookings->pluck('id'))->update(['status' => 'completed']);

            $accrued = Booking::whereIn('id', $bookings->pluck('id'))
                ->with(['payment', 'tutorRequest.package', 'sessions', 'classEnrolment.classSession'])
                ->get()->sum(fn ($b) => $b->accruedPayout());

            $this->assertEqualsWithDelta(
                $owed, $accrued, 0.01,
                "a delivered {$mode->value} class on '{$model}' did not accrue what it owes"
            );
        }
    }

    public function test_every_booking_that_owes_money_can_reach_full_accrual(): void
    {
        // The structural version of the same property: a booking must either be
        // paid upfront, or have enough sessions to ever be fully delivered.
        // A group booking with no sessions satisfies neither, which is exactly
        // how group classes became unpayable.
        $class = ClassSession::create([
            'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject()->id,
            'delivery_mode' => DeliveryMode::OnlineGroup->value, 'title' => 'C',
            'schedule_day' => 'saturday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'total_sessions' => 6, 'capacity' => 10, 'price_per_student' => 30,
            'payout_model' => GroupPayoutModel::PerStudent->value, 'status' => 'open',
        ]);

        $enroller = new ClassEnroller;

        foreach (range(1, 3) as $i) {
            $student = Student::create(['parent_id' => $this->parent->id, 'name' => "K{$i}", 'age' => 14]);
            $enroller->enrol($class, $student)->payment->update(['status' => 'success', 'paid_at' => now()]);
        }

        foreach (Booking::with(['payment', 'tutorRequest.package', 'sessions', 'classEnrolment.classSession'])->get() as $booking) {
            if ($booking->payment?->status !== 'success' || (float) $booking->tutor_payout <= 0) {
                continue;
            }

            $package = $booking->tutorRequest?->package;
            $required = (int) ($package->total_sessions
                ?? $booking->classEnrolment?->classSession?->total_sessions
                ?? 1);

            $this->assertTrue(
                ($package->payout_policy ?? 'per_session') === 'upfront'
                    || $booking->sessions->count() >= $required,
                "booking {$booking->id} owes RM{$booking->tutor_payout} but has "
                    ."{$booking->sessions->count()} of the {$required} session(s) it needs to ever be paid"
            );
        }
    }
}
