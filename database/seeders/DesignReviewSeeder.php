<?php

namespace Database\Seeders;

use App\Enums\CancelledBy;
use App\Models\Booking;
use App\Models\PetHotel;
use App\Models\Review;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRequested;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The personas plus every page state a design review needs to see: reviews on
 * a hotel, a confirmed and a cancelled stay, read and unread notifications,
 * and an account with nothing in it. Only the /design-review capture uses it
 * (.claude/skills/design-review/). Fixed text, so screenshots compare run to run.
 */
class DesignReviewSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PersonaSeeder::class);

        $rahmans = PetHotel::where('slug', 'rahmans-paws-house')->sole();
        $whiskers = PetHotel::where('slug', 'whiskers-and-wags')->sole();

        $reviews = [
            ['Farah Aziz', 5, 'Our beagle came home happy and tired. Rahman sent photos every evening.'],
            ['Kumar Pillai', 4, 'Clean, calm and close to home. The garden is small but well kept.'],
            ['Grace Wong', 5, 'Second stay for our cat. She settled in within an hour this time.'],
        ];
        foreach ($reviews as $i => [$name, $rating, $comment]) {
            $guest = User::factory()->create(['name' => $name]);
            $pet = $guest->pets()->create(['name' => 'Pet '.($i + 1), 'species' => 'dog']);
            $stay = Carbon::today()->subWeeks(10 + $i * 3);
            $booking = $this->book($guest, $pet->id, $rahmans, $stay, $stay->copy()->addDays(3), 'completed', '180.00');
            Review::create([
                'user_id' => $guest->id,
                'hotel_id' => $rahmans->id,
                'booking_id' => $booking->id,
                'rating' => $rating,
                'comment' => $comment,
            ]);
        }

        // Aisyah: one stay ahead, one called off.
        $aisyah = User::where('email', 'aisyah@example.com')->sole();
        $milo = $aisyah->pets()->sole();
        $soon = Carbon::today()->addDays(20);
        $this->book($aisyah, $milo->id, $whiskers, $soon, $soon->copy()->addDays(3), 'confirmed', '225.00');
        $later = Carbon::today()->addDays(40);
        $cancelled = $this->book($aisyah, $milo->id, $rahmans, $later, $later->copy()->addDays(2), 'cancelled', '120.00');

        // The bell: Daniel has one read and one unread; Aisyah one unread.
        $daniel = User::where('email', 'daniel@example.com')->sole();
        [$past, $pending] = $daniel->bookings()->orderBy('check_in')->get()->all();
        $this->notify($daniel, new BookingConfirmed($past), read: true);
        $this->notify($daniel, new BookingRequested($pending), read: false);
        $this->notify($aisyah, new BookingCancelled($cancelled, CancelledBy::Hotel, true), read: false);

        // No pets, no bookings: the empty states.
        User::factory()->create(['name' => 'Nina Baru', 'email' => 'nina@example.com']);
    }

    private function book(User $user, int $petId, PetHotel $hotel, Carbon $checkIn, Carbon $checkOut, string $status, string $total): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'hotel_id' => $hotel->id,
            'pet_id' => $petId,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $status,
            'total_price' => $total,
        ]);
    }

    /** The bell's database row only: a seeder sends no mail. */
    private function notify(User $user, Notification $notification, bool $read): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => $notification::class,
            'data' => $notification->toDatabase($user),
            'read_at' => $read ? now() : null,
        ]);
    }
}
