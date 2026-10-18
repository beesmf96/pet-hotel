<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Booking::checkInText(), checkOutText() and nightsText(): the stay as every
 * email and panel pop-up writes it.
 */
class BookingStayTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_stay_dates_carry_the_hotel_times(): void
    {
        $booking = Booking::factory()->create(['check_in' => '2030-10-25', 'check_out' => '2030-10-28']);
        $booking->hotel->policy()->create(['check_in_time' => '14:00', 'check_out_time' => '12:00']);
        $booking = $booking->fresh();

        $this->assertSame('Fri, 25 Oct 2030, from 2:00 PM', $booking->checkInText());
        $this->assertSame('Mon, 28 Oct 2030, by 12:00 PM', $booking->checkOutText());
    }

    public function test_stay_dates_stand_alone_without_a_policy(): void
    {
        $booking = Booking::factory()->create(['check_in' => '2030-10-25', 'check_out' => '2030-10-28']);

        $this->assertSame('Fri, 25 Oct 2030', $booking->checkInText());
        $this->assertSame('Mon, 28 Oct 2030', $booking->checkOutText());
    }

    public function test_nights_count_the_stay(): void
    {
        $three = Booking::factory()->make(['check_in' => '2030-10-25', 'check_out' => '2030-10-28']);
        $one = Booking::factory()->make(['check_in' => '2030-10-25', 'check_out' => '2030-10-26']);

        $this->assertSame(3, $three->nights());
        $this->assertSame('3 nights', $three->nightsText());
        $this->assertSame('1 night', $one->nightsText());
    }
}
