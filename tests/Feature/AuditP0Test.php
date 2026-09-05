<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\TutorSession;
use App\Models\User;
use App\Support\Payments\PaymentCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The critical findings from the business logic audit.
 *
 * 2.2 marking a payment paid must complete the transaction behind it
 * 2.3 completion must be safe to run twice
 * 2.4 the schedule must be revalidated when the tutor accepts
 */
class AuditP0Test extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private User $parent;

    private Student $student;

    private Subject $subject;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->tutor()->create(['name' => 'Cikgu A']);
        TutorProfile::create([
            'user_id' => $this->tutor->id, 'subjects' => ['Maths'], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
            'latitude' => 3.1073, 'longitude' => 101.6067,
        ]);

        $this->parent = User::factory()->parent()->create();
        $this->student = Student::create([
            'parent_id' => $this->parent->id, 'name' => 'Ali', 'age' => 14,
            'latitude' => 3.1073, 'longitude' => 101.6067,
        ]);
        $this->subject = Subject::create(['name' => 'Maths', 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);
        $this->package = Package::create(['name' => 'P', 'package_type' => 'all', 'total_sessions' => 4,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
            'payout_policy' => 'per_session']);
    }

    private function pendingPayment(): Payment
    {
        $request = TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $this->student->id,
            'subject_id' => $this->subject->id, 'package_id' => $this->package->id,
            'preferred_area' => 'PJ', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'schedule_day' => 'monday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'status' => 'matched', 'matched_tutor_id' => $this->tutor->id,
        ]);

        return Payment::create([
            'tutor_request_id' => $request->id, 'parent_id' => $this->parent->id,
            'amount' => 480, 'commission_amount' => 96, 'tutor_payout' => 384,
            'payment_method' => 'fpx', 'status' => 'pending',
        ]);
    }

    // ---- 2.2 ----

    public function test_an_admin_marking_a_payment_paid_creates_the_booking_and_sessions(): void
    {
        $payment = $this->pendingPayment();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/payments/{$payment->id}/mark-paid")
            ->assertSessionHasNoErrors();

        // Previously this set a status and nothing else.
        $this->assertSame(1, Booking::count());
        $this->assertSame(4, TutorSession::count());
        $this->assertNotNull($payment->fresh()->booking_id);
    }

    public function test_money_marked_paid_by_an_admin_can_actually_reach_the_tutor(): void
    {
        $payment = $this->pendingPayment();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/payments/{$payment->id}/mark-paid");

        TutorSession::query()->update(['status' => 'completed']);

        $booking = Booking::sole();

        // A payment with no booking is money no payout run can ever release.
        $this->assertEqualsWithDelta(384.00, $booking->fresh()->accruedPayout(), 0.01);
    }

    // ---- 2.3 ----

    public function test_completing_the_same_payment_twice_creates_nothing_extra(): void
    {
        $payment = $this->pendingPayment();
        $payment->update(['status' => 'success', 'paid_at' => now()]);

        $completion = app(PaymentCompletion::class);

        $this->assertTrue($completion->complete($payment));
        $this->assertFalse($completion->complete($payment->fresh()), 'the second call should be a no-op');

        $this->assertSame(1, Booking::count());
        $this->assertSame(4, TutorSession::count());
    }

    public function test_a_payment_that_is_not_successful_is_never_completed(): void
    {
        $payment = $this->pendingPayment();

        $this->assertFalse(app(PaymentCompletion::class)->complete($payment));
        $this->assertSame(0, Booking::count());
    }

    // ---- 2.4 ----

    private function acceptJob(TutorRequest $request, string $day, string $time, float $hours = 2)
    {
        return $this->actingAs($this->tutor)->post("/tutor/jobs/{$request->id}/accept", [
            'schedule_day' => $day, 'schedule_time' => $time,
            'duration_hours' => $hours, 'location_type' => 'home',
        ]);
    }

    private function openJob(): TutorRequest
    {
        return TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $this->student->id,
            'subject_id' => $this->subject->id, 'package_id' => $this->package->id,
            'preferred_area' => 'PJ', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'status' => 'matched', 'matched_tutor_id' => $this->tutor->id,
        ]);
    }

    public function test_a_tutor_cannot_accept_a_job_that_clashes_with_their_own_diary(): void
    {
        Booking::create([
            'tutor_id' => $this->tutor->id, 'parent_id' => $this->parent->id,
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id,
            'schedule_day' => 'monday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'location_type' => 'home', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'hourly_rate' => 60, 'commission_rate' => 20, 'status' => 'confirmed',
        ]);

        $job = $this->openJob();

        // The tutor picks the time here, so this is the first moment there is
        // anything to check.
        $this->acceptJob($job, 'monday', '11:00')->assertSessionHas('error');

        $this->assertFalse((bool) $job->fresh()->tutor_accepted);
    }

    public function test_a_tutor_can_accept_a_slot_that_is_genuinely_free(): void
    {
        $job = $this->openJob();

        $this->acceptJob($job, 'tuesday', '14:00')->assertSessionHasNoErrors();

        $this->assertTrue((bool) $job->fresh()->tutor_accepted);
        $this->assertSame('tuesday', $job->fresh()->schedule_day);
    }

    public function test_acceptance_is_checked_even_when_matching_had_no_schedule_to_check(): void
    {
        // The request was matched with no day or time, so nothing could be
        // validated then — which is exactly how a clash used to get through.
        $job = $this->openJob();
        $this->assertNull($job->schedule_day);

        Booking::create([
            'tutor_id' => $this->tutor->id, 'parent_id' => $this->parent->id,
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id,
            'schedule_day' => 'wednesday', 'schedule_time' => '09:00', 'duration_hours' => 3,
            'location_type' => 'home', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'hourly_rate' => 60, 'commission_rate' => 20, 'status' => 'confirmed',
        ]);

        $this->acceptJob($job, 'wednesday', '10:00')->assertSessionHas('error');
    }

    // ---- section 14: acceptance guards ----

    public function test_a_tutor_whose_verification_lapsed_cannot_accept(): void
    {
        $job = $this->openJob();

        // Matching happened earlier; eligibility can lapse in between.
        $this->tutor->tutorProfile->update(['verification_status' => 'rejected']);

        // Refused before the controller by the verification middleware, so the
        // outcome is what matters here rather than which layer stopped it.
        $this->acceptJob($job, 'tuesday', '14:00');

        $this->assertFalse((bool) $job->fresh()->tutor_accepted);
    }

    public function test_a_tutor_who_no_longer_teaches_the_subject_cannot_accept(): void
    {
        $job = $this->openJob();

        $this->tutor->tutorProfile->update(['subjects' => ['Physics']]);

        $this->acceptJob($job, 'tuesday', '14:00')->assertSessionHas('error');
    }

    public function test_a_job_cannot_be_accepted_twice(): void
    {
        $job = $this->openJob();

        $this->acceptJob($job, 'tuesday', '14:00')->assertSessionHasNoErrors();
        $this->acceptJob($job->fresh(), 'wednesday', '09:00')->assertSessionHas('error');

        // The second attempt did not overwrite the agreed schedule.
        $this->assertSame('tuesday', $job->fresh()->schedule_day);
    }

    public function test_a_cancelled_request_cannot_be_accepted(): void
    {
        $job = $this->openJob();
        $job->update(['status' => 'cancelled']);

        $this->acceptJob($job, 'tuesday', '14:00')->assertSessionHas('error');

        $this->assertFalse((bool) $job->fresh()->tutor_accepted);
    }
}
