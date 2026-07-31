<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use App\Models\HotelAvailability;
use App\Models\PetHotel;
use App\Models\PetHotelFacility;
use App\Models\PetHotelPhoto;
use App\Models\PetHotelPolicy;
use App\Models\PetHotelPricing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetHotelTest extends TestCase
{
    use RefreshDatabase;

    private function makeHotel(array $attrs = []): PetHotel
    {
        return PetHotel::factory()->create($attrs);
    }

    // ── PetHotel ─────────────────────────────────────────────────────────────

    public function test_pet_hotel_has_many_facilities(): void
    {
        $hotel = $this->makeHotel();
        PetHotelFacility::create(['hotel_id' => $hotel->id, 'type' => 'grooming']);

        $this->assertInstanceOf(HasMany::class, $hotel->facilities());
        $this->assertCount(1, $hotel->facilities);
    }

    public function test_pet_hotel_has_many_photos(): void
    {
        $hotel = $this->makeHotel();
        PetHotelPhoto::create(['hotel_id' => $hotel->id, 'url' => 'photo.jpg', 'sort_order' => 1]);

        $this->assertInstanceOf(HasMany::class, $hotel->photos());
        $this->assertCount(1, $hotel->photos);
    }

    public function test_pet_hotel_has_one_policy(): void
    {
        $hotel = $this->makeHotel();
        PetHotelPolicy::create([
            'hotel_id' => $hotel->id,
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'cancellation_policy' => 'No refunds',
        ]);

        $this->assertInstanceOf(HasOne::class, $hotel->policy());
        $this->assertNotNull($hotel->policy);
    }

    public function test_pet_hotel_policy_is_null_when_absent(): void
    {
        $hotel = $this->makeHotel();

        $this->assertNull($hotel->policy);
    }

    public function test_pet_hotel_has_many_pricing(): void
    {
        $hotel = $this->makeHotel();
        PetHotelPricing::create(['hotel_id' => $hotel->id, 'pet_type' => 'dog', 'price_per_night' => 50]);

        $this->assertInstanceOf(HasMany::class, $hotel->pricing());
        $this->assertCount(1, $hotel->pricing);
    }

    public function test_photos_are_ordered_by_sort_order(): void
    {
        $hotel = $this->makeHotel();
        PetHotelPhoto::create(['hotel_id' => $hotel->id, 'url' => 'third.jpg', 'sort_order' => 3]);
        PetHotelPhoto::create(['hotel_id' => $hotel->id, 'url' => 'first.jpg', 'sort_order' => 1]);
        PetHotelPhoto::create(['hotel_id' => $hotel->id, 'url' => 'second.jpg', 'sort_order' => 2]);

        $photos = $hotel->photos()->get();

        $this->assertEquals('first.jpg', $photos[0]->url);
        $this->assertEquals('second.jpg', $photos[1]->url);
        $this->assertEquals('third.jpg', $photos[2]->url);
    }

    public function test_pet_hotel_cover_photo_is_nullable(): void
    {
        $hotel = $this->makeHotel(['cover_photo' => null]);

        $this->assertNull($hotel->cover_photo);
    }

    public function test_pet_hotel_has_many_bookings(): void
    {
        $hotel = $this->makeHotel();
        Booking::factory()->for($hotel, 'hotel')->create();

        $this->assertInstanceOf(HasMany::class, $hotel->bookings());
        $this->assertCount(1, $hotel->bookings);
    }

    public function test_pet_hotel_has_many_reviews(): void
    {
        $hotel = $this->makeHotel();
        Review::factory()->create(['hotel_id' => $hotel->id]);

        $this->assertInstanceOf(HasMany::class, $hotel->reviews());
        $this->assertCount(1, $hotel->reviews);
    }

    public function test_reviews_relation_excludes_hidden_reviews(): void
    {
        $hotel = $this->makeHotel();
        Review::factory()->create(['hotel_id' => $hotel->id]);
        Review::factory()->hidden()->create(['hotel_id' => $hotel->id]);

        $this->assertCount(1, $hotel->reviews);
    }

    public function test_pet_hotel_belongs_to_many_owners_with_role_pivot(): void
    {
        $hotel = $this->makeHotel();
        $owner = User::factory()->create();
        $hotel->owners()->attach($owner, ['role' => 'manager']);

        $this->assertInstanceOf(BelongsToMany::class, $hotel->owners());
        $this->assertCount(1, $hotel->owners);
        $this->assertEquals('manager', $hotel->owners->first()->pivot->role);
    }

    // ── PetHotelFacility ─────────────────────────────────────────────────────

    public function test_facility_belongs_to_hotel(): void
    {
        $hotel = $this->makeHotel();
        $facility = PetHotelFacility::create(['hotel_id' => $hotel->id, 'type' => 'grooming']);

        $this->assertInstanceOf(BelongsTo::class, $facility->hotel());
        $this->assertEquals($hotel->id, $facility->hotel->id);
    }

    // ── PetHotelPhoto ────────────────────────────────────────────────────────

    public function test_photo_belongs_to_hotel(): void
    {
        $hotel = $this->makeHotel();
        $photo = PetHotelPhoto::create(['hotel_id' => $hotel->id, 'url' => 'photo.jpg', 'sort_order' => 1]);

        $this->assertInstanceOf(BelongsTo::class, $photo->hotel());
        $this->assertEquals($hotel->id, $photo->hotel->id);
    }

    // ── PetHotelPolicy ───────────────────────────────────────────────────────

    public function test_policy_belongs_to_hotel(): void
    {
        $hotel = $this->makeHotel();
        $policy = PetHotelPolicy::create([
            'hotel_id' => $hotel->id,
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'cancellation_policy' => 'No refunds',
        ]);

        $this->assertInstanceOf(BelongsTo::class, $policy->hotel());
        $this->assertEquals($hotel->id, $policy->hotel->id);
    }

    // ── PetHotelPricing ──────────────────────────────────────────────────────

    public function test_pricing_belongs_to_hotel(): void
    {
        $hotel = $this->makeHotel();
        $pricing = PetHotelPricing::create([
            'hotel_id' => $hotel->id,
            'pet_type' => 'dog',
            'price_per_night' => 75.00,
        ]);

        $this->assertInstanceOf(BelongsTo::class, $pricing->hotel());
        $this->assertEquals($hotel->id, $pricing->hotel->id);
    }

    // ── HotelAvailability ────────────────────────────────────────────────────

    public function test_availability_belongs_to_hotel(): void
    {
        $hotel = $this->makeHotel();
        $availability = HotelAvailability::create([
            'hotel_id' => $hotel->id,
            'date' => now()->addDay()->toDateString(),
            'available_spots' => 5,
            'is_blocked' => false,
        ]);

        $this->assertInstanceOf(BelongsTo::class, $availability->hotel());
        $this->assertEquals($hotel->id, $availability->hotel->id);
    }

    public function test_availability_casts_spots_and_blocked_flag(): void
    {
        $hotel = $this->makeHotel();
        $availability = HotelAvailability::create([
            'hotel_id' => $hotel->id,
            'date' => now()->addDay()->toDateString(),
            'available_spots' => '3',
            'is_blocked' => 1,
        ]);

        $fresh = $availability->fresh();

        $this->assertSame(3, $fresh->available_spots);
        $this->assertTrue($fresh->is_blocked);
    }
}
