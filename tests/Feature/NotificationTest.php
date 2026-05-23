<?php

namespace Tests\Feature;

use App\Jobs\SendBookingCancelledNotification;
use App\Jobs\SendBookingConfirmationNotification;
use App\Jobs\SendBookingRequestNotification;
use App\Models\Booking;
use App\Models\PetHotel;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    // ── Mail notification classes ─────────────────────────────────────────────

    public function test_booking_requested_notification_uses_mail_and_database_channels(): void
    {
        $booking = Booking::factory()->make();

        $notification = new BookingRequested($booking);

        $this->assertEquals(['mail', 'database'], $notification->via(new User));
    }

    public function test_booking_confirmed_notification_uses_mail_and_database_channels(): void
    {
        $booking = Booking::factory()->make();

        $notification = new BookingConfirmed($booking);

        $this->assertEquals(['mail', 'database'], $notification->via(new User));
    }

    public function test_booking_cancelled_notification_uses_mail_and_database_channels(): void
    {
        $booking = Booking::factory()->make();

        $notification = new BookingCancelled($booking);

        $this->assertEquals(['mail', 'database'], $notification->via(new User));
    }

    // ── Job dispatching ───────────────────────────────────────────────────────

    public function test_booking_creation_dispatches_request_notification_job(): void
    {
        Queue::fake();

        $hotel = PetHotel::factory()->create();
        $hotel->pricing()->create(['pet_type' => 'dog', 'price_per_night' => 50]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $pet = $user->pets()->create(['name' => 'Rex', 'species' => 'dog']);

        $this->actingAs($user)->post("/hotels/{$hotel->slug}/bookings", [
            'pet_id' => $pet->id,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-04',
            'notes' => '',
        ]);

        Queue::assertPushed(SendBookingRequestNotification::class);
    }

    public function test_booking_confirmed_dispatches_confirmation_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $booking = Booking::factory()->for($user)->create(['status' => 'pending']);

        $booking->update(['status' => 'confirmed']);

        Queue::assertPushed(SendBookingConfirmationNotification::class, fn ($job) => $job->booking->is($booking));
    }

    public function test_booking_cancelled_dispatches_cancelled_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $booking = Booking::factory()->for($user)->create(['status' => 'pending']);

        $booking->update(['status' => 'cancelled']);

        Queue::assertPushed(SendBookingCancelledNotification::class, fn ($job) => $job->booking->is($booking));
    }

    // ── NotificationController ────────────────────────────────────────────────

    public function test_guest_cannot_access_notifications(): void
    {
        $this->getJson('/notifications')->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_notifications(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $booking = Booking::factory()->for($user)->create();

        $user->notifications()->create([
            'id' => Str::uuid(),
            'type' => BookingRequested::class,
            'data' => [
                'type' => 'booking_requested',
                'booking_id' => $booking->id,
                'hotel_name' => 'Test Hotel',
                'message' => 'Your booking request has been received.',
                'url' => '/bookings/'.$booking->id,
            ],
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/notifications');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['type' => 'booking_requested']);
    }

    public function test_notifications_list_returns_at_most_10(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        for ($i = 0; $i < 12; $i++) {
            $user->notifications()->create([
                'id' => Str::uuid(),
                'type' => BookingRequested::class,
                'data' => ['type' => 'booking_requested', 'message' => "Notification $i", 'hotel_name' => 'H', 'url' => '/'],
                'read_at' => null,
            ]);
        }

        $this->actingAs($user)->getJson('/notifications')
            ->assertOk()
            ->assertJsonCount(10);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $id = Str::uuid();

        $user->notifications()->create([
            'id' => $id,
            'type' => BookingRequested::class,
            'data' => ['type' => 'booking_requested', 'message' => 'Test', 'hotel_name' => 'H', 'url' => '/'],
            'read_at' => null,
        ]);

        $this->actingAs($user)->patchJson("/notifications/{$id}/read")
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNotNull(
            DatabaseNotification::find($id)->read_at
        );
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        for ($i = 0; $i < 3; $i++) {
            $user->notifications()->create([
                'id' => Str::uuid(),
                'type' => BookingRequested::class,
                'data' => ['type' => 'booking_requested', 'message' => "N$i", 'hotel_name' => 'H', 'url' => '/'],
                'read_at' => null,
            ]);
        }

        $this->actingAs($user)->postJson('/notifications/read-all')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_user_cannot_read_another_users_notification(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $id = Str::uuid();

        $owner->notifications()->create([
            'id' => $id,
            'type' => BookingRequested::class,
            'data' => ['type' => 'booking_requested', 'message' => 'Test', 'hotel_name' => 'H', 'url' => '/'],
            'read_at' => null,
        ]);

        $this->actingAs($other)->patchJson("/notifications/{$id}/read")
            ->assertNotFound();
    }

    // ── Inertia shared props ──────────────────────────────────────────────────

    public function test_unread_notifications_count_is_shared_in_inertia_props(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $user->notifications()->create([
            'id' => Str::uuid(),
            'type' => BookingRequested::class,
            'data' => ['type' => 'booking_requested', 'message' => 'Test', 'hotel_name' => 'H', 'url' => '/'],
            'read_at' => null,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('unread_notifications_count', 1));
    }
}
