<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/parent/dashboard');

        $this->assertSame('parent', User::where('email', 'test@example.com')->sole()->role);
    }

    public function test_new_users_can_register_as_a_tutor(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Tutor',
            'email' => 'tutor@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'tutor',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();
        $response->assertRedirect('/tutor/dashboard');

        $user = User::where('email', 'tutor@example.com')->sole();

        $this->assertSame('tutor', $user->role);
        $this->assertDatabaseHas('tutor_profiles', ['user_id' => $user->id]);
    }
}
