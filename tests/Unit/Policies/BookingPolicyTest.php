<?php

namespace Tests\Unit\Policies;

use App\Models\Booking;
use App\Models\User;
use App\Policies\BookingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPolicyTest extends TestCase
{
    use RefreshDatabase;

    private BookingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new BookingPolicy;
    }

    // ── view ──────────────────────────────────────────────────────────────────

    public function test_owner_can_view_their_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->for($user)->create();

        $this->assertTrue($this->policy->view($user, $booking));
    }

    public function test_non_owner_cannot_view_booking(): void
    {
        $booking = Booking::factory()->create();

        $this->assertFalse($this->policy->view(User::factory()->create(), $booking));
    }

    public function test_admin_can_view_any_booking(): void
    {
        $booking = Booking::factory()->create();

        $this->assertTrue($this->policy->view(User::factory()->admin()->create(), $booking));
    }

    // ── cancel ────────────────────────────────────────────────────────────────

    public function test_owner_can_cancel_their_pending_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->for($user)->create(['status' => 'pending']);

        $this->assertTrue($this->policy->cancel($user, $booking));
    }

    public function test_owner_cannot_cancel_a_confirmed_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->for($user)->confirmed()->create();

        $this->assertFalse($this->policy->cancel($user, $booking));
    }

    public function test_non_owner_cannot_cancel_booking(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending']);

        $this->assertFalse($this->policy->cancel(User::factory()->create(), $booking));
    }

    /**
     * Admins manage bookings through the Filament panel's own actions, which update
     * the record directly rather than going through this policy. The admin bypass is
     * deliberately limited to view().
     */
    public function test_admin_does_not_get_a_cancel_bypass(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending']);

        $this->assertFalse($this->policy->cancel(User::factory()->admin()->create(), $booking));
    }
}
