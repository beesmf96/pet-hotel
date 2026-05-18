<?php

namespace Database\Seeders;

use App\Models\HotelAvailability;
use App\Models\PetHotel;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HotelAvailabilitySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $hotels = PetHotel::all();
        $today = Carbon::today();
        $end = $today->copy()->addMonths(3)->endOfMonth();

        foreach ($hotels as $hotel) {
            $cursor = $today->copy()->startOfMonth();
            while ($cursor->lte($end)) {
                $dayOfWeek = $cursor->dayOfWeek;
                $isBlocked = $dayOfWeek === Carbon::SUNDAY;
                $spots = match (true) {
                    $isBlocked => 0,
                    $dayOfWeek === Carbon::SATURDAY => rand(1, 3),
                    default => rand(3, 10),
                };

                HotelAvailability::upsert([
                    [
                        'hotel_id' => $hotel->id,
                        'date' => $cursor->format('Y-m-d'),
                        'available_spots' => $spots,
                        'is_blocked' => $isBlocked,
                    ],
                ], ['hotel_id', 'date'], ['available_spots', 'is_blocked']);

                $cursor->addDay();
            }
        }
    }
}
