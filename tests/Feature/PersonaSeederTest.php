<?php

namespace Tests\Feature;

use App\Models\PetHotel;
use App\Models\User;
use App\Support\Availability;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PersonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The seeded people in docs-src/personas.md: their stories only work if the
 * data around the next 1–5 November is exactly as the doc describes.
 */
class PersonaSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_creates_the_personas_and_their_pets(): void
    {
        $this->seed(PersonaSeeder::class);

        $this->assertSame(['Milo'], $this->petNames('aisyah@example.com'));
        $this->assertSame(['Bruno', 'Oyen'], $this->petNames('daniel@example.com'));
        $this->assertSame(['Mochi'], $this->petNames('meiling@example.com'));

        $rahman = User::where('email', 'rahman@example.com')->sole();
        $this->assertSame(['rahmans-paws-house'], $rahman->ownedHotels()->pluck('slug')->all());
    }

    public function test_rahmans_house_has_two_spots_left_on_every_night_of_the_holiday(): void
    {
        Carbon::setTestNow('2026-10-18');

        $this->seed(PersonaSeeder::class);

        $hotel = PetHotel::where('slug', 'rahmans-paws-house')->sole();
        $nights = Availability::nights($hotel, Carbon::parse('2026-11-01'), Carbon::parse('2026-11-04'));
        $this->assertSame([2, 2, 2, 2], array_values(array_column($nights, 'spots_left')));

        $daniel = User::where('email', 'daniel@example.com')->sole();
        $this->assertSame(
            [['Bruno', 'completed', '120.00'], ['Oyen', 'pending', '90.00']],
            $daniel->bookings()->with('pet')->orderBy('check_in')->get()
                ->map(fn ($b) => [$b->pet->name, $b->status, $b->total_price])->all(),
        );
    }

    public function test_a_holiday_search_hides_whiskers_and_wags(): void
    {
        Carbon::setTestNow('2026-10-18');

        $this->seed(PersonaSeeder::class);

        $hidden = Availability::unavailableHotelIds(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-05'));
        $this->assertSame([PetHotel::where('slug', 'whiskers-and-wags')->sole()->id], $hidden);
    }

    public function test_after_this_years_holiday_the_stories_move_to_next_november(): void
    {
        Carbon::setTestNow('2026-11-02');

        $this->seed(PersonaSeeder::class);

        $oyen = User::where('email', 'daniel@example.com')->sole()->bookings()->where('status', 'pending')->sole();
        $this->assertSame('2027-11-01', $oyen->check_in->toDateString());
    }

    public function test_the_persona_hotels_escape_the_demo_sunday_closures(): void
    {
        Carbon::setTestNow('2026-10-18');

        $this->seed(DatabaseSeeder::class);

        // 1 November 2026 is a Sunday: every demo hotel is closed, Rahman's is not.
        $hotel = PetHotel::where('slug', 'rahmans-paws-house')->sole();
        $this->assertSame(2, Availability::nights($hotel, Carbon::parse('2026-11-01'), Carbon::parse('2026-11-01'))['2026-11-01']['spots_left']);
    }

    /** @return list<string> */
    private function petNames(string $email): array
    {
        return User::where('email', $email)->sole()->pets()->orderBy('name')->pluck('name')->all();
    }
}
