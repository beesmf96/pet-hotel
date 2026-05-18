<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\PetHotel;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    // ── Public review list ────────────────────────────────────────────────────

    public function test_guest_can_view_public_reviews_for_hotel(): void
    {
        $hotel = PetHotel::factory()->create();
        $user = User::factory()->create();
        $booking = Booking::factory()->completed()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);
        Review::factory()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'booking_id' => $booking->id,
            'rating' => 4,
        ]);

        $this->get("/hotels/{$hotel->slug}/reviews")
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Hotels/ReviewsPage')
                ->has('reviews')
                ->has('average_rating')
                ->has('reviews_count')
            );
    }

    public function test_hidden_reviews_are_excluded_from_public_list(): void
    {
        $hotel = PetHotel::factory()->create();
        $user = User::factory()->create();
        $booking = Booking::factory()->completed()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);
        Review::factory()->hidden()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'booking_id' => $booking->id,
        ]);

        $response = $this->get("/hotels/{$hotel->slug}/reviews");
        $response->assertInertia(fn ($page) => $page
            ->where('reviews_count', 0)
        );
    }

    // ── Hotel profile includes reviews ────────────────────────────────────────

    public function test_hotel_profile_includes_reviews_and_average_rating(): void
    {
        $hotel = PetHotel::factory()->create();
        $user = User::factory()->create();

        foreach ([4, 5] as $rating) {
            $booking = Booking::factory()->completed()->create([
                'user_id' => $user->id,
                'hotel_id' => $hotel->id,
            ]);
            Review::factory()->create([
                'user_id' => $user->id,
                'hotel_id' => $hotel->id,
                'booking_id' => $booking->id,
                'rating' => $rating,
            ]);
        }

        $this->get("/hotels/{$hotel->slug}")
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Hotels/HotelProfilePage')
                ->where('average_rating', 4.5)
                ->where('reviews_count', 2)
                ->has('reviews', 2)
            );
    }

    // ── Submit review ─────────────────────────────────────────────────────────

    public function test_guest_cannot_submit_review(): void
    {
        $hotel = PetHotel::factory()->create();

        $this->post("/hotels/{$hotel->slug}/reviews", ['booking_id' => 1, 'rating' => 5])
            ->assertRedirect('/login');
    }

    public function test_unverified_user_cannot_submit_review(): void
    {
        $hotel = PetHotel::factory()->create();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post("/hotels/{$hotel->slug}/reviews", ['booking_id' => 1, 'rating' => 5])
            ->assertRedirect('/email/verify');
    }

    public function test_user_can_submit_review_for_completed_booking(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $booking = Booking::factory()->completed()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);

        $this->actingAs($user)
            ->post("/hotels/{$hotel->slug}/reviews", [
                'booking_id' => $booking->id,
                'rating' => 5,
                'comment' => 'Excellent stay!',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'booking_id' => $booking->id,
            'rating' => 5,
            'comment' => 'Excellent stay!',
            'is_visible' => true,
        ]);
    }

    public function test_user_cannot_submit_review_for_pending_booking(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post("/hotels/{$hotel->slug}/reviews", [
                'booking_id' => $booking->id,
                'rating' => 4,
            ])
            ->assertStatus(404);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_cannot_submit_review_for_confirmed_but_not_completed_booking(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);

        $this->actingAs($user)
            ->post("/hotels/{$hotel->slug}/reviews", [
                'booking_id' => $booking->id,
                'rating' => 3,
            ])
            ->assertStatus(404);
    }

    public function test_user_cannot_submit_review_for_another_users_booking(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $booking = Booking::factory()->completed()->create([
            'user_id' => $owner->id,
            'hotel_id' => $hotel->id,
        ]);

        $this->actingAs($other)
            ->post("/hotels/{$hotel->slug}/reviews", [
                'booking_id' => $booking->id,
                'rating' => 4,
            ])
            ->assertStatus(404);
    }

    public function test_user_cannot_submit_duplicate_review_for_same_booking(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $booking = Booking::factory()->completed()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);
        Review::factory()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'booking_id' => $booking->id,
        ]);

        $this->actingAs($user)
            ->post("/hotels/{$hotel->slug}/reviews", [
                'booking_id' => $booking->id,
                'rating' => 5,
            ])
            ->assertStatus(404);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_review_requires_rating(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $booking = Booking::factory()->completed()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);

        $this->actingAs($user)
            ->post("/hotels/{$hotel->slug}/reviews", [
                'booking_id' => $booking->id,
            ])
            ->assertSessionHasErrors('rating');
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $booking = Booking::factory()->completed()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);

        $this->actingAs($user)
            ->post("/hotels/{$hotel->slug}/reviews", [
                'booking_id' => $booking->id,
                'rating' => 6,
            ])
            ->assertSessionHasErrors('rating');
    }

    public function test_user_cannot_submit_review_for_cancelled_booking(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'status' => 'cancelled',
        ]);

        $this->actingAs($user)
            ->post("/hotels/{$hotel->slug}/reviews", [
                'booking_id' => $booking->id,
                'rating' => 4,
            ])
            ->assertStatus(404);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_user_cannot_submit_review_for_booking_at_different_hotel(): void
    {
        $user = User::factory()->create();
        $hotelA = PetHotel::factory()->create();
        $hotelB = PetHotel::factory()->create();
        $booking = Booking::factory()->completed()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotelA->id,
        ]);

        $this->actingAs($user)
            ->post("/hotels/{$hotelB->slug}/reviews", [
                'booking_id' => $booking->id,
                'rating' => 5,
            ])
            ->assertStatus(404);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_comment_must_not_exceed_1000_characters(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $booking = Booking::factory()->completed()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);

        $this->actingAs($user)
            ->post("/hotels/{$hotel->slug}/reviews", [
                'booking_id' => $booking->id,
                'rating' => 4,
                'comment' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors('comment');
    }

    // ── My Bookings shows has_review ──────────────────────────────────────────

    public function test_my_bookings_includes_has_review_flag(): void
    {
        $user = User::factory()->create();
        $hotel = PetHotel::factory()->create();
        $booking = Booking::factory()->completed()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
        ]);
        Review::factory()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'booking_id' => $booking->id,
        ]);

        $this->actingAs($user)
            ->get('/bookings')
            ->assertInertia(fn ($page) => $page
                ->component('Bookings/MyBookingsPage')
                ->where('bookings.0.has_review', true)
            );
    }
}
