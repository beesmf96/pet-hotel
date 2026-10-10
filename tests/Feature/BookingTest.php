<?php

namespace Tests\Feature;

use App\Exceptions\BookingDoesNotFit;
use App\Jobs\SendBookingConfirmationNotification;
use App\Jobs\SendBookingRequestNotification;
use App\Models\Booking;
use App\Models\HotelAvailability;
use App\Models\Pet;
use App\Models\PetHotel;
use App\Models\User;
use App\Support\Availability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
            'check_in' => $this->futureDate(30),
            'check_out' => $this->futureDate(33),
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
            'check_in' => $this->futureDate(30),
            'check_out' => $this->futureDate(32),
        ]);

        $this->assertDatabaseHas('bookings', [
            'total_price' => 151.00,
        ]);
    }

    public function test_booking_is_rejected_when_the_hotel_has_no_price_for_the_pet_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create(['name' => 'Paws Inn']);
        $hotel->pricing()->create(['pet_type' => 'dog', 'price_per_night' => 50]);
        $pet = $user->pets()->create(['name' => 'Tweety', 'species' => 'bird']);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => $this->futureDate(30),
            'check_out' => $this->futureDate(32),
        ])->assertSessionHasErrors([
            'pet_id' => "Paws Inn has no price for Tweety's pet type (Bird) yet, so Tweety cannot be booked here.",
        ]);

        $this->assertDatabaseCount('bookings', 0);
        Queue::assertNothingPushed();
    }

    public function test_total_is_exact_for_prices_a_float_would_round(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $hotel->pricing()->create(['pet_type' => 'dog', 'price_per_night' => '0.10']);
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'dog']);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => $this->futureDate(30),
            'check_out' => $this->futureDate(33),
        ]);

        $this->assertSame('0.30', Booking::sole()->total_price);
    }

    public function test_user_cannot_book_with_another_users_pet(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $foreignPet = $other->pets()->create(['name' => 'Whiskers', 'species' => 'cat']);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $foreignPet->id,
            'check_in' => $this->futureDate(30),
            'check_out' => $this->futureDate(32),
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
            'check_in' => $this->futureDate(35),
            'check_out' => $this->futureDate(33),
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

    // ── Availability ──────────────────────────────────────────────────────────

    private function spotsLeft(PetHotel $hotel, string $date): int
    {
        $night = Carbon::parse($date);

        return Availability::nights($hotel->fresh(), $night, $night)[$date]['spots_left'];
    }

    public function test_confirming_booking_takes_a_spot_on_each_night(): void
    {
        Queue::fake();

        $hotel = PetHotel::factory()->create(['capacity' => 5]);
        $booking = Booking::factory()->for($hotel, 'hotel')->create([
            'check_in' => '2030-08-01',
            'check_out' => '2030-08-03',
            'status' => 'pending',
        ]);

        $this->assertEquals(5, $this->spotsLeft($hotel, '2030-08-01'));

        $booking->confirm();

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertEquals(4, $this->spotsLeft($hotel, '2030-08-01'));
        $this->assertEquals(4, $this->spotsLeft($hotel, '2030-08-02'));
        $this->assertEquals(5, $this->spotsLeft($hotel, '2030-08-03'));
        Queue::assertPushed(SendBookingConfirmationNotification::class);
    }

    public function test_cancelling_confirmed_booking_frees_its_spot(): void
    {
        Queue::fake();

        $hotel = PetHotel::factory()->create(['capacity' => 5]);
        $booking = Booking::factory()->for($hotel, 'hotel')->confirmed()->create([
            'check_in' => '2030-08-01',
            'check_out' => '2030-08-02',
        ]);

        $this->assertEquals(4, $this->spotsLeft($hotel, '2030-08-01'));

        $booking->update(['status' => 'cancelled']);

        $this->assertEquals(5, $this->spotsLeft($hotel, '2030-08-01'));
    }

    public function test_confirm_is_refused_when_a_night_is_full(): void
    {
        Queue::fake();

        $hotel = PetHotel::factory()->create(['capacity' => 1]);
        Booking::factory()->for($hotel, 'hotel')->confirmed()->create([
            'check_in' => '2030-08-02',
            'check_out' => '2030-08-03',
        ]);
        $booking = Booking::factory()->for($hotel, 'hotel')->create([
            'check_in' => '2030-08-01',
            'check_out' => '2030-08-04',
            'status' => 'pending',
        ]);

        try {
            $booking->confirm();
            $this->fail('Expected BookingDoesNotFit.');
        } catch (BookingDoesNotFit) {
            $this->assertSame('pending', $booking->fresh()->status);
            Queue::assertNotPushed(SendBookingConfirmationNotification::class);
        }
    }

    public function test_confirm_is_refused_when_a_night_is_closed(): void
    {
        $hotel = PetHotel::factory()->create();
        HotelAvailability::create(['hotel_id' => $hotel->id, 'date' => '2030-08-02', 'is_blocked' => true]);
        $booking = Booking::factory()->for($hotel, 'hotel')->create([
            'check_in' => '2030-08-01',
            'check_out' => '2030-08-04',
            'status' => 'pending',
        ]);

        $this->expectException(BookingDoesNotFit::class);

        $booking->confirm();
    }

    public function test_store_rejects_a_stay_over_a_closed_night(): void
    {
        Queue::fake();

        [$user, $hotel, $pet] = $this->bookable();
        HotelAvailability::create(['hotel_id' => $hotel->id, 'date' => $this->futureDate(31), 'is_blocked' => true]);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => $this->futureDate(30),
            'check_out' => $this->futureDate(33),
        ])->assertSessionHasErrors('check_in');

        $this->assertDatabaseCount('bookings', 0);
        Queue::assertNothingPushed();
    }

    public function test_store_rejects_a_stay_over_a_full_night(): void
    {
        [$user, $hotel, $pet] = $this->bookable(capacity: 1);
        Booking::factory()->for($hotel, 'hotel')->confirmed()->create([
            'check_in' => $this->futureDate(32),
            'check_out' => $this->futureDate(33),
        ]);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => $this->futureDate(30),
            'check_out' => $this->futureDate(33),
        ])->assertSessionHasErrors('check_in');

        $this->assertDatabaseMissing('bookings', ['user_id' => $user->id]);
    }

    public function test_store_allows_check_out_on_a_closed_day(): void
    {
        Queue::fake();

        [$user, $hotel, $pet] = $this->bookable();
        HotelAvailability::create(['hotel_id' => $hotel->id, 'date' => $this->futureDate(33), 'is_blocked' => true]);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => $this->futureDate(30),
            'check_out' => $this->futureDate(33),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bookings', ['user_id' => $user->id, 'status' => 'pending']);
    }

    public function test_store_ignores_pending_requests_when_counting_spots(): void
    {
        Queue::fake();

        [$user, $hotel, $pet] = $this->bookable(capacity: 1);
        Booking::factory()->for($hotel, 'hotel')->create([
            'check_in' => $this->futureDate(30),
            'check_out' => $this->futureDate(31),
            'status' => 'pending',
        ]);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => $this->futureDate(30),
            'check_out' => $this->futureDate(31),
        ])->assertSessionHasNoErrors();
    }

    // ── Check-in and check-out times ──────────────────────────────────────────

    public function test_booking_pages_pass_the_hotel_times(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking($user);
        $booking->hotel->policy()->create(['check_in_time' => '14:00', 'check_out_time' => '11:30']);
        $times = ['check_in' => '2:00 PM', 'check_out' => '11:30 AM'];

        $this->actingAs($user)->get("/hotels/{$booking->hotel->slug}/book")
            ->assertInertia(fn ($page) => $page->where('times', $times));
        $this->actingAs($user)->get("/bookings/{$booking->id}/confirmation")
            ->assertInertia(fn ($page) => $page->where('times', $times));
        $this->actingAs($user)->get("/bookings/{$booking->id}")
            ->assertInertia(fn ($page) => $page->where('times', $times));
    }

    public function test_booking_pages_pass_no_times_without_a_policy(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBooking($user);

        $this->actingAs($user)->get("/hotels/{$booking->hotel->slug}/book")
            ->assertInertia(fn ($page) => $page->where('times', null));
        $this->actingAs($user)->get("/bookings/{$booking->id}")
            ->assertInertia(fn ($page) => $page->where('times', null));
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    /**
     * Booking dates must stay in the future: StoreBookingRequest enforces
     * `after_or_equal:today` on check_in, so hardcoded literals silently rot
     * once that date passes.
     */
    private function futureDate(int $daysFromNow): string
    {
        return now()->addDays($daysFromNow)->toDateString();
    }

    /** @return array{User, PetHotel, Pet} */
    private function bookable(int $capacity = 10): array
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create(['capacity' => $capacity]);
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'dog']);
        $hotel->pricing()->create(['pet_type' => 'dog', 'price_per_night' => 50]);

        return [$user, $hotel, $pet];
    }

    private function makeBooking(User $user, string $status = 'pending'): Booking
    {
        $hotel = PetHotel::factory()->create();
        $pet = $user->pets()->create(['name' => 'Buddy', 'species' => 'dog']);

        return Booking::create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'pet_id' => $pet->id,
            'check_in' => $this->futureDate(60),
            'check_out' => $this->futureDate(62),
            'status' => $status,
            'total_price' => 100.00,
        ]);
    }
}
