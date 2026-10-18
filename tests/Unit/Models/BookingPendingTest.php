<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Booking::isPending() and the pending() scope: one place that names an unanswered request. */
class BookingPendingTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_pending_booking_is_pending(): void
    {
        $this->assertTrue(Booking::factory()->make(['status' => 'pending'])->isPending());

        foreach (['confirmed', 'cancelled', 'completed'] as $status) {
            $this->assertFalse(Booking::factory()->make(['status' => $status])->isPending());
        }
    }

    public function test_the_scope_finds_pending_bookings_only(): void
    {
        $pending = Booking::factory()->create(['status' => 'pending']);
        Booking::factory()->confirmed()->create();
        Booking::factory()->create(['status' => 'cancelled']);

        $this->assertSame([$pending->id], Booking::pending()->pluck('id')->all());
    }
}
