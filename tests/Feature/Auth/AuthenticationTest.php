<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    #[DataProvider('roleDashboardProvider')]
    public function test_users_can_authenticate_using_the_login_screen(string $role, string $dashboard): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect($dashboard);
    }

    /**
     * Login lands each role on its own dashboard.
     *
     * @return array<string, array{string, string}>
     */
    public static function roleDashboardProvider(): array
    {
        return [
            'admin' => ['admin', '/admin/dashboard'],
            'tutor' => ['tutor', '/tutor/dashboard'],
            'parent' => ['parent', '/parent/dashboard'],
        ];
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
