<?php

namespace Database\Seeders;

use App\Models\PetHotel;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Fixed data for the Playwright suite in e2e/. Runs only against the e2e
 * database (see playwright.config.js); never part of DatabaseSeeder.
 * Every account's password is "password". Addresses end in @e2e.test so the
 * suite can find its own mail in the shared Mailpit.
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        // A guard, not a formality: a mistyped command once ran an e2e reset
        // against the dev database (docs-src/knowledge/zsh-does-not-split-unquoted-variables.md).
        if (! app()->environment('e2e')) {
            throw new RuntimeException('E2eSeeder only runs with APP_ENV=e2e.');
        }

        $hotel = PetHotel::factory()->create([
            'name' => 'E2E Paws Hotel',
            'slug' => 'e2e-paws',
            'city' => 'Kuala Lumpur',
            'capacity' => 2,
        ]);
        $hotel->policy()->create([
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'cancellation_policy' => 'Free cancellation while pending.',
        ]);
        // No bird price on purpose: the no-price rule is tested with a bird.
        $hotel->pricing()->createMany([
            ['pet_type' => 'dog', 'price_per_night' => 650],
            ['pet_type' => 'cat', 'price_per_night' => 45.5],
        ]);

        User::factory()->hotelOwner($hotel)->create(['name' => 'Olive Owner', 'email' => 'owner@e2e.test']);
        User::factory()->admin()->create(['name' => 'Ada Admin', 'email' => 'admin@e2e.test']);

        $alice = User::factory()->create(['name' => 'Alice Guest', 'email' => 'alice@e2e.test']);
        $alice->pets()->createMany([
            ['name' => 'Buddy', 'species' => 'dog', 'breed' => 'Beagle', 'age' => 3],
            ['name' => 'Tweety', 'species' => 'bird'],
        ]);

        $ben = User::factory()->create(['name' => 'Ben Guest', 'email' => 'ben@e2e.test']);
        $ben->pets()->create(['name' => 'Rex', 'species' => 'dog']);
    }
}
