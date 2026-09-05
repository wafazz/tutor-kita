<?php

namespace Tests\Feature;

use App\Models\Postcode;
use App\Models\User;
use Database\Seeders\PostcodeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostcodeLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_postcode_returns_its_city_and_state(): void
    {
        $this->seed(PostcodeSeeder::class);

        $this->actingAs(User::factory()->parent()->create())
            ->getJson('/postcode-lookup?postcode=01000')
            ->assertOk()
            ->assertJson(['found' => true, 'city' => 'Kangar', 'state' => 'Perlis']);
    }

    public function test_a_postcode_serving_two_cities_returns_both(): void
    {
        $this->seed(PostcodeSeeder::class);

        $response = $this->actingAs(User::factory()->parent()->create())
            ->getJson('/postcode-lookup?postcode=40160')
            ->assertOk();

        // The form fills the first rather than silently choosing between them.
        $this->assertSame('Shah Alam', $response->json('city'));
        $this->assertSame(['Shah Alam', 'Sungai Buloh'], $response->json('cities'));
    }

    public function test_an_unknown_postcode_reports_not_found_rather_than_guessing(): void
    {
        $this->seed(PostcodeSeeder::class);

        $this->actingAs(User::factory()->parent()->create())
            ->getJson('/postcode-lookup?postcode=99999')
            ->assertOk()
            ->assertJson(['found' => false])
            ->assertJsonMissing(['city']);
    }

    public function test_the_lookup_requires_a_postcode(): void
    {
        $this->actingAs(User::factory()->parent()->create())
            ->getJson('/postcode-lookup')
            ->assertStatus(422);
    }

    public function test_a_guest_cannot_read_the_directory(): void
    {
        $this->getJson('/postcode-lookup?postcode=01000')->assertUnauthorized();
    }

    public function test_surrounding_whitespace_is_tolerated(): void
    {
        Postcode::create(['postcode' => '47810', 'city' => 'Petaling Jaya', 'state' => 'Selangor']);

        $this->actingAs(User::factory()->tutor()->create())
            ->getJson('/postcode-lookup?postcode='.urlencode(' 47810 '))
            ->assertOk()
            ->assertJson(['found' => true, 'city' => 'Petaling Jaya']);
    }

    public function test_every_role_that_has_an_address_form_can_use_it(): void
    {
        Postcode::create(['postcode' => '47810', 'city' => 'Petaling Jaya', 'state' => 'Selangor']);

        foreach (['parent', 'tutor', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->getJson('/postcode-lookup?postcode=47810')
                ->assertOk()
                ->assertJson(['found' => true]);
        }
    }
}
