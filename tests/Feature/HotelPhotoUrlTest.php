<?php

namespace Tests\Feature;

use App\Models\PetHotel;
use App\Models\PetHotelPhoto;
use App\Models\PetHotelPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * An uploaded hotel photo used to reach the page as a bare storage path, which
 * the browser resolved against the current URL and failed to load. The seeder's
 * absolute picsum URLs hid it, so these cover both stored formats end to end.
 */
class HotelPhotoUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.photos' => 'test-bucket']);
        Storage::fake('test-bucket', ['url' => 'https://cdn.example.com']);
    }

    private function activeHotel(array $attrs = []): PetHotel
    {
        $hotel = PetHotel::factory()->create($attrs + ['is_active' => true]);

        PetHotelPolicy::create([
            'hotel_id' => $hotel->id,
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'cancellation_policy' => 'Free within 48 hours.',
        ]);

        return $hotel;
    }

    public function test_an_uploaded_cover_photo_reaches_the_page_as_a_url(): void
    {
        $hotel = $this->activeHotel(['cover_photo' => 'cover-photos/paws.jpg']);

        $this->get("/hotels/{$hotel->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('hotel.cover_photo_url', 'https://cdn.example.com/cover-photos/paws.jpg')
                // The raw column still travels untouched — Filament edits it.
                ->where('hotel.cover_photo', 'cover-photos/paws.jpg')
            );
    }

    public function test_a_seeded_absolute_cover_photo_is_passed_through(): void
    {
        $url = 'https://picsum.photos/seed/7/800/600';
        $hotel = $this->activeHotel(['cover_photo' => $url]);

        $this->get("/hotels/{$hotel->slug}")
            ->assertInertia(fn ($page) => $page->where('hotel.cover_photo_url', $url));
    }

    public function test_a_hotel_without_a_cover_photo_sends_null(): void
    {
        $hotel = $this->activeHotel(['cover_photo' => null]);

        $this->get("/hotels/{$hotel->slug}")
            ->assertInertia(fn ($page) => $page->where('hotel.cover_photo_url', null));
    }

    public function test_gallery_photos_are_resolved_too(): void
    {
        $hotel = $this->activeHotel();
        PetHotelPhoto::create([
            'hotel_id' => $hotel->id,
            'url' => 'hotel-photos/kennel.jpg',
            'sort_order' => 0,
        ]);

        $this->get("/hotels/{$hotel->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('hotel.photos.0.photo_url', 'https://cdn.example.com/hotel-photos/kennel.jpg')
            );
    }

    public function test_the_landing_page_resolves_featured_hotel_covers(): void
    {
        $this->activeHotel(['cover_photo' => 'cover-photos/featured.jpg']);

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('featuredHotels.0.cover_photo_url', 'https://cdn.example.com/cover-photos/featured.jpg')
            );
    }

    public function test_the_search_page_resolves_covers(): void
    {
        $this->activeHotel(['cover_photo' => 'cover-photos/searched.jpg']);

        $this->get('/hotels')
            ->assertInertia(fn ($page) => $page
                ->where('hotels.data.0.cover_photo_url', 'https://cdn.example.com/cover-photos/searched.jpg')
            );
    }
}
