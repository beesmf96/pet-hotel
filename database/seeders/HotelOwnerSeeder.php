<?php

namespace Database\Seeders;

use App\Models\PetHotel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HotelOwnerSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Hotel Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $hotel = PetHotel::first();

        if ($hotel) {
            $owner->ownedHotels()->syncWithoutDetaching([$hotel->id => ['role' => 'owner']]);
        }
    }
}
