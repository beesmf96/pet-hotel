<?php

namespace Tests\Feature;

use App\Models\PetHotel;
use App\Models\User;
use Database\Seeders\DesignReviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** The page states the /design-review capture expects to find. */
class DesignReviewSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_every_state_the_capture_visits(): void
    {
        Mail::fake();
        Notification::fake();

        $this->seed(DesignReviewSeeder::class);

        $this->assertSame(3, PetHotel::where('slug', 'rahmans-paws-house')->sole()->reviews()->count());

        $aisyah = User::where('email', 'aisyah@example.com')->sole();
        $this->assertEqualsCanonicalizing(['confirmed', 'cancelled'], $aisyah->bookings()->pluck('status')->all());
        $this->assertSame(1, $aisyah->unreadNotifications()->count());

        $daniel = User::where('email', 'daniel@example.com')->sole();
        $this->assertSame(2, $daniel->notifications()->count());
        $this->assertSame(1, $daniel->unreadNotifications()->count());

        $nina = User::where('email', 'nina@example.com')->sole();
        $this->assertSame(0, $nina->pets()->count());
        $this->assertSame(0, $nina->bookings()->count());

        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }
}
