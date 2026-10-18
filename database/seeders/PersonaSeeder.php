<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\HotelAvailability;
use App\Models\Pet;
use App\Models\PetHotel;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * The people in docs-src/personas.md, with the hotels and bookings their
 * stories need. Runs last in DatabaseSeeder, after HotelAvailabilitySeeder,
 * so its hotels keep their own date overrides (the demo closes every other
 * hotel on Sundays). Every account's password is "password".
 *
 * The stories are set on the next 1–5 November, so the data stays useful
 * whenever it is seeded.
 */
class PersonaSeeder extends Seeder
{
    public function run(): void
    {
        $holiday = Carbon::create(Carbon::today()->year, 11, 1);
        if ($holiday->lt(Carbon::today())) {
            $holiday->addYear();
        }

        $rahmans = $this->hotel([
            'name' => "Rahman's Paws House",
            'slug' => 'rahmans-paws-house',
            'description' => 'A family-run boarding house in a quiet corner of Shah Alam. Dogs and cats sleep indoors, with two walks a day and a shaded garden.',
            'address' => '12 Jalan Anggerik, Seksyen 31',
            'city' => 'Shah Alam',
            'lat' => 3.0733,
            'lng' => 101.5185,
            'capacity' => 6,
        ], ['dog' => 60, 'cat' => 45], ['outdoor_walks', 'play_area', '24h_care']);

        $whiskers = $this->hotel([
            'name' => 'Whiskers & Wags',
            'slug' => 'whiskers-and-wags',
            'description' => 'A small, calm boarding hotel in Petaling Jaya with separate rooms for cats and dogs, and photo updates every evening.',
            'address' => '8 Jalan SS 2/24',
            'city' => 'Petaling Jaya',
            'lat' => 3.1186,
            'lng' => 101.6215,
            'capacity' => 4,
        ], ['dog' => 75, 'cat' => 55], ['grooming', 'webcam', 'vet_care']);

        // Closed one night of the holiday, so a 1–5 Nov search hides it.
        HotelAvailability::create([
            'hotel_id' => $whiskers->id,
            'date' => $holiday->copy()->addDays(2)->format('Y-m-d'),
            'is_blocked' => true,
        ]);

        User::factory()->hotelOwner($rahmans)->create([
            'name' => 'Rahman Ismail',
            'email' => 'rahman@example.com',
            'phone' => '012-000 0004',
        ]);

        $aisyah = User::factory()->create([
            'name' => 'Aisyah Rahim',
            'email' => 'aisyah@example.com',
            'phone' => '012-000 0001',
            'preferred_location' => 'Petaling Jaya',
        ]);
        $aisyah->pets()->create([
            'name' => 'Milo',
            'species' => 'dog',
            'breed' => 'Golden Retriever',
            'age' => 4,
            'special_needs' => 'Anxious with new people. Needs a slow introduction.',
        ]);

        $daniel = User::factory()->create([
            'name' => 'Daniel Tan',
            'email' => 'daniel@example.com',
            'phone' => '012-000 0002',
            'preferred_location' => 'Shah Alam',
        ]);
        $bruno = $daniel->pets()->create(['name' => 'Bruno', 'species' => 'dog', 'breed' => 'Labrador', 'age' => 6]);
        $oyen = $daniel->pets()->create(['name' => 'Oyen', 'species' => 'cat', 'breed' => 'Domestic Shorthair', 'age' => 2]);

        $meiLing = User::factory()->create([
            'name' => 'Lim Mei Ling',
            'email' => 'meiling@example.com',
            'phone' => '012-000 0003',
            'preferred_location' => 'Kuala Lumpur',
        ]);
        $meiLing->pets()->create(['name' => 'Mochi', 'species' => 'cat', 'breed' => 'British Shorthair', 'age' => 1]);

        // A past stay Daniel has not reviewed yet.
        $lastStay = Carbon::today()->subWeeks(6);
        $this->book($daniel, $bruno, $rahmans, $lastStay, $lastStay->copy()->addDays(2), 'completed');

        // Daniel's cat is already waiting on Rahman, so a second request for
        // Oyen on the same nights is refused.
        $this->book($daniel, $oyen, $rahmans, $holiday, $holiday->copy()->addDays(2), 'pending',
            'Oyen hides for the first day. Please leave him a box.');

        // Four other guests fill Rahman's house for the holiday, leaving two
        // spots each night: Aisyah can still book, but not many more.
        foreach (['dog', 'dog', 'cat', 'dog'] as $species) {
            $guest = User::factory()->create();
            $pet = Pet::factory()->create(['user_id' => $guest->id, 'species' => $species]);
            $this->book($guest, $pet, $rahmans, $holiday, $holiday->copy()->addDays(4), 'confirmed');
        }
    }

    /**
     * @param  array<string, float>  $prices  per pet type
     * @param  list<string>  $facilities
     */
    private function hotel(array $attributes, array $prices, array $facilities): PetHotel
    {
        $hotel = PetHotel::factory()->create($attributes);

        $hotel->policy()->create([
            'check_in_time' => '14:00',
            'check_out_time' => '11:00',
            'cancellation_policy' => 'Free cancellation up to 48 hours before check-in.',
        ]);
        foreach ($prices as $petType => $price) {
            $hotel->pricing()->create(['pet_type' => $petType, 'price_per_night' => $price]);
        }
        foreach ($facilities as $type) {
            $hotel->facilities()->create(['type' => $type]);
        }
        foreach ([0, 1] as $i) {
            $hotel->photos()->create([
                'url' => 'https://picsum.photos/seed/'.$hotel->slug.'-'.$i.'/800/600',
                'sort_order' => $i,
            ]);
        }

        return $hotel;
    }

    private function book(User $user, Pet $pet, PetHotel $hotel, Carbon $checkIn, Carbon $checkOut, string $status, ?string $notes = null): Booking
    {
        $price = $hotel->pricing()->where('pet_type', $pet->species)->value('price_per_night');

        return Booking::create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'pet_id' => $pet->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $status,
            'notes' => $notes,
            'total_price' => Money::fromSen(Money::toSen($price) * (int) $checkIn->diffInDays($checkOut)),
        ]);
    }
}
