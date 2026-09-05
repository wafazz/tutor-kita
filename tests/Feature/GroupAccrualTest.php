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
use App\Models\TutorPayout;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\TutorSession;
use App\Models\User;
use App\Support\ClassEnroller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A group class owes its tutor real money, and that money has to reach them.
 *
 * A seat has no package, so accrual has to take the number of sessions from the
 * class instead — otherwise every group booking looks like a one-session
 * package with nothing delivered and accrues nothing, whatever it is worth.
 */
class GroupAccrualTest extends TestCase
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

    private function paidClass(int $students, int $sessions = 1, array $overrides = []): ClassSession
    {
        $class = ClassSession::create(array_merge([
            'tutor_id' => $this->tutor->id,
            'subject_id' => Subject::create(['name' => 'S'.uniqid(), 'category' => 'academic',
                'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true])->id,
            'delivery_mode' => DeliveryMode::OnlineGroup->value, 'title' => 'C',
            'schedule_day' => 'saturday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'total_sessions' => $sessions, 'capacity' => 10, 'price_per_student' => 30,
            'payout_model' => GroupPayoutModel::PerStudent->value, 'status' => 'open',
        ], $overrides));

        $enroller = new ClassEnroller;

        foreach (range(1, $students) as $i) {
            $student = Student::create(['parent_id' => $this->parent->id, 'name' => "Kid {$i}", 'age' => 14]);
            $enroller->enrol($class, $student)->payment->update(['status' => 'success', 'paid_at' => now()]);
        }

        return $class->fresh();
    }

    private function bookingsOf(ClassSession $class)
    {
        return Booking::whereIn('id', $class->activeEnrolments()->pluck('booking_id'))
            ->with(['payment', 'tutorRequest.package', 'sessions'])->get();
    }

    public function test_enrolling_lays_out_the_sessions_the_class_runs(): void
    {
        $class = $this->paidClass(students: 2, sessions: 6);

        // Six weekly sessions each.
        $this->assertSame(12, TutorSession::count());

        $dates = TutorSession::orderBy('session_date')->pluck('session_date')->unique()->values();
        $this->assertCount(6, $dates);
    }

    public function test_a_delivered_group_class_accrues_the_full_amount_it_owes(): void
    {
        $class = $this->paidClass(students: 4);

        // 4 x RM30 = RM120, tutor on 20% keeps RM96.
        $this->assertEquals(96.00, $class->tutorPayoutTotal());

        // Nothing delivered yet, so nothing has been earned.
        $this->assertEquals(0.0, $this->bookingsOf($class)->sum(fn ($b) => $b->accruedPayout()));

        TutorSession::query()->update(['status' => 'completed']);

        $this->assertEqualsWithDelta(96.00, $this->bookingsOf($class)->sum(fn ($b) => $b->accruedPayout()), 0.01);
    }

    public function test_a_multi_week_class_accrues_as_the_weeks_are_delivered(): void
    {
        $class = $this->paidClass(students: 2, sessions: 4);
        $owed = $class->tutorPayoutTotal();

        // One of four weeks delivered for every student.
        foreach ($this->bookingsOf($class) as $booking) {
            $booking->sessions()->orderBy('session_date')->first()->update(['status' => 'completed']);
        }

        $this->assertEqualsWithDelta($owed / 4, $this->bookingsOf($class)->sum(fn ($b) => $b->accruedPayout()), 0.01);
    }

    public function test_the_tutor_is_actually_paid_for_a_delivered_class(): void
    {
        $class = $this->paidClass(students: 4);
        TutorSession::query()->update(['status' => 'completed']);

        $this->actingAs(User::factory()->admin()->create())->post('/admin/payouts', [
            'tutor_id' => $this->tutor->id,
            'period_start' => now()->subMonth()->toDateString(),
            'period_end' => now()->addMonths(3)->toDateString(),
        ]);

        // Previously this ran and paid nothing at all.
        $this->assertEqualsWithDelta(96.00, (float) TutorPayout::where('tutor_id', $this->tutor->id)->sum('amount'), 0.01);
    }

    public function test_a_flat_rate_class_pays_its_fixed_amount_once_delivered(): void
    {
        $class = $this->paidClass(students: 8, overrides: [
            'payout_model' => GroupPayoutModel::Flat->value, 'payout_base' => 80,
        ]);
        TutorSession::query()->update(['status' => 'completed']);

        $this->assertEqualsWithDelta(80.00, $this->bookingsOf($class)->sum(fn ($b) => $b->accruedPayout()), 0.01);
    }

    public function test_one_to_one_accrual_is_unchanged_by_this(): void
    {
        // A booking with a real package must still read its policy from there.
        $package = Package::create([
            'name' => 'P', 'package_type' => 'all', 'total_sessions' => 2, 'duration_hours' => 2,
            'price' => 0, 'is_active' => true, 'sort_order' => 1, 'payout_policy' => 'upfront',
        ]);
        $subject = Subject::create(['name' => 'S'.uniqid(), 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);
        $student = Student::create(['parent_id' => $this->parent->id, 'name' => 'Solo', 'age' => 14]);

        $request = TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'package_id' => $package->id, 'preferred_area' => 'PJ',
            'delivery_mode' => DeliveryMode::HomeStudent->value, 'status' => 'matched',
            'matched_tutor_id' => $this->tutor->id,
        ]);
        $payment = Payment::create([
            'tutor_request_id' => $request->id, 'parent_id' => $this->parent->id,
            'amount' => 100, 'commission_amount' => 20, 'tutor_payout' => 80,
            'payment_method' => 'fpx', 'status' => 'success', 'paid_at' => now(),
        ]);
        $booking = Booking::create([
            'tutor_request_id' => $request->id, 'tutor_id' => $this->tutor->id,
            'parent_id' => $this->parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'schedule_day' => 'monday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'location_type' => 'home', 'hourly_rate' => 50, 'commission_rate' => 20,
            'status' => 'confirmed', 'payment_id' => $payment->id, 'tutor_payout' => 80, 'amount' => 100,
        ]);

        // Upfront, with no sessions at all.
        $this->assertEquals(80.0, $booking->fresh()->accruedPayout());
    }
}
