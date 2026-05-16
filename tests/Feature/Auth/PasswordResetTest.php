<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    // ── Forgot Password ───────────────────────────────────────────────────────

    public function test_forgot_password_page_renders(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_authenticated_user_is_redirected_from_forgot_password(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/forgot-password')
            ->assertRedirect('/dashboard');
    }

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_not_sent_for_unknown_email(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_forgot_password_requires_email(): void
    {
        $this->post('/forgot-password', [])
            ->assertSessionHasErrors('email');
    }

    public function test_forgot_password_requires_valid_email(): void
    {
        $this->post('/forgot-password', ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    // ── Reset Password ────────────────────────────────────────────────────────

    public function test_reset_password_page_renders_with_token(): void
    {
        $this->get('/reset-password/some-token?email=alice@example.com')
            ->assertStatus(200);
    }

    public function test_authenticated_user_is_redirected_from_reset_password(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/reset-password/some-token')
            ->assertRedirect('/dashboard');
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        // Generate a real token via the broker
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertRedirect('/login');

        // Verify the password was actually changed
        $this->assertTrue(
            Hash::check('newpassword', $user->fresh()->password)
        );
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertSessionHasErrors('email');
    }

    public function test_reset_password_requires_token(): void
    {
        $this->post('/reset-password', [
            'email' => 'alice@example.com',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertSessionHasErrors('token');
    }

    public function test_reset_password_requires_password_confirmation(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }

    public function test_reset_password_requires_minimum_password_length(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }
}
