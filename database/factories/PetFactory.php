<?php

namespace Database\Factories;

use App\Models\Pet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pet>
 */
class PetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'species' => fake()->randomElement(['Dog', 'Cat', 'Bird', 'Rabbit']),
            'breed' => fake()->optional()->word(),
            'age' => fake()->optional()->numberBetween(0, 20),
        ];
    }
}
