<?php

namespace Database\Seeders;

use App\Models\PetHotel;
use App\Models\PetHotelFacility;
use App\Models\PetHotelPhoto;
use App\Models\PetHotelPolicy;
use App\Models\PetHotelPricing;
use Illuminate\Database\Seeder;

class PetHotelSeeder extends Seeder
{
    public function run(): void
    {
        $facilityTypes = ['grooming', 'play_area', 'vet_care', 'swimming_pool', 'training', 'outdoor_walks', 'webcam', '24h_care'];
        $petTypes = ['dog', 'cat', 'rabbit', 'bird', 'other'];

        PetHotel::factory(10)->create()->each(function (PetHotel $hotel) use ($facilityTypes, $petTypes) {
            $selectedFacilities = fake()->randomElements($facilityTypes, fake()->numberBetween(3, 7));
            foreach ($selectedFacilities as $type) {
                PetHotelFacility::create(['hotel_id' => $hotel->id, 'type' => $type]);
            }

            foreach (array_slice($petTypes, 0, fake()->numberBetween(2, 5)) as $i => $petType) {
                PetHotelPhoto::create([
                    'hotel_id' => $hotel->id,
                    'url' => 'https://picsum.photos/seed/'.$hotel->id.'-'.$i.'/800/600',
                    'sort_order' => $i,
                ]);
            }

            PetHotelPolicy::create([
                'hotel_id' => $hotel->id,
                'check_in_time' => '14:00',
                'check_out_time' => '11:00',
                'cancellation_policy' => 'Free cancellation up to 48 hours before check-in. After that, the first night is non-refundable.',
            ]);

            foreach ($petTypes as $petType) {
                PetHotelPricing::create([
                    'hotel_id' => $hotel->id,
                    'pet_type' => $petType,
                    'price_per_night' => fake()->randomFloat(2, 30, 150),
                ]);
            }
        });
    }
}
