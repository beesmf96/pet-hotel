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

    /**
     * Demo date changes for the next three months: every hotel is closed on
     * Sundays and takes three pets on Saturdays. Every other night uses the
     * hotel's normal capacity, so it gets no row.
     */
    public function run(): void
    {
        $hotels = PetHotel::all();
        $today = Carbon::today();
        $end = $today->copy()->addMonths(3)->endOfMonth();

        foreach ($hotels as $hotel) {
            for ($cursor = $today->copy()->startOfMonth(); $cursor->lte($end); $cursor->addDay()) {
                $override = match ($cursor->dayOfWeek) {
                    Carbon::SUNDAY => ['is_blocked' => true, 'capacity' => null],
                    Carbon::SATURDAY => ['is_blocked' => false, 'capacity' => 3],
                    default => null,
                };

                if ($override) {
                    HotelAvailability::updateOrCreate(
                        ['hotel_id' => $hotel->id, 'date' => $cursor->format('Y-m-d')],
                        $override,
                    );
                }
            }
        }
    }
}
