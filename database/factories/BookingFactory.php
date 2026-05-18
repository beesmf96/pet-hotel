<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Pet;
use App\Models\PetHotel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('now', '+1 month');
        $checkOut = fake()->dateTimeBetween($checkIn, '+2 months');

        return [
            'user_id' => User::factory(),
            'hotel_id' => PetHotel::factory(),
            'pet_id' => fn (array $attrs) => Pet::factory()->create(['user_id' => $attrs['user_id']]),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => 'pending',
            'notes' => null,
            'total_price' => fake()->randomFloat(2, 50, 500),
        ];
    }

    public function completed(): static
    {
        $checkIn = fake()->dateTimeBetween('-3 months', '-7 days');
        $checkOut = fake()->dateTimeBetween($checkIn, '-1 day');

        return $this->state([
            'status' => 'completed',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed']);
    }
}
