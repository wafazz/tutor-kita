<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingRulesTest extends TestCase
{
    use RefreshDatabase;

    private function subject(float $home, float $online): Subject
    {
        return Subject::create([
            'name' => 'S'.uniqid(), 'category' => 'academic',
            'hourly_rate_home' => $home, 'hourly_rate_online' => $online, 'is_active' => true,
        ]);
    }

    private function package(int $sessions = 1, float $hours = 2): Package
    {
        return Package::create([
            'name' => 'P', 'package_type' => 'all', 'total_sessions' => $sessions,
            'duration_hours' => $hours, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
        ]);
    }

    private function request(Subject $subject, Package $package, ?string $location, ?User $tutor = null): TutorRequest
    {
        $parent = User::factory()->parent()->create();
        $student = Student::create(['parent_id' => $parent->id, 'name' => 'Kid', 'age' => 15]);

        return TutorRequest::create([
            'parent_id' => $parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'package_id' => $package->id, 'preferred_area' => 'PJ', 'preferred_location' => $location,
            'status' => 'matched', 'matched_tutor_id' => $tutor?->id,
        ]);
    }

    private function tutor(?float $commission = null, ?string $teaches = null): User
    {
        $tutor = User::factory()->tutor()->create();
        $attrs = [
            'user_id' => $tutor->id, 'subjects' => $teaches ? [$teaches] : [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel', 'verification_status' => 'verified',
        ];
        if ($commission !== null) {
            $attrs['commission_rate'] = $commission;
        }
        TutorProfile::create($attrs);

        return $tutor->fresh();
    }

    // ---- 3. online bookings must never price at zero ----

    public function test_an_online_booking_falls_back_to_the_home_rate_when_no_online_rate_is_set(): void
    {
        $request = $this->request($this->subject(home: 60, online: 0), $this->package(), 'online');

        // Previously RM0: parent charged nothing, tutor earned nothing.
        $this->assertSame(120.0, $request->calculateAmount());
    }

    public function test_a_real_online_rate_is_still_honoured(): void
    {
        $request = $this->request($this->subject(home: 60, online: 45), $this->package(), 'online');

        $this->assertSame(90.0, $request->calculateAmount());
    }

    public function test_approval_refuses_to_raise_a_zero_amount_payment(): void
    {
        $tutor = $this->tutor();
        $request = $this->request($this->subject(home: 0, online: 0), $this->package(), 'home', $tutor);

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/requests/{$request->id}/match", ['matched_tutor_id' => $tutor->id])
            ->assertSessionHas('error');

        $this->assertSame(0, Payment::where('tutor_request_id', $request->id)->count());
    }

    // ---- 1. the global commission setting must actually govern ----

    public function test_a_new_tutor_inherits_the_platform_commission_rate(): void
    {
        Setting::set('commission_rate', '35');

        $this->post('/register/tutor', [
            'name' => 'New Tutor', 'email' => 'new@tutor.test',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        $profile = User::where('email', 'new@tutor.test')->sole()->tutorProfile;

        $this->assertEquals(35.0, (float) $profile->commission_rate);
    }

    public function test_the_platform_rate_is_used_when_a_tutor_has_no_profile(): void
    {
        Setting::set('commission_rate', '42');

        $tutorWithoutProfile = User::factory()->tutor()->create();
        $request = $this->request($this->subject(home: 50, online: 50), $this->package(sessions: 1, hours: 1), 'home', $tutorWithoutProfile);

        $split = $request->calculateSplit($tutorWithoutProfile);

        $this->assertEquals(42.0, $split['commission_rate']);
        $this->assertEquals(21.0, $split['commission_amount']);
        $this->assertEquals(29.0, $split['tutor_payout']);
    }

    public function test_an_existing_tutor_keeps_their_own_rate_over_the_platform_default(): void
    {
        Setting::set('commission_rate', '35');

        $tutor = $this->tutor(commission: 60);
        $request = $this->request($this->subject(home: 50, online: 50), $this->package(sessions: 1, hours: 1), 'home', $tutor);

        // Changing the platform default must not reprice existing tutors.
        $this->assertEquals(60.0, $request->calculateSplit($tutor)['commission_rate']);
    }

    // ---- 2. settled money must not be silently restated ----

    public function test_re_approving_does_not_rewrite_a_payment_the_parent_already_settled(): void
    {
        $subject = $this->subject(home: 60, online: 60);
        $tutor = $this->tutor(commission: 20, teaches: $subject->name);
        $package = $this->package(sessions: 1, hours: 2);
        $request = $this->request($subject, $package, 'home', $tutor);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post("/admin/requests/{$request->id}/match", ['matched_tutor_id' => $tutor->id]);

        $payment = Payment::where('tutor_request_id', $request->id)->sole();
        $this->assertEquals(120.00, (float) $payment->amount);

        // Parent pays, then the subject is repriced and the request re-approved.
        $payment->update(['status' => 'success', 'paid_at' => now()]);
        $subject->update(['hourly_rate_home' => 200]);

        $this->actingAs($admin)->post("/admin/requests/{$request->id}/match", ['matched_tutor_id' => $tutor->id]);

        $this->assertEquals(120.00, (float) $payment->fresh()->amount, 'settled money was restated');
        $this->assertSame('success', $payment->fresh()->status);
    }

    public function test_a_still_pending_payment_is_repriced_on_re_approval(): void
    {
        $subject = $this->subject(home: 60, online: 60);
        $tutor = $this->tutor(commission: 20, teaches: $subject->name);
        $request = $this->request($subject, $this->package(sessions: 1, hours: 2), 'home', $tutor);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post("/admin/requests/{$request->id}/match", ['matched_tutor_id' => $tutor->id]);

        $subject->update(['hourly_rate_home' => 80]);
        $this->actingAs($admin)->post("/admin/requests/{$request->id}/match", ['matched_tutor_id' => $tutor->id]);

        $this->assertEquals(160.00, (float) Payment::where('tutor_request_id', $request->id)->sole()->amount);
    }
}
