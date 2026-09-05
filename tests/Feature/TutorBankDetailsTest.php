<?php

namespace Tests\Feature;

use App\Models\TutorPayout;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Payouts were recordable but not executable — nothing held a destination
 * account. Tutors supply their own; admins read them to make the transfer.
 */
class TutorBankDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function tutorWithProfile(array $bank = []): User
    {
        $tutor = User::factory()->tutor()->create();

        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ] + $bank);

        return $tutor->fresh();
    }

    public function test_a_tutor_can_save_their_payout_details(): void
    {
        $tutor = $this->tutorWithProfile();

        $this->actingAs($tutor)->put('/tutor/profile', [
            'bank_name' => 'Maybank',
            'bank_account_number' => '514012345678',
            'bank_account_name' => 'Ahmad bin Ali',
        ])->assertSessionHasNoErrors();

        $profile = $tutor->tutorProfile->fresh();

        $this->assertSame('Maybank', $profile->bank_name);
        $this->assertSame('514012345678', $profile->bank_account_number);
        $this->assertTrue($profile->hasBankDetails());
    }

    public function test_the_account_number_is_encrypted_at_rest(): void
    {
        $tutor = $this->tutorWithProfile([
            'bank_name' => 'CIMB',
            'bank_account_number' => '7001234567',
            'bank_account_name' => 'Siti binti Omar',
        ]);

        $stored = DB::table('tutor_profiles')
            ->where('user_id', $tutor->id)
            ->value('bank_account_number');

        // A database dump must not reveal the account number.
        $this->assertNotSame('7001234567', $stored);
        $this->assertStringNotContainsString('7001234567', $stored);

        // But the application still reads it back.
        $this->assertSame('7001234567', $tutor->tutorProfile->bank_account_number);
    }

    public function test_an_account_number_is_rejected_if_it_is_not_alphanumeric(): void
    {
        $tutor = $this->tutorWithProfile();

        $this->actingAs($tutor)
            ->from('/tutor/profile')
            ->put('/tutor/profile', ['bank_account_number' => '5140-1234 5678'])
            ->assertSessionHasErrors('bank_account_number');
    }

    public function test_a_profile_is_incomplete_until_all_three_fields_are_present(): void
    {
        $profile = $this->tutorWithProfile(['bank_name' => 'Maybank'])->tutorProfile;

        $this->assertFalse($profile->hasBankDetails());

        $profile->update(['bank_account_number' => '514012345678', 'bank_account_name' => 'Ahmad']);

        $this->assertTrue($profile->fresh()->hasBankDetails());
    }

    public function test_the_account_number_is_masked_for_display(): void
    {
        $profile = $this->tutorWithProfile(['bank_account_number' => '514012345678'])->tutorProfile;

        $this->assertSame('••••••••5678', $profile->maskedAccountNumber());
        $this->assertNull($this->tutorWithProfile()->tutorProfile->maskedAccountNumber());
    }

    public function test_admin_sees_the_destination_account_on_the_payout(): void
    {
        $tutor = $this->tutorWithProfile([
            'bank_name' => 'Maybank',
            'bank_account_number' => '514012345678',
            'bank_account_name' => 'Ahmad bin Ali',
        ]);

        $payout = TutorPayout::create([
            'tutor_id' => $tutor->id, 'amount' => 240, 'sessions_count' => 3,
            'period_start' => now()->subMonth()->toDateString(),
            'period_end' => now()->toDateString(), 'status' => 'pending',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/payouts/{$payout->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('bankDetails.bank_name', 'Maybank')
                ->where('bankDetails.bank_account_number', '514012345678')
            );
    }

    public function test_a_payout_to_a_tutor_with_no_bank_details_says_so(): void
    {
        $tutor = $this->tutorWithProfile();

        $payout = TutorPayout::create([
            'tutor_id' => $tutor->id, 'amount' => 240, 'sessions_count' => 3,
            'period_start' => now()->subMonth()->toDateString(),
            'period_end' => now()->toDateString(), 'status' => 'pending',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/payouts/{$payout->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('bankDetails', null));
    }

    public function test_a_tutor_cannot_read_another_tutors_bank_details(): void
    {
        $this->tutorWithProfile([
            'bank_name' => 'Maybank',
            'bank_account_number' => '514012345678',
            'bank_account_name' => 'Ahmad bin Ali',
        ]);

        $other = $this->tutorWithProfile();

        $response = $this->actingAs($other)->get('/tutor/profile');

        $response->assertOk();
        $response->assertDontSee('514012345678');
    }
}
