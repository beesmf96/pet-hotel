<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InertiaSharedPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_user_is_null_for_guests(): void
    {
        $this->get('/hotels')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('auth.user', null));
    }

    public function test_auth_user_is_shared_with_correct_shape(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.id', $user->id)
                ->where('auth.user.name', $user->name)
                ->where('auth.user.email', $user->email)
                ->has('auth.user.email_verified_at')
                ->missing('auth.user.password')
            );
    }

    public function test_flash_status_is_null_when_not_set(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('flash.status', null));
    }

    public function test_flash_status_is_shared_from_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['status' => 'Password reset successfully.'])
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('flash.status', 'Password reset successfully.')
            );
    }
}
