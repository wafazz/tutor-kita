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
use App\Support\BookingCancellation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cancelling after payment touches the request, the booking, its sessions, the
 * parent's money and the tutor's entitlement. Setting one status is not a
 * cancellation — it leaves a confirmed booking, scheduled sessions and a tutor
 * still earning for lessons that will never happen.
 */
class CancellationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private User $parent;

    private Student $student;

    private Subject $subject;

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
        $this->student = Student::create(['parent_id' => $this->parent->id, 'name' => 'Ali', 'age' => 14]);
        $this->subject = Subject::create(['name' => 'Maths', 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);
    }

    /** RM500 paid, 5 sessions, RM400 to the tutor. */
    private function paidBooking(int $sessions = 5, int $delivered = 0): Booking
    {
        $package = Package::create(['name' => 'P', 'package_type' => 'all', 'total_sessions' => $sessions,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
            'payout_policy' => 'per_session']);

        $request = TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $this->student->id,
            'subject_id' => $this->subject->id, 'package_id' => $package->id,
            'preferred_area' => 'PJ', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'schedule_day' => 'monday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'status' => 'matched', 'matched_tutor_id' => $this->tutor->id,
        ]);

        $payment = Payment::create([
            'tutor_request_id' => $request->id, 'parent_id' => $this->parent->id,
            'amount' => 500, 'commission_amount' => 100, 'tutor_payout' => 400,
            'payment_method' => 'fpx', 'status' => 'success', 'paid_at' => now(),
        ]);

        $booking = Booking::create([
            'tutor_request_id' => $request->id, 'tutor_id' => $this->tutor->id,
            'parent_id' => $this->parent->id, 'student_id' => $this->student->id,
            'subject_id' => $this->subject->id, 'schedule_day' => 'monday', 'schedule_time' => '10:00',
            'duration_hours' => 2, 'location_type' => 'home',
            'delivery_mode' => DeliveryMode::HomeStudent->value,
            'hourly_rate' => 50, 'commission_rate' => 20, 'status' => 'confirmed',
            'payment_id' => $payment->id, 'amount' => 500, 'commission_amount' => 100, 'tutor_payout' => 400,
        ]);

        $payment->update(['booking_id' => $booking->id]);

        for ($i = 0; $i < $sessions; $i++) {
            TutorSession::create([
                'booking_id' => $booking->id, 'session_date' => now()->addWeeks($i)->toDateString(),
                'start_time' => '10:00', 'end_time' => '12:00',
                'check_in_token' => bin2hex(random_bytes(8)),
                'status' => $i < $delivered ? 'completed' : 'scheduled',
            ]);
        }

        return $booking->fresh();
    }

    public function test_cancelling_before_delivery_refunds_everything(): void
    {
        $booking = $this->paidBooking(sessions: 5, delivered: 0);

        $outcome = app(BookingCancellation::class)->cancel($booking, 'Parent changed their mind');

        $this->assertEqualsWithDelta(500.00, $outcome['refundable'], 0.01);
        $this->assertEqualsWithDelta(0.00, $outcome['tutor_keeps'], 0.01);
        $this->assertSame(5, $outcome['cancelled_sessions']);
    }

    public function test_the_tutor_keeps_what_they_taught(): void
    {
        // Two of five delivered: RM160 of the RM400 payout is earned.
        $booking = $this->paidBooking(sessions: 5, delivered: 2);

        $outcome = app(BookingCancellation::class)->cancel($booking);

        $this->assertEqualsWithDelta(160.00, $outcome['tutor_keeps'], 0.01);
        // The parent gets back the value of the three undelivered sessions.
        $this->assertEqualsWithDelta(300.00, $outcome['refundable'], 0.01);
        $this->assertSame(3, $outcome['cancelled_sessions']);
    }

    public function test_delivered_sessions_survive_and_undelivered_ones_do_not(): void
    {
        $booking = $this->paidBooking(sessions: 5, delivered: 2);

        app(BookingCancellation::class)->cancel($booking);

        $this->assertSame(2, TutorSession::where('status', 'completed')->count());
        $this->assertSame(3, TutorSession::where('status', 'cancelled')->count());
        $this->assertSame(0, TutorSession::where('status', 'scheduled')->count());
    }

    public function test_the_booking_stops_accruing_after_cancellation(): void
    {
        $booking = $this->paidBooking(sessions: 5, delivered: 2);

        app(BookingCancellation::class)->cancel($booking);

        $fresh = $booking->fresh();

        // The contract value is left alone; cancelling the remaining sessions
        // is what caps the tutor at what they actually taught. Overwriting the
        // payout as well would apply that proportion twice.
        $this->assertEqualsWithDelta(400.00, (float) $fresh->tutor_payout, 0.01);
        $this->assertEqualsWithDelta(160.00, $fresh->accruedPayout(), 0.01);
        $this->assertSame('cancelled', $fresh->status);

        // And it cannot grow: there are no deliverable sessions left.
        $this->assertSame(0, $fresh->sessions()->where('status', 'scheduled')->count());
    }

    public function test_money_already_paid_to_the_tutor_is_not_refunded(): void
    {
        $booking = $this->paidBooking(sessions: 5, delivered: 1);
        // The tutor was already paid RM320 in an earlier run.
        $booking->forceFill(['paid_out_amount' => 320])->save();

        $outcome = app(BookingCancellation::class)->cancel($booking->fresh());

        // The platform cannot refund what it has already sent on.
        $this->assertEqualsWithDelta(320.00, $outcome['tutor_keeps'], 0.01);
        $this->assertLessThan(400.00, $outcome['refundable']);
        $this->assertGreaterThanOrEqual(0.0, $outcome['refundable']);
    }

    public function test_a_full_refund_marks_the_payment_refunded(): void
    {
        $booking = $this->paidBooking(sessions: 5, delivered: 0);

        app(BookingCancellation::class)->cancel($booking);

        $payment = Payment::sole();
        $this->assertSame('refunded', $payment->status);
        $this->assertEqualsWithDelta(500.00, (float) $payment->refunded_amount, 0.01);
    }

    public function test_a_partial_refund_leaves_the_payment_successful(): void
    {
        $booking = $this->paidBooking(sessions: 5, delivered: 2);

        app(BookingCancellation::class)->cancel($booking);

        $payment = Payment::sole();
        // It was a real payment; part of it was returned.
        $this->assertSame('success', $payment->status);
        $this->assertEqualsWithDelta(300.00, (float) $payment->refunded_amount, 0.01);
    }

    // ---- who may cancel ----

    public function test_a_parent_cannot_cancel_a_request_they_have_paid_for(): void
    {
        $booking = $this->paidBooking();
        $request = $booking->tutorRequest;

        $this->actingAs($this->parent)
            ->post("/parent/requests/{$request->id}/cancel")
            ->assertSessionHas('error');

        // Nothing was quietly half-cancelled.
        $this->assertSame('matched', $request->fresh()->status);
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_a_parent_can_still_cancel_an_unpaid_request(): void
    {
        $package = Package::create(['name' => 'P', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1]);

        $request = TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $this->student->id,
            'subject_id' => $this->subject->id, 'package_id' => $package->id,
            'preferred_area' => 'PJ', 'status' => 'open',
        ]);

        $this->actingAs($this->parent)
            ->post("/parent/requests/{$request->id}/cancel")
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $request->fresh()->status);
    }

    public function test_an_admin_can_cancel_a_paid_booking_and_is_told_the_outcome(): void
    {
        $booking = $this->paidBooking(sessions: 5, delivered: 2);

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Tutor unavailable'])
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('Tutor unavailable', $booking->fresh()->cancellation_reason);
        $this->assertStringContainsString('RM160.00', session('success'));
        $this->assertStringContainsString('RM300.00', session('success'));
    }

    public function test_a_booking_cannot_be_cancelled_twice(): void
    {
        $booking = $this->paidBooking();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post("/admin/bookings/{$booking->id}/cancel");
        $this->actingAs($admin)->post("/admin/bookings/{$booking->id}/cancel")->assertForbidden();

        // The refund was not counted a second time.
        $this->assertEqualsWithDelta(500.00, (float) Payment::sole()->refunded_amount, 0.01);
    }
}
