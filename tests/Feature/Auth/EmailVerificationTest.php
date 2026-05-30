<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    // ── Notice ────────────────────────────────────────────────────────────────

    public function test_verification_notice_renders_for_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertStatus(200);
    }

    public function test_guest_cannot_access_verification_notice(): void
    {
        $this->get('/email/verify')->assertRedirect('/login');
    }

    // ── Verify ────────────────────────────────────────────────────────────────

    public function test_user_can_verify_email_with_valid_signed_url(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect('/bookings');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_already_verified_user_is_redirected_to_bookings(): void
    {
        $user = User::factory()->create(); // email_verified_at is set by default

        $url = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect('/bookings');
    }

    public function test_verification_fails_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => 'invalid-hash',
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_fails_with_unsigned_url(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get("/email/verify/{$user->id}/".sha1($user->email))
            ->assertForbidden();
    }

    // ── Resend ────────────────────────────────────────────────────────────────

    public function test_unverified_user_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_is_redirected_when_resending(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertRedirect('/bookings');
    }

    public function test_guest_cannot_resend_verification(): void
    {
        $this->post('/email/verification-notification')->assertRedirect('/login');
    }
}
