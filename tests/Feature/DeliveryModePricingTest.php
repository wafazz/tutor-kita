<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Models\Package;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectRate;
use App\Models\TutorRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Five delivery modes priced from a rate table, so a new mode or a new price is
 * data rather than a migration.
 */
class DeliveryModePricingTest extends TestCase
{
    use RefreshDatabase;

    private function subject(float $home = 60, float $online = 50): Subject
    {
        return Subject::create([
            'name' => 'S'.uniqid(), 'category' => 'academic',
            'hourly_rate_home' => $home, 'hourly_rate_online' => $online, 'is_active' => true,
        ]);
    }

    private function request(Subject $subject, DeliveryMode $mode, float $hours = 2, int $sessions = 1): TutorRequest
    {
        $parent = User::factory()->parent()->create();
        $student = Student::create(['parent_id' => $parent->id, 'name' => 'Kid', 'age' => 15]);
        $package = Package::create([
            'name' => 'P', 'package_type' => 'all', 'total_sessions' => $sessions,
            'duration_hours' => $hours, 'price' => 0, 'is_active' => true, 'sort_order' => 1,
        ]);

        return TutorRequest::create([
            'parent_id' => $parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'package_id' => $package->id, 'preferred_area' => 'PJ', 'delivery_mode' => $mode->value,
            'status' => 'open',
        ]);
    }

    public function test_every_mode_prices_from_its_own_rate_when_one_is_set(): void
    {
        $subject = $this->subject();

        $rates = [
            DeliveryMode::HomeStudent->value => 70,
            DeliveryMode::HomeTutor->value => 55,
            DeliveryMode::CentreGroup->value => 25,
            DeliveryMode::OnlineSolo->value => 50,
            DeliveryMode::OnlineGroup->value => 20,
        ];

        foreach ($rates as $mode => $rate) {
            SubjectRate::create([
                'subject_id' => $subject->id, 'delivery_mode' => $mode,
                'hourly_rate' => $rate, 'is_active' => true,
            ]);
        }

        foreach ($rates as $mode => $rate) {
            $request = $this->request($subject, DeliveryMode::from($mode));
            $this->assertSame($rate * 2.0, $request->calculateAmount(), "{$mode} mispriced");
        }
    }

    public function test_an_unpriced_mode_inherits_rather_than_dropping_to_zero(): void
    {
        $subject = $this->subject();
        SubjectRate::create([
            'subject_id' => $subject->id, 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'hourly_rate' => 70, 'is_active' => true,
        ]);

        // Neither has a rate of its own; both inherit from home_student.
        $this->assertSame(70.0, $subject->rateFor(DeliveryMode::HomeTutor));
        $this->assertSame(70.0, $subject->rateFor(DeliveryMode::CentreGroup));

        // ...and the screens can tell that it was inherited, not chosen.
        $this->assertTrue($subject->hasOwnRateFor(DeliveryMode::HomeStudent));
        $this->assertFalse($subject->hasOwnRateFor(DeliveryMode::CentreGroup));
    }

    public function test_a_subject_with_no_rate_rows_still_prices_off_the_legacy_columns(): void
    {
        $subject = $this->subject(home: 60, online: 45);

        $this->assertSame(60.0, $subject->rateFor(DeliveryMode::HomeStudent));
        $this->assertSame(45.0, $subject->rateFor(DeliveryMode::OnlineSolo));
        $this->assertSame(120.0, $this->request($subject, DeliveryMode::HomeStudent)->calculateAmount());
    }

    public function test_a_subject_priced_nowhere_reports_no_rate_rather_than_free(): void
    {
        $subject = $this->subject(home: 0, online: 0);

        $this->assertNull($subject->rateFor(DeliveryMode::HomeStudent));
        $this->assertSame(0.0, $this->request($subject, DeliveryMode::HomeStudent)->calculateAmount());
    }

    public function test_an_inactive_rate_is_ignored(): void
    {
        $subject = $this->subject(home: 0, online: 0);
        SubjectRate::create([
            'subject_id' => $subject->id, 'delivery_mode' => DeliveryMode::OnlineSolo->value,
            'hourly_rate' => 99, 'is_active' => false,
        ]);

        $this->assertNull($subject->rateFor(DeliveryMode::OnlineSolo));
    }

    public function test_legacy_rows_without_a_delivery_mode_still_resolve(): void
    {
        $subject = $this->subject();
        $request = $this->request($subject, DeliveryMode::HomeStudent);
        $request->forceFill(['delivery_mode' => null, 'preferred_location' => 'online'])->save();

        $this->assertSame(DeliveryMode::OnlineSolo, $request->fresh()->deliveryMode());
    }

    public function test_modes_declare_who_travels_and_which_need_geography(): void
    {
        $this->assertSame('tutor', DeliveryMode::HomeStudent->traveller());
        $this->assertSame('student', DeliveryMode::HomeTutor->traveller());
        $this->assertSame('student', DeliveryMode::CentreGroup->traveller());
        $this->assertNull(DeliveryMode::OnlineSolo->traveller());

        $this->assertTrue(DeliveryMode::CentreGroup->needsGeo());
        $this->assertFalse(DeliveryMode::OnlineGroup->needsGeo());

        $this->assertTrue(DeliveryMode::OnlineGroup->isGroup());
        $this->assertFalse(DeliveryMode::OnlineSolo->isGroup());
    }
}
