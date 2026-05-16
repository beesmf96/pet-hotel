<?php

namespace Database\Factories;

use App\Models\PetHotel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PetHotel>
 */
class PetHotelFactory extends Factory
{
    protected $model = PetHotel::class;

    public function definition(): array
    {
        $name = fake()->company().' Pet Hotel';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->paragraphs(3, true),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'lat' => fake()->latitude(1.2, 1.5),
            'lng' => fake()->longitude(103.6, 104.0),
            'cover_photo' => null,
        ];
    }
}
