<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\PetHotel;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'hotel_id' => PetHotel::factory(),
            'booking_id' => Booking::factory()->completed(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional(0.8)->paragraph(),
            'is_visible' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_visible' => false]);
    }
}
