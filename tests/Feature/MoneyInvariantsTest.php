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
 * Properties the money model must hold no matter what path produced the data.
 *
 * These are deliberately asserted over a messy scenario — a grouped request
 * across tutors on different commission rates, partial accrual, and several
 * payout runs — rather than a single tidy booking.
 */
class MoneyInvariantsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private array $tutors = [];

    /** Build a group of 3 requests across 3 tutors under one payment. */
    private function messyScenario(): Payment
    {
        $parent = User::factory()->parent()->create();
        $student = Student::create(['parent_id' => $parent->id, 'name' => 'Kid', 'age' => 15]);

        $package = Package::create([
            'name' => 'Multi', 'package_type' => 'all', 'total_sessions' => 3,
            'duration_hours' => 1.5, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
            'payout_policy' => 'per_session',
        ]);

        // Deliberately awkward: rates that do not divide cleanly.
        $specs = [[20.0, 33.0], [37.5, 41.0], [50.0, 29.0]];
        $requests = [];

        foreach ($specs as $i => [$commission, $rate]) {
            $tutor = User::factory()->tutor()->create(['name' => "T{$i}"]);
            TutorProfile::create([
                'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => $rate,
                'location_area' => 'PJ', 'location_state' => 'Sel',
                'verification_status' => 'verified', 'commission_rate' => $commission,
            ]);
            $this->tutors[] = $tutor;

            $subject = Subject::create([
                'name' => "S{$i}".uniqid(), 'category' => 'academic',
                'hourly_rate_home' => $rate, 'hourly_rate_online' => $rate, 'is_active' => true,
            ]);

            $requests[] = TutorRequest::create([
                'request_group' => 'grp-messy', 'parent_id' => $parent->id, 'student_id' => $student->id,
                'subject_id' => $subject->id, 'package_id' => $package->id,
                'preferred_area' => 'PJ', 'preferred_location' => 'home',
                'status' => 'matched', 'matched_tutor_id' => $tutor->id,
            ]);
        }

        // One payment covering the group, split by each tutor's own rate.
        $gross = 0.0;
        $commission = 0.0;
        foreach ($requests as $i => $req) {
            $amount = $req->calculateAmount();
            $gross += $amount;
            $commission += $amount * ($specs[$i][0] / 100);
        }

        $payment = Payment::create([
            'tutor_request_id' => $requests[0]->id, 'parent_id' => $parent->id,
            'amount' => round($gross, 2),
            'commission_amount' => round($commission, 2),
            'tutor_payout' => round($gross - $commission, 2),
            'payment_method' => 'fpx', 'status' => 'success', 'paid_at' => now(),
        ]);

        foreach ($requests as $i => $req) {
            Booking::create([
                'tutor_request_id' => $req->id, 'tutor_id' => $this->tutors[$i]->id,
                'parent_id' => $parent->id, 'student_id' => $student->id,
                'subject_id' => $req->subject_id, 'duration_hours' => 1.5,
                'schedule_day' => 'monday', 'schedule_time' => '10:00', 'location_type' => 'home',
                'hourly_rate' => $specs[$i][1], 'commission_rate' => $specs[$i][0],
                'status' => 'confirmed',
            ]);
        }

        $payment->allocateToBookings();
        $this->admin = User::factory()->admin()->create();

        return $payment->fresh();
    }

    private function completeSessions(Booking $booking, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            TutorSession::create([
                'booking_id' => $booking->id, 'session_date' => now()->toDateString(),
                'start_time' => '10:00', 'end_time' => '11:30', 'status' => 'completed',
                'check_in_token' => bin2hex(random_bytes(8)),
            ]);
        }
    }

    public function test_shares_always_reconcile_to_the_recorded_payment(): void
    {
        $payment = $this->messyScenario();
        $bookings = $payment->relatedBookings();

        $this->assertCount(3, $bookings);
        $this->assertEqualsWithDelta((float) $payment->amount, $bookings->sum(fn ($b) => (float) $b->amount), 0.001);
        $this->assertEqualsWithDelta((float) $payment->tutor_payout, $bookings->sum(fn ($b) => (float) $b->tutor_payout), 0.001);
        $this->assertEqualsWithDelta((float) $payment->commission_amount, $bookings->sum(fn ($b) => (float) $b->commission_amount), 0.001);
    }

    public function test_every_booking_splits_into_commission_plus_payout(): void
    {
        $payment = $this->messyScenario();

        foreach ($payment->relatedBookings() as $booking) {
            $this->assertEqualsWithDelta(
                (float) $booking->amount,
                (float) $booking->commission_amount + (float) $booking->tutor_payout,
                0.001,
                "booking {$booking->id} does not split cleanly"
            );
        }
    }

    public function test_accrual_never_exceeds_the_booking_payout(): void
    {
        $payment = $this->messyScenario();

        foreach ($payment->relatedBookings() as $booking) {
            // Far more sessions than the package holds.
            $this->completeSessions($booking, 9);
            $fresh = $booking->fresh();

            $this->assertLessThanOrEqual((float) $fresh->tutor_payout + 0.001, $fresh->accruedPayout());
        }
    }

    public function test_no_tutor_is_ever_paid_more_than_they_accrued(): void
    {
        $payment = $this->messyScenario();
        $period = [
            'period_start' => now()->subDay()->toDateString(),
            'period_end' => now()->addDay()->toDateString(),
        ];

        // Accrue in stages, running a payout after each — the shape that
        // previously double-paid.
        foreach ([1, 1, 1] as $batch) {
            foreach ($payment->relatedBookings() as $booking) {
                $this->completeSessions($booking, $batch);
            }
            foreach ($this->tutors as $tutor) {
                $this->actingAs($this->admin)->post('/admin/payouts', ['tutor_id' => $tutor->id] + $period);
            }
        }

        foreach ($this->tutors as $tutor) {
            $accrued = Booking::where('tutor_id', $tutor->id)
                ->with(['payment', 'tutorRequest.package', 'sessions'])->get()
                ->sum(fn ($b) => $b->accruedPayout());
            $paid = (float) TutorPayout::where('tutor_id', $tutor->id)->sum('amount');

            $this->assertLessThanOrEqual($accrued + 0.001, $paid, "tutor {$tutor->id} was overpaid");
        }
    }

    public function test_each_payout_equals_the_sum_of_the_slices_it_recorded(): void
    {
        $payment = $this->messyScenario();
        foreach ($payment->relatedBookings() as $booking) {
            $this->completeSessions($booking, 2);
        }

        foreach ($this->tutors as $tutor) {
            $this->actingAs($this->admin)->post('/admin/payouts', [
                'tutor_id' => $tutor->id,
                'period_start' => now()->subDay()->toDateString(),
                'period_end' => now()->addDay()->toDateString(),
            ]);
        }

        $this->assertGreaterThan(0, TutorPayout::count());

        foreach (TutorPayout::with('bookings')->get() as $payout) {
            $this->assertEqualsWithDelta(
                (float) $payout->amount,
                $payout->bookings->sum(fn ($b) => (float) $b->pivot->amount),
                0.001,
                "payout {$payout->id} does not reconcile with its slices"
            );
        }
    }

    public function test_money_is_never_paid_against_an_unsuccessful_payment(): void
    {
        $payment = $this->messyScenario();
        $payment->update(['status' => 'pending']);

        foreach ($payment->relatedBookings() as $booking) {
            $this->completeSessions($booking, 3);
            $this->assertSame(0.0, $booking->fresh()->accruedPayout());
        }

        foreach ($this->tutors as $tutor) {
            $this->actingAs($this->admin)->post('/admin/payouts', [
                'tutor_id' => $tutor->id,
                'period_start' => now()->subDay()->toDateString(),
                'period_end' => now()->addDay()->toDateString(),
            ]);
        }

        $this->assertSame(0, TutorPayout::count());
    }
}
