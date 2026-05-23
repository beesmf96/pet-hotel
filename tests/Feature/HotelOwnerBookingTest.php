<?php

namespace Tests\Feature;

use App\Filament\HotelOwner\Resources\BookingResource;
use App\Models\Booking;
use App\Models\PetHotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HotelOwnerBookingTest extends TestCase
{
    use RefreshDatabase;

    // ── Tenant isolation ──────────────────────────────────────────────────────

    public function test_owner_only_sees_their_hotels_bookings(): void
    {
        $hotelA = PetHotel::factory()->create();
        $hotelB = PetHotel::factory()->create();

        $ownerA = User::factory()->hotelOwner($hotelA)->create();

        $guest = User::factory()->create();
        $pet = $guest->pets()->create(['name' => 'Buddy', 'species' => 'dog']);

        $bookingA = Booking::factory()->create([
            'hotel_id' => $hotelA->id,
            'user_id' => $guest->id,
            'pet_id' => $pet->id,
        ]);
        $bookingB = Booking::factory()->create([
            'hotel_id' => $hotelB->id,
            'user_id' => $guest->id,
            'pet_id' => $pet->id,
        ]);

        $this->actingAs($ownerA);

        $query = BookingResource::getEloquentQuery();
        $ids = $query->pluck('id');

        $this->assertTrue($ids->contains($bookingA->id), 'Owner A should see hotel A booking');
        $this->assertFalse($ids->contains($bookingB->id), 'Owner A must NOT see hotel B booking');
    }

    public function test_owner_a_cannot_see_owner_b_bookings(): void
    {
        $hotelA = PetHotel::factory()->create();
        $hotelB = PetHotel::factory()->create();

        $ownerA = User::factory()->hotelOwner($hotelA)->create();
        $ownerB = User::factory()->hotelOwner($hotelB)->create();

        $guest = User::factory()->create();
        $pet = $guest->pets()->create(['name' => 'Whiskers', 'species' => 'cat']);

        Booking::factory()->create([
            'hotel_id' => $hotelB->id,
            'user_id' => $guest->id,
            'pet_id' => $pet->id,
        ]);

        $this->actingAs($ownerA);

        $ids = BookingResource::getEloquentQuery()->pluck('id');

        $this->assertCount(0, $ids, 'Owner A must see zero bookings when only hotel B has bookings');
    }

    // ── No hotel assigned → 403 ───────────────────────────────────────────────

    public function test_user_with_no_hotel_gets_403_on_bookings_query(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->expectException(HttpException::class);

        BookingResource::getEloquentQuery();
    }
}
