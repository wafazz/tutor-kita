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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A grouped request raises ONE payment covering several tutors. Each tutor must
 * be paid only their own share — previously the whole group total was credited
 * to whichever tutor the payment happened to link to.
 */
class GroupPayoutAttributionTest extends TestCase
{
    use RefreshDatabase;

    private function makeTutor(string $name, float $commissionRate, float $hourlyRate = 50): User
    {
        $tutor = User::factory()->tutor()->create(['name' => $name]);

        TutorProfile::create([
            'user_id' => $tutor->id,
            'subjects' => [],
            'hourly_rate' => $hourlyRate,
            'location_area' => 'PJ',
            'location_state' => 'Selangor',
            'verification_status' => 'verified',
            'commission_rate' => $commissionRate,
        ]);

        return $tutor->fresh();
    }

    private function makeSubject(float $homeRate): Subject
    {
        return Subject::create([
            'name' => 'Subject '.uniqid(),
            'category' => 'academic',
            'education_level' => 'SPM',
            'hourly_rate_home' => $homeRate,
            'hourly_rate_online' => $homeRate,
            'is_active' => true,
        ]);
    }

    public function test_group_payment_is_split_across_tutors_by_their_own_share(): void
    {
        $parent = User::factory()->parent()->create();
        $student = Student::create(['parent_id' => $parent->id, 'name' => 'Kid', 'age' => 15]);

        // Two tutors, two subjects priced differently, one grouped request.
        $tutorA = $this->makeTutor('Tutor A', 20);   // RM100 gross -> RM80 payout
        $tutorB = $this->makeTutor('Tutor B', 50);   // RM200 gross -> RM100 payout

        $subjectA = $this->makeSubject(50);
        $subjectB = $this->makeSubject(100);

        $package = Package::create([
            'name' => 'Std', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
        ]);

        $group = 'grp-test-1';

        $reqA = TutorRequest::create([
            'request_group' => $group, 'parent_id' => $parent->id, 'student_id' => $student->id,
            'subject_id' => $subjectA->id, 'package_id' => $package->id, 'preferred_area' => 'PJ', 'preferred_location' => 'home',
            'status' => 'matched', 'matched_tutor_id' => $tutorA->id,
        ]);
        $reqB = TutorRequest::create([
            'request_group' => $group, 'parent_id' => $parent->id, 'student_id' => $student->id,
            'subject_id' => $subjectB->id, 'package_id' => $package->id, 'preferred_area' => 'PJ', 'preferred_location' => 'home',
            'status' => 'matched', 'matched_tutor_id' => $tutorB->id,
        ]);

        // Sanity: the per-request prices the split is weighted by.
        $this->assertSame(100.0, $reqA->calculateAmount()); // 50 x 2hr x 1
        $this->assertSame(200.0, $reqB->calculateAmount()); // 100 x 2hr x 1

        // One payment for the whole group, as createGroupPayment() raises it.
        $payment = Payment::create([
            'tutor_request_id' => $reqA->id,
            'parent_id' => $parent->id,
            'amount' => 300.00,
            'commission_amount' => 120.00,  // 20% of 100 + 50% of 200
            'tutor_payout' => 180.00,       // 80 + 100
            'payment_method' => 'fpx',
            'status' => 'success',
            'paid_at' => now(),
        ]);

        foreach ([[$reqA, $tutorA], [$reqB, $tutorB]] as [$req, $tutor]) {
            Booking::create([
                'tutor_request_id' => $req->id, 'tutor_id' => $tutor->id,
                'parent_id' => $parent->id, 'student_id' => $student->id,
                'subject_id' => $req->subject_id, 'duration_hours' => 2,
                'schedule_day' => 'monday', 'schedule_time' => '10:00', 'location_type' => 'home',
                'hourly_rate' => 50, 'commission_rate' => $tutor->tutorProfile->commission_rate,
                'status' => 'confirmed',
            ]);
        }

        $payment->allocateToBookings();

        $bookingA = Booking::where('tutor_id', $tutorA->id)->sole();
        $bookingB = Booking::where('tutor_id', $tutorB->id)->sole();

        // Weighted 100:200 against the recorded totals.
        $this->assertEquals(100.00, (float) $bookingA->amount);
        $this->assertEquals(200.00, (float) $bookingB->amount);
        $this->assertEquals(60.00, (float) $bookingA->tutor_payout);
        $this->assertEquals(120.00, (float) $bookingB->tutor_payout);

        // The shares must always sum back to what the parent was charged.
        $this->assertEquals(
            (float) $payment->tutor_payout,
            (float) $bookingA->tutor_payout + (float) $bookingB->tutor_payout
        );
        $this->assertEquals(
            (float) $payment->amount,
            (float) $bookingA->amount + (float) $bookingB->amount
        );

        // Every booking in the group is linked to the settling payment, not
        // just the first one.
        $this->assertSame($payment->id, $bookingA->payment_id);
        $this->assertSame($payment->id, $bookingB->payment_id);
    }

