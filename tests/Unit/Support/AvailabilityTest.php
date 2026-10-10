<?php

namespace Tests\Unit\Support;

use App\Models\Booking;
use App\Models\HotelAvailability;
use App\Models\PetHotel;
use App\Support\Availability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private PetHotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = PetHotel::factory()->create(['capacity' => 3]);
    }

    private function book(string $checkIn, string $checkOut, string $status = 'confirmed'): Booking
    {
        return Booking::factory()->for($this->hotel, 'hotel')->create([
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $status,
        ]);
    }

    /** @return array<string, array{capacity: int, booked: int, spots_left: int, blocked: bool}> */
    private function nights(string $first, string $last): array
    {
        return Availability::nights($this->hotel, Carbon::parse($first), Carbon::parse($last));
    }

    private function fits(string $checkIn, string $checkOut): bool
    {
        return Availability::fits($this->hotel, Carbon::parse($checkIn), Carbon::parse($checkOut));
    }

    public function test_a_date_with_no_row_uses_the_hotel_capacity(): void
    {
        $this->assertSame(
            ['capacity' => 3, 'booked' => 0, 'spots_left' => 3, 'blocked' => false],
            $this->nights('2030-12-24', '2030-12-24')['2030-12-24'],
        );
    }

    public function test_every_night_in_the_range_is_returned(): void
    {
        $this->assertSame(
            ['2030-12-30', '2030-12-31', '2031-01-01'],
            array_keys($this->nights('2030-12-30', '2031-01-01')),
        );
    }

    public function test_confirmed_and_completed_bookings_take_a_spot(): void
    {
        $this->book('2030-12-24', '2030-12-25', 'confirmed');
        $this->book('2030-12-24', '2030-12-25', 'completed');

        $this->assertSame(1, $this->nights('2030-12-24', '2030-12-24')['2030-12-24']['spots_left']);
    }

    public function test_pending_and_cancelled_bookings_do_not_take_a_spot(): void
    {
        $this->book('2030-12-24', '2030-12-25', 'pending');
        $this->book('2030-12-24', '2030-12-25', 'cancelled');

        $this->assertSame(3, $this->nights('2030-12-24', '2030-12-24')['2030-12-24']['spots_left']);
    }

    public function test_the_check_out_day_is_not_a_night(): void
    {
        $this->book('2030-12-25', '2030-12-28');

        $nights = $this->nights('2030-12-24', '2030-12-28');

        $this->assertSame(0, $nights['2030-12-24']['booked']);
        $this->assertSame(1, $nights['2030-12-25']['booked']);
        $this->assertSame(1, $nights['2030-12-27']['booked']);
        $this->assertSame(0, $nights['2030-12-28']['booked']);
    }

    public function test_a_booking_reaching_past_the_range_counts_only_inside_it(): void
    {
        $this->book('2030-12-20', '2031-01-05');

        $nights = $this->nights('2030-12-30', '2030-12-31');

        $this->assertSame(1, $nights['2030-12-30']['booked']);
        $this->assertSame(1, $nights['2030-12-31']['booked']);
    }

    public function test_another_hotels_bookings_are_ignored(): void
    {
        Booking::factory()->confirmed()->create(['check_in' => '2030-12-24', 'check_out' => '2030-12-25']);

        $this->assertSame(3, $this->nights('2030-12-24', '2030-12-24')['2030-12-24']['spots_left']);
    }

    public function test_a_date_capacity_replaces_the_hotel_capacity(): void
    {
        HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => '2030-12-30', 'capacity' => 1]);
        $this->book('2030-12-30', '2030-12-31');

        $night = $this->nights('2030-12-30', '2030-12-30')['2030-12-30'];

        $this->assertSame(1, $night['capacity']);
        $this->assertSame(0, $night['spots_left']);
    }

    public function test_a_closed_date_is_blocked_with_the_normal_capacity(): void
    {
        HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => '2030-12-31', 'is_blocked' => true]);

        $night = $this->nights('2030-12-31', '2030-12-31')['2030-12-31'];

        $this->assertTrue($night['blocked']);
        $this->assertSame(3, $night['capacity']);
    }

    public function test_spots_left_never_goes_below_zero(): void
    {
        $this->book('2030-12-24', '2030-12-25');
        $this->book('2030-12-24', '2030-12-25');
        $this->hotel->update(['capacity' => 1]);

        $this->assertSame(0, $this->nights('2030-12-24', '2030-12-24')['2030-12-24']['spots_left']);
    }

    public function test_changing_the_hotel_capacity_changes_every_date(): void
    {
        $this->book('2030-12-24', '2030-12-26');
        $this->hotel->update(['capacity' => 5]);

        $nights = $this->nights('2030-12-24', '2030-12-27');

        $this->assertSame([4, 4, 5, 5], array_column($nights, 'spots_left'));
    }

    public function test_fits_when_every_night_has_a_spot(): void
    {
        $this->book('2030-12-24', '2030-12-25');

        $this->assertTrue($this->fits('2030-12-24', '2030-12-27'));
    }

    public function test_does_not_fit_when_one_night_is_full(): void
    {
        HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => '2030-12-25', 'capacity' => 0]);

        $this->assertFalse($this->fits('2030-12-24', '2030-12-27'));
    }

    public function test_does_not_fit_when_one_night_is_closed(): void
    {
        HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => '2030-12-26', 'is_blocked' => true]);

        $this->assertFalse($this->fits('2030-12-24', '2030-12-27'));
    }

    public function test_fits_when_only_the_check_out_day_is_closed(): void
    {
        HotelAvailability::create(['hotel_id' => $this->hotel->id, 'date' => '2030-12-27', 'is_blocked' => true]);

        $this->assertTrue($this->fits('2030-12-24', '2030-12-27'));
    }

    /** @return array{capacity: int, booked: int, spots_left: int, blocked: bool} */
    private function night(int $capacity, int $spotsLeft, bool $blocked = false): array
    {
        return ['capacity' => $capacity, 'booked' => $capacity - $spotsLeft, 'spots_left' => $spotsLeft, 'blocked' => $blocked];
    }

    public function test_status_reads_closed_before_anything_else(): void
    {
        $this->assertSame('blocked', Availability::status($this->night(4, 4, blocked: true)));
    }

    public function test_status_is_full_with_no_spot_left(): void
    {
        $this->assertSame('full', Availability::status($this->night(4, 0)));
        $this->assertSame('full', Availability::status($this->night(0, 0)));
    }

    public function test_status_is_limited_at_half_or_fewer_spots_left(): void
    {
        $this->assertSame('limited', Availability::status($this->night(4, 2)));
        $this->assertSame('limited', Availability::status($this->night(2, 1)));
        $this->assertSame('limited', Availability::status($this->night(10, 3)));
    }

    public function test_status_is_available_above_half(): void
    {
        $this->assertSame('available', Availability::status($this->night(4, 3)));
        $this->assertSame('available', Availability::status($this->night(2, 2)));
        $this->assertSame('available', Availability::status($this->night(1, 1)));
    }
}
