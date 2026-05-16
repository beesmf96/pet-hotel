<?php

namespace Tests\Feature;

use App\Models\PetHotel;
use App\Models\PetHotelFacility;
use App\Models\PetHotelPhoto;
use App\Models\PetHotelPolicy;
use App\Models\PetHotelPricing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelTest extends TestCase
{
    use RefreshDatabase;

    private function makeHotel(array $attributes = []): PetHotel
    {
        $hotel = PetHotel::factory()->create($attributes);

        PetHotelPolicy::create([
            'hotel_id' => $hotel->id,
            'check_in_time' => '14:00',
            'check_out_time' => '11:00',
            'cancellation_policy' => 'Free cancellation 48h prior.',
        ]);

        PetHotelPricing::create(['hotel_id' => $hotel->id, 'pet_type' => 'dog', 'price_per_night' => 50.00]);
        PetHotelPricing::create(['hotel_id' => $hotel->id, 'pet_type' => 'cat', 'price_per_night' => 40.00]);

        PetHotelFacility::create(['hotel_id' => $hotel->id, 'type' => 'grooming']);
        PetHotelFacility::create(['hotel_id' => $hotel->id, 'type' => 'play_area']);

        PetHotelPhoto::create(['hotel_id' => $hotel->id, 'url' => 'https://example.com/photo.jpg', 'sort_order' => 0]);

        return $hotel;
    }

    public function test_guest_can_view_hotel_profile(): void
    {
        $hotel = $this->makeHotel();

        $this->get("/hotels/{$hotel->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Hotels/HotelProfilePage')
                ->has('hotel')
                ->where('hotel.slug', $hotel->slug)
            );
    }

    public function test_hotel_profile_includes_related_data(): void
    {
        $hotel = $this->makeHotel();

        $this->get("/hotels/{$hotel->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('hotel.facilities', 2)
                ->has('hotel.photos', 1)
                ->has('hotel.policy')
                ->has('hotel.pricing', 2)
            );
    }

    public function test_authenticated_user_can_view_hotel_profile(): void
    {
        $hotel = $this->makeHotel();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get("/hotels/{$hotel->slug}")
            ->assertOk();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/hotels/does-not-exist')->assertNotFound();
    }

    public function test_hotel_profile_has_correct_name(): void
    {
        $hotel = $this->makeHotel(['name' => 'Happy Paws Hotel']);

        $this->get("/hotels/{$hotel->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hotel.name', 'Happy Paws Hotel')
            );
    }
}
