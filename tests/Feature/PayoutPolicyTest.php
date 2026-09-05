<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorPayout;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\TutorSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * When a tutor becomes payable is chosen per package at creation.
 */
class PayoutPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{User, User, Booking} admin, tutor, booking worth RM800 over 10 sessions */
    private function booking(string $policy, int $totalSessions = 10): array
    {
        $parent = User::factory()->parent()->create();
        $student = Student::create(['parent_id' => $parent->id, 'name' => 'Kid', 'age' => 15]);
        $tutor = User::factory()->tutor()->create();

        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);

        $subject = Subject::create([
            'name' => 'S'.uniqid(), 'category' => 'academic',
            'hourly_rate_home' => 50, 'hourly_rate_online' => 50, 'is_active' => true,
        ]);

        $package = Package::create([
            'name' => 'P', 'package_type' => 'all', 'total_sessions' => $totalSessions,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
            'payout_policy' => $policy,
        ]);

        $req = TutorRequest::create([
            'parent_id' => $parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'package_id' => $package->id, 'preferred_area' => 'PJ', 'preferred_location' => 'home',
            'status' => 'matched', 'matched_tutor_id' => $tutor->id,
        ]);

        $payment = Payment::create([
            'tutor_request_id' => $req->id, 'parent_id' => $parent->id,
            'amount' => 1000, 'commission_amount' => 200, 'tutor_payout' => 800,
            'payment_method' => 'fpx', 'status' => 'success', 'paid_at' => now(),
        ]);

        $booking = Booking::create([
            'tutor_request_id' => $req->id, 'tutor_id' => $tutor->id, 'parent_id' => $parent->id,
            'student_id' => $student->id, 'subject_id' => $subject->id, 'duration_hours' => 2,
            'schedule_day' => 'monday', 'schedule_time' => '10:00', 'location_type' => 'home',
            'hourly_rate' => 50, 'commission_rate' => 20, 'status' => 'confirmed',
        ]);

        $payment->allocateToBookings();

        return [User::factory()->admin()->create(), $tutor, $booking->fresh()];
    }

    private function complete(Booking $booking, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            TutorSession::create([
                'booking_id' => $booking->id, 'session_date' => now()->toDateString(),
                'start_time' => '10:00', 'end_time' => '12:00', 'status' => 'completed',
                'check_in_token' => bin2hex(random_bytes(8)),
            ]);
        }
    }

    public function test_upfront_is_payable_the_moment_the_parent_pays(): void
    {
        [, , $booking] = $this->booking('upfront');

        // No sessions delivered at all.
        $this->assertEquals(800.00, $booking->fresh()->accruedPayout());
    }

    public function test_per_session_accrues_a_share_for_each_completed_session(): void
    {
        [, , $booking] = $this->booking('per_session', totalSessions: 10);

        $this->assertEquals(0.0, $booking->fresh()->accruedPayout());

        $this->complete($booking, 1);
        $this->assertEquals(80.00, $booking->fresh()->accruedPayout());

        $this->complete($booking, 4);
        $this->assertEquals(400.00, $booking->fresh()->accruedPayout());

        $this->complete($booking, 5);
        $this->assertEquals(800.00, $booking->fresh()->accruedPayout());
    }

    public function test_per_session_never_accrues_past_the_whole(): void
    {
        [, , $booking] = $this->booking('per_session', totalSessions: 10);

        // More sessions delivered than the package contains.
        $this->complete($booking, 14);

        $this->assertEquals(800.00, $booking->fresh()->accruedPayout());
    }

    public function test_on_completion_pays_nothing_until_every_session_is_delivered(): void
    {
        [, , $booking] = $this->booking('on_completion', totalSessions: 10);

        $this->complete($booking, 9);
        $this->assertEquals(0.0, $booking->fresh()->accruedPayout());

        $this->complete($booking, 1);
        $this->assertEquals(800.00, $booking->fresh()->accruedPayout());
    }

    public function test_nothing_accrues_while_the_parent_payment_is_unpaid(): void
    {
        [, , $booking] = $this->booking('upfront');
        $booking->payment->update(['status' => 'pending']);

        $this->assertEquals(0.0, $booking->fresh()->accruedPayout());
    }

    public function test_per_session_pays_out_progressively_across_runs(): void
    {
        [$admin, $tutor, $booking] = $this->booking('per_session', totalSessions: 10);

        $period = [
            'period_start' => now()->subDay()->toDateString(),
            'period_end' => now()->addDay()->toDateString(),
        ];

        // Two sessions delivered -> RM160 payable.
        $this->complete($booking, 2);
        $this->actingAs($admin)->post('/admin/payouts', ['tutor_id' => $tutor->id] + $period);
        $this->assertEquals(160.00, (float) TutorPayout::where('tutor_id', $tutor->id)->sum('amount'));

        // Nothing new delivered -> nothing more to pay.
        $again = $this->actingAs($admin)->post('/admin/payouts', ['tutor_id' => $tutor->id] + $period);
        $again->assertSessionHas('error');
        $this->assertEquals(160.00, (float) TutorPayout::where('tutor_id', $tutor->id)->sum('amount'));

        // Three more sessions -> only the new RM240 is paid.
        $this->complete($booking, 3);
        $this->actingAs($admin)->post('/admin/payouts', ['tutor_id' => $tutor->id] + $period);
        $this->assertEquals(400.00, (float) TutorPayout::where('tutor_id', $tutor->id)->sum('amount'));
        $this->assertSame(2, TutorPayout::where('tutor_id', $tutor->id)->count());

        // Booking ledger agrees with the runs.
        $this->assertEquals(400.00, (float) $booking->fresh()->paid_out_amount);
    }

    public function test_upfront_package_is_payable_with_no_sessions_at_all(): void
    {
        [$admin, $tutor] = $this->booking('upfront');

        // The gap this policy closes: money collected but no session recorded.
        $this->actingAs($admin)->post('/admin/payouts', [
            'tutor_id' => $tutor->id,
            'period_start' => now()->subYear()->toDateString(),
            'period_end' => now()->addYear()->toDateString(),
        ]);

        $this->assertEquals(800.00, (float) TutorPayout::where('tutor_id', $tutor->id)->sum('amount'));
    }
}
