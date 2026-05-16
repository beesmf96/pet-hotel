<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_guest_cannot_view_profile(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_unverified_user_cannot_view_profile(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get('/profile')
            ->assertRedirect('/email/verify');
    }

    public function test_user_can_view_profile_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertStatus(200);
    }

    public function test_profile_page_contains_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Alice',
            'phone' => '555-1234',
            'preferred_location' => 'Central Park',
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertInertia(fn ($page) => $page
                ->where('user.name', 'Alice')
                ->where('user.phone', '555-1234')
                ->where('user.preferred_location', 'Central Park')
            );
    }

    public function test_profile_does_not_expose_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertInertia(fn ($page) => $page
                ->missing('user.password')
            );
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_update_profile(): void
    {
        $this->patch('/profile', ['name' => 'Alice'])->assertRedirect('/login');
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'New Name',
                'phone' => '555-9999',
                'preferred_location' => 'Downtown',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Profile updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'phone' => '555-9999',
            'preferred_location' => 'Downtown',
        ]);
    }

    public function test_update_requires_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_update_accepts_nullable_phone(): void
    {
        $user = User::factory()->create(['phone' => '555-1234']);

        $this->actingAs($user)
            ->patch('/profile', ['name' => $user->name, 'phone' => null])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone' => null]);
    }

    public function test_update_accepts_nullable_preferred_location(): void
    {
        $user = User::factory()->create(['preferred_location' => 'Central Park']);

        $this->actingAs($user)
            ->patch('/profile', ['name' => $user->name, 'preferred_location' => null])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'preferred_location' => null]);
    }

    public function test_update_validates_name_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', ['name' => str_repeat('a', 256)])
            ->assertSessionHasErrors('name');
    }

    public function test_update_validates_phone_max_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', ['name' => 'Alice', 'phone' => str_repeat('1', 31)])
            ->assertSessionHasErrors('phone');
    }
}
