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
            ->assertRedirect('/bookings');
    }

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * An unknown address must be indistinguishable from a known one, or the
     * endpoint becomes a user-enumeration oracle. No mail is sent either way.
     */
    public function test_unknown_email_gets_the_same_response_as_a_known_one(): void
    {
        Notification::fake();

        $known = User::factory()->create();

        $knownResponse = $this->post('/forgot-password', ['email' => $known->email]);
        $unknownResponse = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $knownResponse->assertSessionHas('status');
        $unknownResponse->assertSessionHas('status');
        $unknownResponse->assertSessionHasNoErrors();

        $this->assertSame(
            session()->get('status'),
            $knownResponse->getSession()->get('status'),
        );

        Notification::assertNothingSentTo(
            User::factory()->make(['email' => 'nobody@example.com'])
        );
    }

    /**
     * Without a limit these two routes are an unmetered reset-token guessing
     * surface, and the only unauthenticated POSTs in the app lacking one.
     */
    public function test_forgot_password_is_rate_limited(): void
    {
        Notification::fake();

        foreach (range(1, 5) as $i) {
            $this->post('/forgot-password', ['email' => 'nobody@example.com'])
                ->assertSessionHasNoErrors();
        }

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertStatus(429);
    }

    public function test_reset_password_is_rate_limited(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post('/reset-password', [
                'token' => 'bad-token',
                'email' => 'nobody@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        $this->post('/reset-password', [
            'token' => 'bad-token',
            'email' => 'nobody@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(429);
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
            ->assertRedirect('/bookings');
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
