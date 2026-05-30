<?php

namespace Tests\Feature;

use App\Models\PetHotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_route_renders_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Landing'));
    }

    public function test_landing_page_passes_featured_hotels_prop(): void
    {
        PetHotel::factory()->count(2)->create(['is_active' => true]);

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->component('Landing')
                ->has('featuredHotels')
            );
    }

    public function test_landing_page_returns_at_most_4_hotels(): void
    {
        PetHotel::factory()->count(6)->create(['is_active' => true]);

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->component('Landing')
                ->has('featuredHotels', 4)
            );
    }

    public function test_landing_page_excludes_inactive_hotels(): void
    {
        PetHotel::factory()->count(2)->create(['is_active' => true]);
        PetHotel::factory()->count(3)->create(['is_active' => false]);

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->component('Landing')
                ->has('featuredHotels', 2)
            );
    }

    public function test_landing_page_accessible_while_authenticated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Landing'));
    }

    public function test_guest_has_null_auth_user_in_shared_props(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Landing')
                ->where('auth.user', null)
            );
    }

    public function test_authenticated_user_has_auth_user_in_shared_props(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Landing')
                ->where('auth.user.id', $user->id)
                ->where('auth.user.name', $user->name)
            );
    }
}
