<?php

namespace Tests\Feature;

use App\Models\PetHotel;
use App\Models\PetHotelFacility;
use App\Models\PetHotelPricing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeHotel(array $attrs = [], array $pricing = [], array $facilities = []): PetHotel
    {
        $hotel = PetHotel::factory()->create($attrs);

        $defaultPricing = $pricing ?: [['pet_type' => 'dog', 'price_per_night' => 50.00]];
        foreach ($defaultPricing as $p) {
            PetHotelPricing::create(['hotel_id' => $hotel->id, ...$p]);
        }

        foreach ($facilities as $type) {
            PetHotelFacility::create(['hotel_id' => $hotel->id, 'type' => $type]);
        }

        return $hotel;
    }

    public function test_guest_can_access_search_page(): void
    {
        $this->get('/hotels')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Hotels/SearchPage'));
    }

    public function test_authenticated_user_can_access_search_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/hotels')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Hotels/SearchPage'));
    }

    public function test_search_returns_all_hotels_with_no_filters(): void
    {
        $this->makeHotel();
        $this->makeHotel();

        $this->get('/hotels')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hotels.total', 2));
    }

    public function test_filter_by_city(): void
    {
        $this->makeHotel(['city' => 'Kuala Lumpur']);
        $this->makeHotel(['city' => 'Penang']);

        $this->get('/hotels?city=Kuala')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hotels.total', 1));
    }

    public function test_city_filter_is_case_insensitive(): void
    {
        $this->makeHotel(['city' => 'Kuala Lumpur']);

        $this->get('/hotels?city=kuala')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hotels.total', 1));
    }

    public function test_filter_by_pet_type(): void
    {
        $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 50]]);
        $this->makeHotel([], [['pet_type' => 'cat', 'price_per_night' => 40]]);

        $this->get('/hotels?pet_type=dog')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hotels.total', 1));
    }

    public function test_filter_by_price_min(): void
    {
        $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 30]]);
        $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 80]]);

        $this->get('/hotels?price_min=50')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hotels.total', 1));
    }

    public function test_filter_by_price_max(): void
    {
        $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 30]]);
        $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 80]]);

        $this->get('/hotels?price_max=50')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hotels.total', 1));
    }

    public function test_filter_by_price_range(): void
    {
        $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 20]]);
        $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 60]]);
        $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 120]]);

        $this->get('/hotels?price_min=40&price_max=100')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hotels.total', 1));
    }

    public function test_filter_by_facility(): void
    {
        $this->makeHotel([], [], ['grooming', 'play_area']);
        $this->makeHotel([], [], ['vet_care']);

        $this->get('/hotels?facilities[]=grooming')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hotels.total', 1));
    }

    public function test_filter_by_multiple_facilities_requires_all(): void
    {
        $this->makeHotel([], [], ['grooming', 'play_area']);
        $this->makeHotel([], [], ['grooming']);

        $this->get('/hotels?facilities[]=grooming&facilities[]=play_area')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hotels.total', 1));
    }

    public function test_sort_by_price_asc(): void
    {
        $cheap = $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 30]]);
        $expensive = $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 100]]);

        $this->get('/hotels?sort=price_asc')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hotels.data.0.id', $cheap->id)
                ->where('hotels.data.1.id', $expensive->id)
            );
    }

    public function test_sort_by_price_desc(): void
    {
        $cheap = $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 30]]);
        $expensive = $this->makeHotel([], [['pet_type' => 'dog', 'price_per_night' => 100]]);

        $this->get('/hotels?sort=price_desc')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hotels.data.0.id', $expensive->id)
                ->where('hotels.data.1.id', $cheap->id)
            );
    }

    public function test_results_include_price_from(): void
    {
        $this->makeHotel([], [
            ['pet_type' => 'dog', 'price_per_night' => 80],
            ['pet_type' => 'cat', 'price_per_night' => 40],
        ]);

        $this->get('/hotels')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hotels.data.0.price_from', 40)
            );
    }

    public function test_pagination_returns_15_per_page(): void
    {
        PetHotel::factory(20)->create();

        $this->get('/hotels')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hotels.per_page', 15)
                ->where('hotels.total', 20)
                ->has('hotels.data', 15)
            );
    }

    public function test_filters_are_passed_back_to_page(): void
    {
        $this->get('/hotels?city=Kuala+Lumpur&pet_type=dog&sort=price_asc')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.city', 'Kuala Lumpur')
                ->where('filters.pet_type', 'dog')
                ->where('filters.sort', 'price_asc')
            );
    }

    public function test_empty_city_returns_all_hotels(): void
    {
        $this->makeHotel(['city' => 'Penang']);
        $this->makeHotel(['city' => 'Johor']);

        $this->get('/hotels?city=')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hotels.total', 2));
    }
}
