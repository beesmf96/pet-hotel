<?php

namespace Tests\Feature;

use App\Jobs\SendBookingRequestNotification;
use App\Models\Booking;
use App\Models\HotelAvailability;
use App\Models\Pet;
use App\Models\PetHotel;
use App\Models\PetHotelPricing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    // ── Auth guards ───────────────────────────────────────────────────────────

    public function test_guest_cannot_view_booking_form(): void
    {
        $hotel = PetHotel::factory()->create();
        $this->get("/hotels/{$hotel->slug}/book")->assertRedirect('/login');
    }

    public function test_guest_cannot_view_my_bookings(): void
    {
        $this->get('/bookings')->assertRedirect('/login');
    }

    public function test_unverified_user_cannot_view_booking_form(): void
    {
        $hotel = PetHotel::factory()->create();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get("/hotels/{$hotel->slug}/book")
            ->assertRedirect('/email/verify');
    }

    // ── Create form ───────────────────────────────────────────────────────────

    public function test_user_can_view_booking_form(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();

        $this->actingAs($user)
            ->get("/hotels/{$hotel->slug}/book")
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Bookings/BookingFormPage')
                ->has('hotel')
                ->has('pets')
            );
    }

    public function test_booking_form_includes_user_pets(): void
    {
        $user = User::factory()->create();
        $user->pets()->createMany([
            ['name' => 'Buddy', 'species' => 'dog'],
            ['name' => 'Whiskers', 'species' => 'cat'],
        ]);
        $hotel = PetHotel::factory()->create();

        $this->actingAs($user)
            ->get("/hotels/{$hotel->slug}/book")
            ->assertInertia(fn ($page) => $page->has('pets', 2));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_user_can_create_booking(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'dog']);
        $hotel->pricing()->create(['pet_type' => 'dog', 'price_per_night' => 50]);

        $response = $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-04',
            'notes' => 'Needs medication at 8am',
        ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'pet_id' => $pet->id,
            'status' => 'pending',
            'total_price' => 150.00,
        ]);

        $response->assertRedirect();
        Queue::assertPushed(SendBookingRequestNotification::class);
    }

    public function test_booking_calculates_total_price_correctly(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'cat']);
        $hotel->pricing()->create(['pet_type' => 'cat', 'price_per_night' => 75.50]);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => '2026-07-10',
            'check_out' => '2026-07-12',
        ]);

        $this->assertDatabaseHas('bookings', [
            'total_price' => 151.00,
        ]);
    }

    public function test_booking_with_no_matching_pricing_sets_zero_total(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $pet = $user->pets()->create(['name' => 'Tweety', 'species' => 'bird']);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => '2026-07-10',
            'check_out' => '2026-07-12',
        ]);

        $this->assertDatabaseHas('bookings', ['total_price' => 0]);
    }

    public function test_user_cannot_book_with_another_users_pet(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $foreignPet = $other->pets()->create(['name' => 'Whiskers', 'species' => 'cat']);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $foreignPet->id,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-03',
        ])->assertStatus(404);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [])
            ->assertSessionHasErrors(['pet_id', 'check_in', 'check_out']);
    }

    public function test_store_validates_check_out_after_check_in(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'dog']);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => '2026-06-05',
            'check_out' => '2026-06-03',
        ])->assertSessionHasErrors(['check_out']);
    }

    // ── Confirmation page ─────────────────────────────────────────────────────

    public function test_user_can_view_own_booking_confirmation(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking($user);

        $this->actingAs($user)
            ->get("/bookings/{$booking->id}/confirmation")
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Bookings/BookingConfirmationPage'));
    }

    public function test_user_cannot_view_another_users_confirmation(): void
    {
        $owner = User::factory()->create();
        $booking = $this->makeBooking($owner);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get("/bookings/{$booking->id}/confirmation")
            ->assertStatus(403);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_user_can_view_my_bookings(): void
    {
        $user = User::factory()->create();
        $this->makeBooking($user);
        $this->makeBooking($user);

        $this->actingAs($user)
            ->get('/bookings')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->has('bookings', 2));
    }

    public function test_user_only_sees_own_bookings(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->makeBooking($user);
        $this->makeBooking($other);

        $this->actingAs($user)
            ->get('/bookings')
            ->assertInertia(fn ($page) => $page->has('bookings', 1));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_user_can_view_own_booking_detail(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking($user);

        $this->actingAs($user)
            ->get("/bookings/{$booking->id}")
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Bookings/BookingDetailPage'));
    }

    public function test_user_cannot_view_other_booking_detail(): void
    {
        $owner = User::factory()->create();
        $booking = $this->makeBooking($owner);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get("/bookings/{$booking->id}")
            ->assertStatus(403);
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function test_user_can_cancel_pending_booking(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking($user, 'pending');

        $this->actingAs($user)
            ->patch("/bookings/{$booking->id}/cancel")
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
    }

    public function test_user_cannot_cancel_confirmed_booking(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking($user, 'confirmed');

        $this->actingAs($user)
            ->patch("/bookings/{$booking->id}/cancel")
            ->assertStatus(403);
    }

    public function test_user_cannot_cancel_another_users_booking(): void
    {
        $owner = User::factory()->create();
        $booking = $this->makeBooking($owner, 'pending');
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->patch("/bookings/{$booking->id}/cancel")
            ->assertStatus(403);
    }

    // ── Availability blocking ─────────────────────────────────────────────────

    public function test_confirming_booking_decrements_availability(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'dog']);

        HotelAvailability::create(['hotel_id' => $hotel->id, 'date' => '2026-08-01', 'available_spots' => 5, 'is_blocked' => false]);
        HotelAvailability::create(['hotel_id' => $hotel->id, 'date' => '2026-08-02', 'available_spots' => 5, 'is_blocked' => false]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'pet_id' => $pet->id,
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-03',
            'status' => 'pending',
            'total_price' => 100,
        ]);

        $booking->update(['status' => 'confirmed']);

        // Use the model to avoid date-format differences between SQLite and PostgreSQL
        $spots = fn ($date) => HotelAvailability::where('hotel_id', $hotel->id)
            ->whereDate('date', $date)->value('available_spots');

        $this->assertEquals(4, $spots('2026-08-01'));
        $this->assertEquals(4, $spots('2026-08-02'));
    }

    public function test_cancelling_confirmed_booking_restores_availability(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'dog']);

        HotelAvailability::create(['hotel_id' => $hotel->id, 'date' => '2026-08-01', 'available_spots' => 4, 'is_blocked' => false]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'pet_id' => $pet->id,
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-02',
            'status' => 'confirmed',
            'total_price' => 50,
        ]);

        $booking->update(['status' => 'cancelled']);

        $spots = HotelAvailability::where('hotel_id', $hotel->id)
            ->whereDate('date', '2026-08-01')->value('available_spots');

        $this->assertEquals(5, $spots);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function makeBooking(User $user, string $status = 'pending'): Booking
    {
        $hotel = PetHotel::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'dog']);

        return Booking::create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'pet_id' => $pet->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'status' => $status,
            'total_price' => 100.00,
        ]);
    }
}
