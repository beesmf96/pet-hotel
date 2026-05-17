<?php

namespace Tests\Feature;

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
            'available_spots' => 0,
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
            'available_spots' => 0,
            'is_blocked' => false,
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
            'available_spots' => 5,
            'is_blocked' => false,
        ]);

        $response = $this->getJson("/hotels/{$this->hotel->slug}/availability?month=2026-08");

        $response->assertOk()
            ->assertJsonPath('days.2026-08-20.status', 'available')
            ->assertJsonPath('days.2026-08-20.available_spots', 5);
    }

    public function test_dates_with_no_record_default_to_available(): void
    {
        $response = $this->getJson("/hotels/{$this->hotel->slug}/availability?month=2026-08");

        $response->assertOk()
            ->assertJsonPath('days.2026-08-05.status', 'available');
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
