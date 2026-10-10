<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotelAvailability;
use App\Models\PetHotel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private PetHotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hotel = PetHotel::create([
            'name' => 'Paws Inn',
            'slug' => 'paws-inn',
            'description' => 'A nice place',
            'address' => '1 Main St',
            'city' => 'Sydney',
        ]);
    }

    public function test_returns_json_with_days_for_current_month(): void
    {
        $response = $this->getJson("/hotels/{$this->hotel->slug}/availability");

        $response->assertOk()
            ->assertJsonStructure(['hotel_id', 'month', 'days'])
            ->assertJsonPath('hotel_id', $this->hotel->id);
    }

    public function test_returns_days_for_requested_month(): void
    {
        $response = $this->getJson("/hotels/{$this->hotel->slug}/availability?month=2026-08");

        $response->assertOk()
            ->assertJsonPath('month', '2026-08');

        $days = $response->json('days');
        $this->assertArrayHasKey('2026-08-01', $days);
        $this->assertArrayHasKey('2026-08-31', $days);
        $this->assertArrayNotHasKey('2026-07-31', $days);
    }

    public function test_blocked_date_shows_blocked_status(): void
    {
        HotelAvailability::create([
            'hotel_id' => $this->hotel->id,
            'date' => '2026-08-15',
            'is_blocked' => true,
        ]);

        $response = $this->getJson("/hotels/{$this->hotel->slug}/availability?month=2026-08");

        $response->assertOk()
            ->assertJsonPath('days.2026-08-15.status', 'blocked');
    }

    public function test_full_date_shows_full_status(): void
    {
        HotelAvailability::create([
            'hotel_id' => $this->hotel->id,
            'date' => '2026-08-10',
            'capacity' => 0,
        ]);

        $response = $this->getJson("/hotels/{$this->hotel->slug}/availability?month=2026-08");

        $response->assertOk()
            ->assertJsonPath('days.2026-08-10.status', 'full');
    }

    public function test_available_date_shows_available_status(): void
    {
        HotelAvailability::create([
            'hotel_id' => $this->hotel->id,
            'date' => '2026-08-20',
            'capacity' => 5,
        ]);

        $response = $this->getJson("/hotels/{$this->hotel->slug}/availability?month=2026-08");

        $response->assertOk()
            ->assertJsonPath('days.2026-08-20.status', 'available')
            ->assertJsonPath('days.2026-08-20.available_spots', 5);
    }

    public function test_dates_with_no_record_use_the_hotel_capacity(): void
    {
        $this->hotel->update(['capacity' => 4]);

        $response = $this->getJson("/hotels/{$this->hotel->slug}/availability?month=2026-08");

        $response->assertOk()
            ->assertJsonPath('days.2026-08-05.status', 'available')
            ->assertJsonPath('days.2026-08-05.available_spots', 4);
    }

    public function test_confirmed_bookings_reduce_spots_left(): void
    {
        $this->hotel->update(['capacity' => 2]);
        Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create([
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-07',
        ]);
        Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create([
            'check_in' => '2026-08-06',
            'check_out' => '2026-08-07',
        ]);

        $response = $this->getJson("/hotels/{$this->hotel->slug}/availability?month=2026-08");

        $response->assertJsonPath('days.2026-08-05.available_spots', 1)
            ->assertJsonPath('days.2026-08-06.status', 'full')
            ->assertJsonPath('days.2026-08-06.available_spots', 0)
            ->assertJsonPath('days.2026-08-07.available_spots', 2);
    }

    public function test_limited_status_is_relative_to_the_nights_capacity(): void
    {
        $this->hotel->update(['capacity' => 2]);
        Booking::factory()->for($this->hotel, 'hotel')->confirmed()->create([
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-06',
        ]);

        $response = $this->getJson("/hotels/{$this->hotel->slug}/availability?month=2026-08");

        $response->assertJsonPath('days.2026-08-04.status', 'available')
            ->assertJsonPath('days.2026-08-04.capacity', 2)
            ->assertJsonPath('days.2026-08-05.status', 'limited')
            ->assertJsonPath('days.2026-08-05.available_spots', 1);
    }

    public function test_returns_404_for_unknown_hotel(): void
    {
        $this->getJson('/hotels/does-not-exist/availability')
            ->assertNotFound();
    }

    public function test_rejects_invalid_month_format(): void
    {
        $this->getJson("/hotels/{$this->hotel->slug}/availability?month=not-a-month")
            ->assertUnprocessable();
    }

    public function test_all_days_in_month_are_returned(): void
    {
        $response = $this->getJson("/hotels/{$this->hotel->slug}/availability?month=2026-06");

        $days = $response->json('days');
        $this->assertCount(30, $days); // June has 30 days
    }
}
