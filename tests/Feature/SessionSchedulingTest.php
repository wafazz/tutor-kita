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
use App\Support\SessionScheduler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sessions are what delivery is recorded against, so a booking with fewer than
 * its package promises can never be fully paid under per-session accrual.
 * Laying them out is what makes the money reachable, not bookkeeping.
 */
class SessionSchedulingTest extends TestCase
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

    private function booking(int $sessions = 4, string $policy = 'per_session'): Booking
    {
        $package = Package::create([
            'name' => 'P', 'package_type' => 'all', 'total_sessions' => $sessions,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
            'payout_policy' => $policy,
        ]);
        $subject = Subject::create(['name' => 'S'.uniqid(), 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);
        $student = Student::create(['parent_id' => $this->parent->id, 'name' => 'Kid', 'age' => 14]);

        $request = TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'package_id' => $package->id, 'preferred_area' => 'PJ',
            'delivery_mode' => DeliveryMode::HomeStudent->value,
            'status' => 'matched', 'matched_tutor_id' => $this->tutor->id,
        ]);

        $payment = Payment::create([
            'tutor_request_id' => $request->id, 'parent_id' => $this->parent->id,
            'amount' => 500, 'commission_amount' => 100, 'tutor_payout' => 400,
            'payment_method' => 'fpx', 'status' => 'success', 'paid_at' => now(),
        ]);

        return Booking::create([
            'tutor_request_id' => $request->id, 'tutor_id' => $this->tutor->id,
            'parent_id' => $this->parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'schedule_day' => 'saturday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'location_type' => 'home', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'hourly_rate' => 60, 'commission_rate' => 20, 'status' => 'confirmed',
            'payment_id' => $payment->id, 'amount' => 500, 'commission_amount' => 100, 'tutor_payout' => 400,
        ]);
    }

    public function test_sessions_are_laid_out_weekly_on_the_bookings_day(): void
    {
        $booking = $this->booking(sessions: 4);

        app(SessionScheduler::class)->ensure($booking, 4);

        $dates = $booking->sessions()->orderBy('session_date')->pluck('session_date');

        $this->assertCount(4, $dates);

        foreach ($dates as $date) {
            $this->assertSame(Carbon::SATURDAY, Carbon::parse($date)->dayOfWeek);
        }

        // Consecutive weeks, not the same day four times.
        $this->assertEqualsWithDelta(7, Carbon::parse($dates[0])->diffInDays(Carbon::parse($dates[1])), 0.01);
    }

    public function test_running_twice_does_not_double_up(): void
    {
        $booking = $this->booking(sessions: 4);
        $scheduler = app(SessionScheduler::class);

        $this->assertSame(4, $scheduler->ensure($booking, 4));
        $this->assertSame(0, $scheduler->ensure($booking->fresh(), 4));
        $this->assertSame(4, TutorSession::count());
    }

    public function test_it_tops_up_a_partly_scheduled_booking(): void
    {
        $booking = $this->booking(sessions: 6);
        $scheduler = app(SessionScheduler::class);

        $scheduler->ensure($booking, 2);
        $this->assertSame(4, $scheduler->ensure($booking->fresh(), 6));
        $this->assertSame(6, TutorSession::count());
    }

    public function test_it_never_creates_a_second_session_on_a_taken_date(): void
    {
        $booking = $this->booking(sessions: 3);

        TutorSession::create([
            'booking_id' => $booking->id,
            'session_date' => Carbon::today()->next(Carbon::SATURDAY)->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'check_in_token' => bin2hex(random_bytes(8)), 'status' => 'scheduled',
        ]);

        app(SessionScheduler::class)->ensure($booking->fresh(), 3);

        $dates = TutorSession::pluck('session_date')->map(fn ($d) => Carbon::parse($d)->toDateString());

        $this->assertCount(3, $dates);
        $this->assertSame(3, $dates->unique()->count(), 'a date was scheduled twice');
    }

    public function test_paying_for_a_request_lays_out_its_sessions(): void
    {
        $package = Package::create([
            'name' => 'P', 'package_type' => 'all', 'total_sessions' => 5,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
            'payout_policy' => 'per_session',
        ]);
        $subject = Subject::create(['name' => 'S'.uniqid(), 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);
        $student = Student::create(['parent_id' => $this->parent->id, 'name' => 'Kid', 'age' => 14]);

        $request = TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'package_id' => $package->id, 'preferred_area' => 'PJ',
            'delivery_mode' => DeliveryMode::HomeStudent->value, 'schedule_day' => 'monday',
            'schedule_time' => '10:00', 'duration_hours' => 2,
            'status' => 'matched', 'matched_tutor_id' => $this->tutor->id,
        ]);

        $payment = Payment::create([
            'tutor_request_id' => $request->id, 'parent_id' => $this->parent->id,
            'amount' => 500, 'commission_amount' => 100, 'tutor_payout' => 400,
            'payment_method' => 'fpx', 'status' => 'pending',
        ]);

        // No gateway keys, so this takes the manual path and creates the booking.
        $this->actingAs($this->parent)->post("/parent/payments/{$payment->id}/pay");

        $booking = Booking::sole();

        // Previously the tutor had to remember to press a button.
        $this->assertSame(5, $booking->sessions()->count());

        // The request never set a location_type, and bookings require one.
        $this->assertSame('home', $booking->location_type);
    }

    public function test_a_fully_delivered_booking_accrues_everything_without_anyone_pressing_a_button(): void
    {
        $booking = $this->booking(sessions: 4, policy: 'per_session');

        app(SessionScheduler::class)->ensure($booking, 4);
        TutorSession::query()->update(['status' => 'completed']);

        $this->assertEqualsWithDelta(400.00, $booking->fresh()->accruedPayout(), 0.01);
    }

    public function test_the_tutors_own_button_still_tops_up_and_reports_when_nothing_is_needed(): void
    {
        $booking = $this->booking(sessions: 3);

        $this->actingAs($this->tutor)
            ->post("/tutor/bookings/{$booking->id}/generate-sessions")
            ->assertSessionHas('success');

        $this->assertSame(3, $booking->sessions()->count());

        $this->actingAs($this->tutor)
            ->post("/tutor/bookings/{$booking->id}/generate-sessions");

        $this->assertStringContainsString('already exist', session('success'));
        $this->assertSame(3, $booking->fresh()->sessions()->count());
    }

    public function test_another_tutor_cannot_schedule_someone_elses_booking(): void
    {
        $booking = $this->booking();
        // Verified, so the ownership check is what refuses them rather than
        // the verification middleware.
        $other = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $other->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);

        $this->actingAs($other)
            ->post("/tutor/bookings/{$booking->id}/generate-sessions")
            ->assertForbidden();
    }
}
