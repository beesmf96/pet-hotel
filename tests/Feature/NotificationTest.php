<?php

namespace Tests\Feature;

use App\Enums\CancelledBy;
use App\Jobs\NotifyOwnersOfGuestCancellation;
use App\Jobs\SendBookingCancelledNotification;
use App\Jobs\SendBookingConfirmationNotification;
use App\Jobs\SendBookingRequestNotification;
use App\Models\Booking;
use App\Models\PetHotel;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
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

        $notification = new BookingCancelled($booking, CancelledBy::Guest, false);

        $this->assertEquals(['mail', 'database'], $notification->via(new User));
    }

    public function test_booking_requested_mail_describes_the_stay(): void
    {
        $hotel = PetHotel::factory()->create(['name' => 'Happy Paws']);
        $user = User::factory()->create(['name' => 'Ada']);
        $booking = Booking::factory()->for($user)->for($hotel, 'hotel')->create([
            'total_price' => 150.00,
        ]);

        $mail = (new BookingRequested($booking))->toMail($user);
        $lines = implode(' ', $mail->introLines);

        $this->assertSame('Booking Request Received — Happy Paws', $mail->subject);
        $this->assertSame('Hi Ada,', $mail->greeting);
        $this->assertSame('View Booking', $mail->actionText);
        $this->assertSame(route('bookings.show', $booking), $mail->actionUrl);
        $this->assertStringContainsString('Happy Paws', $lines);
        $this->assertStringContainsString($booking->check_in->format('D, d M Y'), $lines);
        $this->assertStringContainsString($booking->check_out->format('D, d M Y'), $lines);
        $this->assertStringContainsString('Total: RM 150.00', $lines);
        $this->assertStringNotContainsString('$', $lines);
    }

    public function test_booking_requested_mail_gives_the_hotel_times(): void
    {
        $booking = Booking::factory()->create();
        $booking->hotel->policy()->create(['check_in_time' => '14:00', 'check_out_time' => '12:00']);

        $lines = implode(' ', (new BookingRequested($booking->fresh()))->toMail($booking->user)->introLines);

        $this->assertStringContainsString($booking->check_in->format('D, d M Y').', from 2:00 PM', $lines);
        $this->assertStringContainsString($booking->check_out->format('D, d M Y').', by 12:00 PM', $lines);
    }

    public function test_booking_confirmed_mail_gives_the_hotel_times(): void
    {
        $booking = Booking::factory()->confirmed()->create();
        $booking->hotel->policy()->create(['check_in_time' => '09:30', 'check_out_time' => '18:00']);

        $lines = implode(' ', (new BookingConfirmed($booking->fresh()))->toMail($booking->user)->introLines);

        $this->assertStringContainsString(', from 9:30 AM', $lines);
        $this->assertStringContainsString(', by 6:00 PM', $lines);
    }

    public function test_booking_mail_leaves_out_times_without_a_policy(): void
    {
        $booking = Booking::factory()->create();

        $lines = implode(' ', (new BookingRequested($booking))->toMail($booking->user)->introLines);

        $this->assertStringNotContainsString(', from ', $lines);
        $this->assertStringNotContainsString(', by ', $lines);
    }

    public function test_booking_requested_database_payload_describes_the_booking(): void
    {
        $hotel = PetHotel::factory()->create(['name' => 'Happy Paws']);
        $user = User::factory()->create();
        $booking = Booking::factory()->for($user)->for($hotel, 'hotel')->create();

        $payload = (new BookingRequested($booking))->toDatabase($user);

        $this->assertSame('booking_requested', $payload['type']);
        $this->assertSame($booking->id, $payload['booking_id']);
        $this->assertSame('Happy Paws', $payload['hotel_name']);
        $this->assertStringContainsString('Happy Paws', $payload['message']);
        $this->assertSame(route('bookings.show', $booking), $payload['url']);
    }

    // ── Job handling ──────────────────────────────────────────────────────────

    public function test_request_notification_job_notifies_the_booking_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $booking = Booking::factory()->for($user)->create();

        (new SendBookingRequestNotification($booking))->handle();

        Notification::assertSentTo(
            $user,
            BookingRequested::class,
            fn (BookingRequested $notification) => $notification->booking->is($booking),
        );
    }

    public function test_request_notification_job_writes_a_database_notification(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->for($user)->create();

        (new SendBookingRequestNotification($booking))->handle();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => BookingRequested::class,
        ]);
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
            'check_in' => now()->addDays(30)->toDateString(),
            'check_out' => now()->addDays(33)->toDateString(),
            'notes' => '',
        ]);

        Queue::assertPushed(SendBookingRequestNotification::class);
    }

    public function test_confirming_dispatches_the_confirmation_job(): void
    {
        Queue::fake();

        $booking = Booking::factory()->create(['status' => 'pending']);

        $booking->confirm();

        Queue::assertPushed(SendBookingConfirmationNotification::class, fn ($job) => $job->booking->is($booking));
    }

    public function test_a_guest_cancelling_dispatches_the_guest_wording_and_tells_the_owners(): void
    {
        Queue::fake();

        $booking = Booking::factory()->create(['status' => 'pending']);

        $booking->cancel(CancelledBy::Guest);

        $this->assertSame('cancelled', $booking->fresh()->status);
        Queue::assertPushed(SendBookingCancelledNotification::class, fn ($job) => $job->booking->is($booking)
            && $job->by === CancelledBy::Guest && $job->wasConfirmed === false);
        Queue::assertPushed(NotifyOwnersOfGuestCancellation::class);
    }

    public function test_a_hotel_cancelling_a_confirmed_stay_says_so_and_spares_the_owners(): void
    {
        Queue::fake();

        $booking = Booking::factory()->confirmed()->create();

        $booking->cancel(CancelledBy::Hotel);

        Queue::assertPushed(SendBookingCancelledNotification::class, fn ($job) => $job->by === CancelledBy::Hotel && $job->wasConfirmed);
        Queue::assertNotPushed(NotifyOwnersOfGuestCancellation::class);
    }

    public function test_a_plain_status_update_sends_nothing(): void
    {
        Queue::fake();

        Booking::factory()->create(['status' => 'pending'])->update(['status' => 'completed']);

        Queue::assertNothingPushed();
    }

    // ── Cancellation wording ──────────────────────────────────────────────────

    private function cancelledMail(CancelledBy $by, bool $wasConfirmed): MailMessage
    {
        $hotel = PetHotel::factory()->create(['name' => 'Happy Paws']);
        $booking = Booking::factory()->for($hotel, 'hotel')->create();

        return (new BookingCancelled($booking, $by, $wasConfirmed))->toMail($booking->user);
    }

    public function test_guest_cancel_mail_says_the_guest_cancelled(): void
    {
        $mail = $this->cancelledMail(CancelledBy::Guest, false);

        $this->assertSame('Booking Request Cancelled — Happy Paws', $mail->subject);
        $this->assertStringContainsString('You cancelled your booking request at **Happy Paws**.', implode(' ', $mail->introLines));
        $this->assertStringContainsString('If you did not cancel it yourself', implode(' ', $mail->outroLines));
        $this->assertSame(route('hotels.index'), $mail->actionUrl);
    }

    public function test_decline_mail_says_the_hotel_could_not_take_it(): void
    {
        $mail = $this->cancelledMail(CancelledBy::Hotel, false);
        $lines = implode(' ', $mail->introLines);

        $this->assertSame('Booking Request Declined — Happy Paws', $mail->subject);
        $this->assertStringContainsString('**Happy Paws** could not take your booking request.', $lines);
        $this->assertStringContainsString('No booking was made.', $lines);
        $this->assertStringNotContainsString('did not cancel', $lines.implode(' ', $mail->outroLines));
        $this->assertSame('Find Another Stay', $mail->actionText);
    }

    public function test_hotel_cancel_mail_says_the_confirmed_stay_was_cancelled(): void
    {
        $mail = $this->cancelledMail(CancelledBy::Hotel, true);

        $this->assertSame('Booking Cancelled by the Hotel — Happy Paws', $mail->subject);
        $this->assertStringContainsString('**Happy Paws** has cancelled your confirmed booking.', implode(' ', $mail->introLines));
        $this->assertSame('View Booking', $mail->actionText);
    }

    public function test_cancelled_database_message_matches_the_wording(): void
    {
        $booking = Booking::factory()->create();

        $payload = (new BookingCancelled($booking, CancelledBy::Hotel, false))->toDatabase($booking->user);

        $this->assertSame('booking_cancelled', $payload['type']);
        $this->assertSame($booking->hotel->name.' could not take your booking request.', $payload['message']);
    }

    public function test_customer_notifications_are_not_queued_a_second_time(): void
    {
        foreach ([BookingRequested::class, BookingConfirmed::class, BookingCancelled::class] as $class) {
            $this->assertNotInstanceOf(ShouldQueue::class, (new \ReflectionClass($class))->newInstanceWithoutConstructor(), $class);
        }
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
