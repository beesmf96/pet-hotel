<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ── Register ─────────────────────────────────────────────────────────────

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_authenticated_user_is_redirected_from_register(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/register')
            ->assertRedirect('/dashboard');
    }

    public function test_guest_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_register_requires_name(): void
    {
        $this->post('/register', [
            'email' => 'alice@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('name');
    }

    public function test_register_requires_valid_email(): void
    {
        $this->post('/register', [
            'name' => 'Alice',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $this->post('/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_register_requires_password_confirmation(): void
    {
        $this->post('/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password',
            'password_confirmation' => 'wrong',
        ])->assertSessionHasErrors('password');
    }

    public function test_register_requires_minimum_password_length(): void
    {
        $this->post('/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_cannot_login_with_unknown_email(): void
    {
        $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_email(): void
    {
        $this->post('/login', ['password' => 'password'])
            ->assertSessionHasErrors('email');
    }

    public function test_login_requires_password(): void
    {
        $this->post('/login', ['email' => 'alice@example.com'])
            ->assertSessionHasErrors('password');
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_guest_cannot_access_logout(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }
}
