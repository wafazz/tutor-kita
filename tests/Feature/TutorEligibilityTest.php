<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\User;
use App\Support\TutorEligibility;
use App\Support\TutorMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Eligibility is separate from ranking: a tutor who fails a mandatory
 * requirement is excluded, never merely ranked lower. Softer concerns are
 * reported so an admin can weigh them.
 */
class TutorEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private Student $student;

    private Subject $maths;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parent = User::factory()->parent()->create();
        $this->student = Student::create([
            'parent_id' => $this->parent->id, 'name' => 'Ali', 'age' => 14,
            'latitude' => 3.1073, 'longitude' => 101.6067,
        ]);
        $this->maths = Subject::create(['name' => 'Maths', 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);
    }

    private function tutor(array $profile = [], array $user = []): User
    {
        $tutor = User::factory()->tutor()->create($user + ['name' => 'Cikgu '.uniqid()]);

        TutorProfile::create($profile + [
            'user_id' => $tutor->id, 'subjects' => ['Maths'], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
            'latitude' => 3.1073, 'longitude' => 101.6067,
        ]);

        return $tutor->fresh();
    }

    private function request(array $attrs = []): TutorRequest
    {
        $package = Package::create(['name' => 'P', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1]);

        return TutorRequest::create($attrs + [
            'parent_id' => $this->parent->id, 'student_id' => $this->student->id,
            'subject_id' => $this->maths->id, 'package_id' => $package->id,
            'preferred_area' => 'PJ', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'status' => 'open',
        ]);
    }

    private function assess(User $tutor, TutorRequest $request): array
    {
        return app(TutorEligibility::class)->assess($tutor, $request);
    }

    // ---- 3.2 subject ----

    public function test_a_tutor_who_does_not_teach_the_subject_is_excluded(): void
    {
        $tutor = $this->tutor(['subjects' => ['Physics']]);

        $result = $this->assess($tutor, $this->request());

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('does not teach Maths', implode(' ', $result['blockers']));
    }

    public function test_subject_matching_ignores_case_and_padding(): void
    {
        $tutor = $this->tutor(['subjects' => ['  maths  ']]);

        $this->assertTrue($this->assess($tutor, $this->request())['eligible']);
    }

    public function test_a_tutor_with_no_subjects_recorded_teaches_nothing(): void
    {
        // Silence is not consent: an empty list must not match everything.
        $tutor = $this->tutor(['subjects' => []]);

        $this->assertFalse($this->assess($tutor, $this->request())['eligible']);
    }

    // ---- 3.5 availability ----

    public function test_a_tutor_is_excluded_on_a_day_they_do_not_work(): void
    {
        $tutor = $this->tutor(['availability' => ['Saturday', 'Sunday']]);

        $result = $this->assess($tutor, $this->request(['schedule_day' => 'monday']));

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('not available on Monday', implode(' ', $result['blockers']));
    }

    public function test_a_tutor_is_eligible_on_a_day_they_do_work(): void
    {
        $tutor = $this->tutor(['availability' => ['Saturday', 'Sunday']]);

        $this->assertTrue($this->assess($tutor, $this->request(['schedule_day' => 'saturday']))['eligible']);
    }

    public function test_a_tutor_who_has_not_stated_availability_is_not_excluded_by_it(): void
    {
        // Otherwise every tutor who has not filled it in disappears.
        $tutor = $this->tutor(['availability' => null]);

        $this->assertTrue($this->assess($tutor, $this->request(['schedule_day' => 'monday']))['eligible']);
    }

    // ---- 3.4 gender ----

    public function test_a_stated_gender_preference_excludes_the_other(): void
    {
        $tutor = $this->tutor(user: ['gender' => 'male']);

        $result = $this->assess($tutor, $this->request(['preferred_tutor_gender' => 'female']));

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('female tutor', implode(' ', $result['blockers']));
    }

    public function test_a_matching_gender_is_eligible(): void
    {
        $tutor = $this->tutor(user: ['gender' => 'female']);

        $this->assertTrue($this->assess($tutor, $this->request(['preferred_tutor_gender' => 'female']))['eligible']);
    }

    public function test_an_unknown_gender_warns_rather_than_excludes(): void
    {
        $tutor = $this->tutor(user: ['gender' => null]);

        $result = $this->assess($tutor, $this->request(['preferred_tutor_gender' => 'female']));

        // Not knowing is not the same as not matching.
        $this->assertTrue($result['eligible']);
        $this->assertStringContainsString('cannot be checked', implode(' ', $result['warnings']));
    }

    // ---- 3.3 budget ----

    public function test_being_over_budget_warns_rather_than_excludes(): void
    {
        $tutor = $this->tutor(['hourly_rate' => 80]);

        $result = $this->assess($tutor, $this->request(['budget_max' => 40]));

        // The platform sets the price from the subject, so the tutor's own
        // rate is indicative rather than what the parent pays.
        $this->assertTrue($result['eligible']);
        $this->assertStringContainsString('above the parent', implode(' ', $result['warnings']));
    }

    // ---- 3.1 eligibility before ranking ----

    public function test_an_ineligible_tutor_is_never_ranked_above_an_eligible_one(): void
    {
        // Better rated and more experienced, but teaches the wrong subject.
        $this->tutor(['subjects' => ['Physics'], 'rating_avg' => 5, 'experience_years' => 10]);
        $eligible = $this->tutor(['rating_avg' => 1, 'experience_years' => 0]);

        $assessed = app(TutorMatcher::class)->assessFor($this->request());

        $this->assertSame($eligible->id, $assessed->first()['tutor']->id);
        $this->assertTrue($assessed->first()['eligible']);
        $this->assertFalse($assessed->last()['eligible']);
    }

    public function test_a_busy_tutor_is_excluded_rather_than_ranked_lower(): void
    {
        $busy = $this->tutor();

        Booking::create([
            'tutor_id' => $busy->id, 'parent_id' => $this->parent->id,
            'student_id' => $this->student->id, 'subject_id' => $this->maths->id,
            'schedule_day' => 'monday', 'schedule_time' => '10:00', 'duration_hours' => 2,
            'location_type' => 'home', 'delivery_mode' => DeliveryMode::HomeStudent->value,
            'hourly_rate' => 60, 'commission_rate' => 20, 'status' => 'confirmed',
        ]);

        $assessed = app(TutorMatcher::class)
            ->assessFor($this->request(['schedule_day' => 'monday', 'schedule_time' => '11:00', 'duration_hours' => 2]));

        $row = $assessed->firstWhere('tutor.id', $busy->id);

        $this->assertFalse($row['eligible']);
        $this->assertStringContainsString('already teaching', implode(' ', $row['blockers']));
    }

    // ---- enforced on the action, not only the screen ----

    public function test_the_match_action_refuses_an_ineligible_tutor(): void
    {
        $tutor = $this->tutor(['subjects' => ['Physics']]);
        $request = $this->request();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/requests/{$request->id}/match", ['matched_tutor_id' => $tutor->id])
            ->assertSessionHas('error');

        $this->assertNull($request->fresh()->matched_tutor_id);
    }

    public function test_the_match_action_allows_an_eligible_tutor(): void
    {
        $tutor = $this->tutor();
        $request = $this->request();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/requests/{$request->id}/match", ['matched_tutor_id' => $tutor->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($tutor->id, $request->fresh()->matched_tutor_id);
    }
}
