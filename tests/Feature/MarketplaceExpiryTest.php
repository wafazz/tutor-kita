<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Enums\GroupPayoutModel;
use App\Models\Booking;
use App\Models\ClassSession;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\User;
use App\Support\ClassEnroller;
use App\Support\MarketplaceExpiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A matched tutor is unavailable to everyone else while they decide, and a
 * held seat is unavailable while its payment is outstanding. Without a limit
 * one parent who never pays keeps a tutor out of the market indefinitely.
 */
class MarketplaceExpiryTest extends TestCase
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

        $this->tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $this->tutor->id, 'subjects' => ['Maths'], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);

        $this->parent = User::factory()->parent()->create();
        $this->student = Student::create(['parent_id' => $this->parent->id, 'name' => 'Ali', 'age' => 14]);
        $this->subject = Subject::create(['name' => 'Maths', 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);
        $this->package = Package::create(['name' => 'P', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1]);
    }

    private function matchedRequest(int $matchedHoursAgo): TutorRequest
    {
        $request = TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $this->student->id,
            'subject_id' => $this->subject->id, 'package_id' => $this->package->id,
            'preferred_area' => 'PJ', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'status' => 'matched', 'matched_tutor_id' => $this->tutor->id,
            'tutor_accepted' => false,
        ]);

        $request->forceFill(['matched_at' => now()->subHours($matchedHoursAgo)])->save();

        return $request->fresh();
    }

    private function pendingPayment(TutorRequest $request, int $raisedHoursAgo): Payment
    {
        $payment = Payment::create([
            'tutor_request_id' => $request->id, 'parent_id' => $this->parent->id,
            'amount' => 120, 'commission_amount' => 24, 'tutor_payout' => 96,
            'payment_method' => 'fpx', 'status' => 'pending',
        ]);

        $payment->forceFill(['created_at' => now()->subHours($raisedHoursAgo)])->save();

        return $payment->fresh();
    }

    // ---- tutor acceptance expiry ----

    public function test_a_tutor_who_never_answers_releases_the_request(): void
    {
        $request = $this->matchedRequest(matchedHoursAgo: 30);

        $this->assertSame(1, app(MarketplaceExpiry::class)->expireUnacceptedMatches());

        $fresh = $request->fresh();

        // Released, reopened, and ready to match another tutor.
        $this->assertSame('open', $fresh->status);
        $this->assertNull($fresh->matched_tutor_id);
        $this->assertNull($fresh->matched_at);
    }

    public function test_a_tutor_still_inside_the_window_is_left_alone(): void
    {
        $request = $this->matchedRequest(matchedHoursAgo: 2);

        $this->assertSame(0, app(MarketplaceExpiry::class)->expireUnacceptedMatches());
        $this->assertSame('matched', $request->fresh()->status);
    }

    public function test_the_window_is_configurable(): void
    {
        Setting::set('acceptance_expiry_hours', '1');
        $request = $this->matchedRequest(matchedHoursAgo: 2);

        $this->assertSame(1, app(MarketplaceExpiry::class)->expireUnacceptedMatches());
        $this->assertSame('open', $request->fresh()->status);
    }

    public function test_an_accepted_job_is_never_released(): void
    {
        $request = $this->matchedRequest(matchedHoursAgo: 100);
        $request->update(['tutor_accepted' => true]);

        $this->assertSame(0, app(MarketplaceExpiry::class)->expireUnacceptedMatches());
        $this->assertSame($this->tutor->id, $request->fresh()->matched_tutor_id);
    }

    public function test_a_request_already_paid_for_is_never_released(): void
    {
        $request = $this->matchedRequest(matchedHoursAgo: 100);
        $this->pendingPayment($request, 100)->update(['status' => 'success', 'paid_at' => now()]);

        $this->assertSame(0, app(MarketplaceExpiry::class)->expireUnacceptedMatches());
        $this->assertSame('matched', $request->fresh()->status);
    }

    public function test_releasing_a_match_expires_the_invoice_raised_for_it(): void
    {
        $request = $this->matchedRequest(matchedHoursAgo: 30);
        $payment = $this->pendingPayment($request, 30);

        app(MarketplaceExpiry::class)->expireUnacceptedMatches();

        $this->assertSame('expired', $payment->fresh()->status);
    }

    // ---- payment expiry ----

    public function test_an_unpaid_invoice_expires_and_reopens_the_request(): void
    {
        $request = $this->matchedRequest(matchedHoursAgo: 1);
        $request->update(['tutor_accepted' => true]);
        $payment = $this->pendingPayment($request, raisedHoursAgo: 60);

        $this->assertSame(1, app(MarketplaceExpiry::class)->expireUnpaidPayments());

        $this->assertSame('expired', $payment->fresh()->status);
        $this->assertSame('open', $request->fresh()->status);
        $this->assertNull($request->fresh()->matched_tutor_id);
    }

    public function test_a_paid_invoice_is_never_expired(): void
    {
        $request = $this->matchedRequest(matchedHoursAgo: 1);
        $payment = $this->pendingPayment($request, 100);
        $payment->update(['status' => 'success', 'paid_at' => now()]);

        $this->assertSame(0, app(MarketplaceExpiry::class)->expireUnpaidPayments());
        $this->assertSame('success', $payment->fresh()->status);
    }

    public function test_an_invoice_with_a_booking_is_never_expired(): void
    {
        // A booking means the work is underway, whatever the payment says.
        $request = $this->matchedRequest(matchedHoursAgo: 1);
        $payment = $this->pendingPayment($request, 100);

        $booking = Booking::create([
            'tutor_request_id' => $request->id, 'tutor_id' => $this->tutor->id,
            'parent_id' => $this->parent->id, 'student_id' => $this->student->id,
            'subject_id' => $this->subject->id, 'schedule_day' => 'monday', 'schedule_time' => '10:00',
            'duration_hours' => 2, 'location_type' => 'home', 'hourly_rate' => 50,
            'commission_rate' => 20, 'status' => 'confirmed',
        ]);
        $payment->update(['booking_id' => $booking->id]);

        $this->assertSame(0, app(MarketplaceExpiry::class)->expireUnpaidPayments());
    }

    // ---- held class seats ----

    public function test_an_unpaid_class_seat_is_released_back_to_the_class(): void
    {
        $class = ClassSession::create([
            'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'delivery_mode' => DeliveryMode::OnlineGroup->value, 'title' => 'C',
            'schedule_day' => 'saturday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'total_sessions' => 1, 'capacity' => 2, 'price_per_student' => 30,
            'payout_model' => GroupPayoutModel::PerStudent->value, 'status' => 'open',
            'commission_rate' => 20,
        ]);

        $enrolment = (new ClassEnroller)->enrol($class, $this->student);
        $enrolment->forceFill(['created_at' => now()->subHours(60)])->save();

        $this->assertSame(1, $class->fresh()->seatsTaken());

        $this->assertSame(1, app(MarketplaceExpiry::class)->releaseUnpaidSeats());

        // The seat is back, and available to someone who will pay for it.
        $this->assertSame('cancelled', $enrolment->fresh()->status);
        $this->assertSame(0, $class->fresh()->seatsTaken());
        $this->assertSame(2, $class->fresh()->seatsLeft());
    }

    public function test_a_paid_seat_is_never_released(): void
    {
        $class = ClassSession::create([
            'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'delivery_mode' => DeliveryMode::OnlineGroup->value, 'title' => 'C',
            'schedule_day' => 'saturday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'total_sessions' => 1, 'capacity' => 2, 'price_per_student' => 30,
            'payout_model' => GroupPayoutModel::PerStudent->value, 'status' => 'open',
            'commission_rate' => 20,
        ]);

        $enrolment = (new ClassEnroller)->enrol($class, $this->student);
        $enrolment->forceFill(['created_at' => now()->subHours(60)])->save();
        $enrolment->payment->update(['status' => 'success', 'paid_at' => now()]);
        $enrolment->update(['status' => 'active']);

        $this->assertSame(0, app(MarketplaceExpiry::class)->releaseUnpaidSeats());
        $this->assertSame(1, $class->fresh()->seatsTaken());
    }

    // ---- the command ----

    public function test_the_command_reports_what_it_released(): void
    {
        $this->matchedRequest(matchedHoursAgo: 30);

        $this->artisan('marketplace:expire')
            ->expectsOutputToContain('1 request(s) reopened')
            ->assertSuccessful();
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        $request = $this->matchedRequest(matchedHoursAgo: 30);

        $this->artisan('marketplace:expire --dry-run')->assertSuccessful();

        $this->assertSame('matched', $request->fresh()->status);
    }
}
