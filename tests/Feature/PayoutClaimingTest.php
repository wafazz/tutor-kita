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
 * A payout claims the bookings it pays. Payout runs select by session date but
 * the amount is held per booking, so without claiming, a booking whose sessions
 * straddle two periods was paid in full by both.
 */
class PayoutClaimingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{User, User, Booking} admin, tutor, booking worth RM80 */
    private function paidBooking(array $sessionDates = []): array
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
            'name' => 'P', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
        ]);

        $req = TutorRequest::create([
            'parent_id' => $parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'package_id' => $package->id, 'preferred_area' => 'PJ', 'preferred_location' => 'home',
            'status' => 'matched', 'matched_tutor_id' => $tutor->id,
        ]);

        $payment = Payment::create([
            'tutor_request_id' => $req->id, 'parent_id' => $parent->id,
            'amount' => 100, 'commission_amount' => 20, 'tutor_payout' => 80,
            'payment_method' => 'fpx', 'status' => 'success', 'paid_at' => now(),
        ]);

        $booking = Booking::create([
            'tutor_request_id' => $req->id, 'tutor_id' => $tutor->id, 'parent_id' => $parent->id,
            'student_id' => $student->id, 'subject_id' => $subject->id, 'duration_hours' => 2,
            'schedule_day' => 'monday', 'schedule_time' => '10:00', 'location_type' => 'home',
            'hourly_rate' => 50, 'commission_rate' => 20, 'status' => 'confirmed',
        ]);

        $payment->allocateToBookings();

        foreach ($sessionDates as $date) {
            TutorSession::create([
                'booking_id' => $booking->id, 'session_date' => $date,
                'start_time' => '10:00', 'end_time' => '12:00', 'status' => 'completed',
                'check_in_token' => bin2hex(random_bytes(8)),
            ]);
        }

        return [User::factory()->admin()->create(), $tutor, $booking->fresh()];
    }

    public function test_the_same_period_cannot_be_paid_out_twice(): void
    {
        [$admin, $tutor] = $this->paidBooking([now()->toDateString()]);

        $period = [
            'period_start' => now()->subDay()->toDateString(),
            'period_end' => now()->addDay()->toDateString(),
        ];

        $this->actingAs($admin)->post('/admin/payouts', ['tutor_id' => $tutor->id] + $period);
        $second = $this->actingAs($admin)->post('/admin/payouts', ['tutor_id' => $tutor->id] + $period);

        $this->assertSame(1, TutorPayout::where('tutor_id', $tutor->id)->count());
        $this->assertEquals(80.00, (float) TutorPayout::where('tutor_id', $tutor->id)->sum('amount'));
        $second->assertSessionHas('error');
    }

    public function test_a_booking_spanning_two_periods_is_paid_only_once(): void
    {
        // Sessions in this month and the next, under one RM80 booking.
        [$admin, $tutor] = $this->paidBooking([
            now()->toDateString(),
            now()->addMonth()->toDateString(),
        ]);

        $this->actingAs($admin)->post('/admin/payouts', [
            'tutor_id' => $tutor->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ]);
        $this->actingAs($admin)->post('/admin/payouts', [
            'tutor_id' => $tutor->id,
            'period_start' => now()->addMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->addMonth()->endOfMonth()->toDateString(),
        ]);

        // Previously RM160 across two ordinary monthly runs.
        $this->assertEquals(80.00, (float) TutorPayout::where('tutor_id', $tutor->id)->sum('amount'));
    }

    public function test_a_payout_claims_its_bookings_and_they_leave_the_unpaid_pool(): void
    {
        [$admin, $tutor, $booking] = $this->paidBooking([now()->toDateString()]);

        $this->actingAs($admin)->post('/admin/payouts', [
            'tutor_id' => $tutor->id,
            'period_start' => now()->subDay()->toDateString(),
            'period_end' => now()->addDay()->toDateString(),
        ]);

        $payout = TutorPayout::where('tutor_id', $tutor->id)->sole();

        // The payout records the slice it paid, and the booking's running
        // total absorbs it so the same money cannot be paid again.
        $this->assertSame(1, $payout->bookings()->count());
        $this->assertEquals(80.00, (float) $payout->bookings()->first()->pivot->amount);
        $this->assertEquals(80.00, (float) $booking->fresh()->paid_out_amount);
        $this->assertEquals(0.0, $booking->fresh()->payableNow());

        $this->actingAs($admin)->get("/admin/payouts/{$payout->id}")->assertOk();
    }

    public function test_claiming_does_not_block_later_unrelated_earnings(): void
    {
        [$admin, $tutor, $firstBooking] = $this->paidBooking([now()->toDateString()]);

        $period = [
            'period_start' => now()->subDay()->toDateString(),
            'period_end' => now()->addDay()->toDateString(),
        ];
        $this->actingAs($admin)->post('/admin/payouts', ['tutor_id' => $tutor->id] + $period);
        $this->assertEquals(80.00, (float) TutorPayout::where('tutor_id', $tutor->id)->sum('amount'));

        // A second paid booking for the same tutor, in the same window.
        $second = $firstBooking->replicate(['paid_out_amount']);
        $second->paid_out_amount = 0;
        $second->save();

        Payment::create([
            'tutor_request_id' => null, 'parent_id' => $firstBooking->parent_id,
            'amount' => 100, 'commission_amount' => 20, 'tutor_payout' => 80,
            'payment_method' => 'fpx', 'status' => 'success', 'paid_at' => now(),
        ])->allocateToBookings(collect([$second]));

        TutorSession::create([
            'booking_id' => $second->id, 'session_date' => now()->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00', 'status' => 'completed',
            'check_in_token' => bin2hex(random_bytes(8)),
        ]);

        $this->actingAs($admin)->post('/admin/payouts', ['tutor_id' => $tutor->id] + $period);

        // The new booking is payable; the already-claimed one is not repaid.
        $this->assertEquals(160.00, (float) TutorPayout::where('tutor_id', $tutor->id)->sum('amount'));
        $this->assertSame(2, TutorPayout::where('tutor_id', $tutor->id)->count());
    }
}