    public function test_each_tutor_earnings_show_only_their_own_share(): void
    {
        $this->test_group_payment_is_split_across_tutors_by_their_own_share();

        $tutorA = User::where('name', 'Tutor A')->sole();
        $tutorB = User::where('name', 'Tutor B')->sole();

        $earnedBy = fn (User $t) => (float) Booking::where('tutor_id', $t->id)
            ->whereHas('payment', fn ($q) => $q->where('status', 'success'))
            ->sum('tutor_payout');

        // Before the fix both tutors resolved to the full 180 (or 0).
        $this->assertEquals(60.00, $earnedBy($tutorA));
        $this->assertEquals(120.00, $earnedBy($tutorB));

        $this->actingAs($tutorA)->get('/tutor/earnings')->assertOk();
    }

    public function test_rounding_remainder_never_loses_or_invents_money(): void
    {
        $parent = User::factory()->parent()->create();
        $student = Student::create(['parent_id' => $parent->id, 'name' => 'Kid', 'age' => 15]);

        $tutors = [$this->makeTutor('R1', 20), $this->makeTutor('R2', 20), $this->makeTutor('R3', 20)];
        $subject = $this->makeSubject(10);
        $package = Package::create([
            'name' => 'P', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 1, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
        ]);

        // 100.00 split three equal ways cannot divide cleanly.
        $payment = Payment::create([
            'tutor_request_id' => null, 'parent_id' => $parent->id,
            'amount' => 100.00, 'commission_amount' => 0, 'tutor_payout' => 100.00,
            'payment_method' => 'manual', 'status' => 'success', 'paid_at' => now(),
        ]);

        $bookings = collect($tutors)->map(function ($tutor) use ($parent, $student, $subject, $package) {
            $req = TutorRequest::create([
                'request_group' => 'grp-round', 'parent_id' => $parent->id, 'student_id' => $student->id,
                'subject_id' => $subject->id, 'package_id' => $package->id, 'preferred_area' => 'PJ', 'preferred_location' => 'home',
                'status' => 'matched', 'matched_tutor_id' => $tutor->id,
            ]);

            return Booking::create([
                'tutor_request_id' => $req->id, 'tutor_id' => $tutor->id,
                'parent_id' => $parent->id, 'student_id' => $student->id,
                'subject_id' => $subject->id, 'duration_hours' => 1,
                'schedule_day' => 'monday', 'schedule_time' => '10:00', 'location_type' => 'home',
                'hourly_rate' => 10, 'commission_rate' => 20, 'status' => 'confirmed',
            ]);
        });

        $payment->allocateToBookings($bookings);

        $total = Booking::whereIn('id', $bookings->pluck('id'))->sum('tutor_payout');

        $this->assertEquals(100.00, (float) $total, 'shares must sum back to the recorded payout');
    }

    public function test_admin_payout_screens_render_with_per_booking_breakdown(): void
    {
        $this->test_group_payment_is_split_across_tutors_by_their_own_share();

        $admin = User::factory()->admin()->create();
        $tutorB = User::where('name', 'Tutor B')->sole();

        // Create screen lists tutors with an outstanding balance.
        $this->actingAs($admin)->get('/admin/payouts/create')->assertOk();

        $payout = TutorPayout::create([
            'tutor_id' => $tutorB->id,
            'amount' => 120.00,
            'sessions_count' => 1,
            'period_start' => now()->subMonth()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->get("/admin/payouts/{$payout->id}")->assertOk();
        $this->actingAs($admin)->get('/admin/payouts')->assertOk();
    }
}
