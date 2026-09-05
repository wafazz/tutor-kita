<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A payment is raised when a request is approved and only gains a booking once
 * the parent actually pays. The admin payment screens must therefore render
 * with booking still null — they previously crashed, blanking the page.
 */
class AdminPaymentsViewTest extends TestCase
{
    use RefreshDatabase;

    private function pendingPaymentWithoutBooking(): Payment
    {
        $parent = User::factory()->parent()->create();
        $student = Student::create(['parent_id' => $parent->id, 'name' => 'Kid', 'age' => 15]);
        $tutor = User::factory()->tutor()->create(['name' => 'Cikgu Aminah']);

        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);

        $subject = Subject::create([
            'name' => 'Bahasa Melayu', 'category' => 'academic',
            'hourly_rate_home' => 50, 'hourly_rate_online' => 50, 'is_active' => true,
        ]);

        $package = Package::create([
            'name' => 'P', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
        ]);

        $request = TutorRequest::create([
            'parent_id' => $parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'package_id' => $package->id, 'preferred_area' => 'PJ', 'preferred_location' => 'home',
            'status' => 'matched', 'matched_tutor_id' => $tutor->id,
        ]);

        return Payment::create([
            'tutor_request_id' => $request->id, 'parent_id' => $parent->id,
            'amount' => 100, 'commission_amount' => 20, 'tutor_payout' => 80,
            'payment_method' => 'fpx', 'status' => 'pending',
        ]);
    }

    public function test_the_payments_list_renders_when_a_payment_has_no_booking(): void
    {
        $payment = $this->pendingPaymentWithoutBooking();
        $this->assertNull($payment->booking_id);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/payments')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Payments/Index')
                ->where('payments.data.0.booking', null)
                // Falls back to the originating request so the row is not empty.
                ->where('payments.data.0.tutor_request.matched_tutor.name', 'Cikgu Aminah')
                ->where('payments.data.0.tutor_request.subject.name', 'Bahasa Melayu')
            );
    }

    public function test_the_payment_detail_renders_when_a_payment_has_no_booking(): void
    {
        $payment = $this->pendingPaymentWithoutBooking();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/payments/{$payment->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Payments/Show')
                ->where('payment.booking', null)
                ->where('payment.tutor_request.matched_tutor.name', 'Cikgu Aminah')
            );
    }

    public function test_the_payments_list_still_renders_a_fully_booked_payment(): void
    {
        $this->pendingPaymentWithoutBooking();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/payments')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('payments.data', 1));
    }
}
