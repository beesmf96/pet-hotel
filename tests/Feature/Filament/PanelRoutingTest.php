<?php

namespace Tests\Feature\Filament;

use App\Models\PetHotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Both panels were previously pinned to hostnames that only resolve through a
 * local hosts-file entry, which left them unroutable on any real deployment.
 * These cover the routing itself; the resource tests drive the panels through
 * Livewire and so never exercise it.
 */
class PanelRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_panel_login_is_reachable_on_the_app_domain(): void
    {
        $this->get('/admin/login')->assertSuccessful();
    }

    public function test_the_hotel_owner_panel_login_is_reachable_on_the_app_domain(): void
    {
        $this->get('/owner/login')->assertSuccessful();
    }

    public function test_the_admin_panel_still_turns_away_non_admins(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertForbidden();
    }

    public function test_an_admin_reaches_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin')->assertSuccessful();
    }

    public function test_the_owner_panel_still_turns_away_users_without_a_hotel(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/owner')->assertForbidden();
    }

    /**
     * The owner panel has no dashboard page — only BookingResource — so its root
     * redirects to that resource rather than rendering.
     */
    public function test_a_hotel_owner_reaches_the_owner_panel(): void
    {
        $owner = User::factory()->create();
        PetHotel::factory()->create()->owners()->attach($owner, ['role' => 'owner']);

        $this->actingAs($owner);

        $this->get('/owner')->assertRedirect('/owner/bookings');
        $this->get('/owner/bookings')->assertSuccessful();
    }

    /**
     * The panels claim path prefixes on the same origin as the SPA now, so the
     * customer-facing routes must be unaffected.
     */
    public function test_the_customer_facing_home_page_still_resolves(): void
    {
        $this->get('/')->assertSuccessful();
    }
}
