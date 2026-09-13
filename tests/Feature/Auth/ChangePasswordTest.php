<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_change_a_password(): void
    {
        $this->put(route('profile.password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));
    }

    public function test_user_can_change_password_with_the_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_a_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'not-the-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_current_password_is_required_when_the_user_has_one(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_the_new_password_must_be_at_least_eight_characters(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'old-password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_oauth_user_can_set_a_password_without_confirming_a_current_one(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => '1234567890']);

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'password' => 'chosen-password',
                'password_confirmation' => 'chosen-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertTrue(Hash::check('chosen-password', $user->fresh()->password));
    }

    public function test_oauth_user_can_log_in_with_a_password_only_after_setting_one(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => '1234567890']);

        // A null password must not authenticate anything. Submit a real string so
        // this reaches the hasher rather than stopping at 'required' validation.
        $this->post('/login', ['email' => $user->email, 'password' => 'any-guess-at-all'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)->put(route('profile.password.update'), [
            'password' => 'chosen-password',
            'password_confirmation' => 'chosen-password',
        ])->assertSessionHasNoErrors();

        auth()->logout();

        $this->post('/login', ['email' => $user->email, 'password' => 'chosen-password'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_the_profile_page_reports_whether_the_user_has_a_password(): void
    {
        $withPassword = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($withPassword)
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->component('Profile')
                ->where('hasPassword', true)
            );

        $oauthUser = User::factory()->create(['password' => null, 'google_id' => '1234567890']);

        $this->actingAs($oauthUser)
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->component('Profile')
                ->where('hasPassword', false)
            );
    }
}
