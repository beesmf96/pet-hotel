<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function mockSocialiteUser(string $id, string $email, string $name, bool $emailVerified = true): SocialiteUser
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);

        // The controller reads the OIDC `email_verified` claim off the raw payload,
        // which Socialite exposes as the public $user array rather than a getter.
        $socialiteUser->user = ['email_verified' => $emailVerified];

        return $socialiteUser;
    }

    private function mockSocialiteDriver(SocialiteUser $socialiteUser): void
    {
        $provider = Mockery::mock(AbstractProvider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($provider);
    }

    // ── Redirect ──────────────────────────────────────────────────────────────

    public function test_google_redirect_route_is_accessible_to_guests(): void
    {
        // We can't easily test the actual redirect (it goes to Google), but we
        // can assert the route resolves and does not 404/403.
        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::mock(AbstractProvider::class, function ($mock) {
                $mock->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com'));
            }));

        $this->get('/auth/google')->assertRedirect();
    }

    // ── Callback: new user ────────────────────────────────────────────────────

    public function test_new_google_user_is_created_and_logged_in(): void
    {
        $googleUser = $this->mockSocialiteUser('google-uid-1', 'new@example.com', 'New User');
        $this->mockSocialiteDriver($googleUser);

        $response = $this->get('/auth/google/callback');

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'google_id' => 'google-uid-1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/bookings');
    }

    public function test_new_google_user_has_verified_email_and_hashed_password(): void
    {
        $googleUser = $this->mockSocialiteUser('google-uid-2', 'verified@example.com', 'Verified User');
        $this->mockSocialiteDriver($googleUser);

        $this->get('/auth/google/callback');

        $user = User::where('email', 'verified@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->getAttributes()['password']);
    }

    // ── Callback: existing user by email, no google_id ────────────────────────

    public function test_existing_user_by_email_gets_google_id_linked(): void
    {
        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'google_id' => null,
        ]);

        $googleUser = $this->mockSocialiteUser('google-uid-3', 'existing@example.com', 'Existing User');
        $this->mockSocialiteDriver($googleUser);

        $response = $this->get('/auth/google/callback');

        $this->assertDatabaseHas('users', [
            'id' => $existing->id,
            'google_id' => 'google-uid-3',
        ]);

        $this->assertAuthenticatedAs($existing->fresh());
        $response->assertRedirect('/bookings');
    }

    public function test_existing_user_by_email_does_not_create_duplicate(): void
    {
        User::factory()->create([
            'email' => 'dup@example.com',
            'google_id' => null,
        ]);

        $googleUser = $this->mockSocialiteUser('google-uid-4', 'dup@example.com', 'Dup User');
        $this->mockSocialiteDriver($googleUser);

        $this->get('/auth/google/callback');

        $this->assertSame(1, User::where('email', 'dup@example.com')->count());
    }

    public function test_unverified_provider_email_does_not_adopt_existing_account(): void
    {
        $existing = User::factory()->create([
            'email' => 'victim@example.com',
            'google_id' => null,
        ]);

        $googleUser = $this->mockSocialiteUser(
            'attacker-uid', 'victim@example.com', 'Attacker', emailVerified: false
        );
        $this->mockSocialiteDriver($googleUser);

        $response = $this->get('/auth/google/callback');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $this->assertNull($existing->fresh()->google_id);
    }

    public function test_missing_email_verified_claim_is_treated_as_unverified(): void
    {
        $existing = User::factory()->create([
            'email' => 'noclaim@example.com',
            'google_id' => null,
        ]);

        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('uid-no-claim');
        $googleUser->shouldReceive('getEmail')->andReturn('noclaim@example.com');
        $googleUser->shouldReceive('getName')->andReturn('No Claim');
        $googleUser->user = [];
        $this->mockSocialiteDriver($googleUser);

        $this->get('/auth/google/callback')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertNull($existing->fresh()->google_id);
    }

    // ── Callback: existing user by google_id ─────────────────────────────────

    public function test_existing_user_by_google_id_is_logged_in_directly(): void
    {
        $existing = User::factory()->create([
            'email' => 'guser@example.com',
            'google_id' => 'google-uid-5',
        ]);

        $googleUser = $this->mockSocialiteUser('google-uid-5', 'guser@example.com', 'G User');
        $this->mockSocialiteDriver($googleUser);

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($existing);
        $response->assertRedirect('/bookings');

        // No duplicate created
        $this->assertSame(1, User::where('google_id', 'google-uid-5')->count());
    }

    // ── Callback: InvalidStateException ──────────────────────────────────────

    public function test_invalid_state_exception_redirects_to_login(): void
    {
        $provider = Mockery::mock(AbstractProvider::class);
        $provider->shouldReceive('user')
            ->andThrow(new InvalidStateException);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
