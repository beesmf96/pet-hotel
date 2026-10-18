<?php

namespace Tests\Feature;

use App\Enums\CancelledBy;
use App\Jobs\NotifyOwnersOfGuestCancellation;
use App\Jobs\SendBookingCancelledNotification;
use App\Models\Booking;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExpirePendingBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_request_past_its_answer_deadline_expires(): void
    {
        Queue::fake();

        $booking = $this->pending(sentHoursAgo: 49, checkInDaysFromNow: 10);

        $this->artisan('bookings:expire-pending')
            ->expectsOutput('Expired 1 pending booking request(s).')
            ->assertSuccessful();

        $this->assertSame('cancelled', $booking->fresh()->status);
        Queue::assertPushed(
            SendBookingCancelledNotification::class,
            fn ($job) => $job->booking->is($booking) && $job->by === CancelledBy::Expiry && ! $job->wasConfirmed,
        );
        Queue::assertNotPushed(NotifyOwnersOfGuestCancellation::class);
    }

    public function test_a_request_still_inside_its_deadline_stays_pending(): void
    {
        Queue::fake();

        $booking = $this->pending(sentHoursAgo: 47, checkInDaysFromNow: 10);

        $this->artisan('bookings:expire-pending')
            ->expectsOutput('Expired 0 pending booking request(s).')
            ->assertSuccessful();

        $this->assertSame('pending', $booking->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_a_recent_request_expires_at_noon_the_day_before_check_in(): void
    {
        Queue::fake();
        // 10:00 AM in Malaysia on 31 Oct; the request checks in on 2 Nov.
        $this->travelTo('2026-10-31 02:00:00');
        $booking = $this->pending(sentHoursAgo: 2, checkInDaysFromNow: 2);

        $this->travelTo('2026-11-01 03:59:00');
        $this->artisan('bookings:expire-pending')->expectsOutput('Expired 0 pending booking request(s).');

        $this->travelTo('2026-11-01 04:00:00');
        $this->artisan('bookings:expire-pending')->expectsOutput('Expired 1 pending booking request(s).');
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_bookings_that_are_not_pending_are_left_alone(): void
    {
        Queue::fake();

        $confirmed = Booking::factory()->confirmed()->create([
            'check_in' => today()->subDays(3),
            'check_out' => today()->addDay(),
        ]);

        $this->artisan('bookings:expire-pending')->assertSuccessful();

        $this->assertSame('confirmed', $confirmed->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_it_runs_every_five_minutes(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'bookings:expire-pending'));

        $this->assertNotNull($event);
        $this->assertSame('*/5 * * * *', $event->expression);
    }

    private function pending(int $sentHoursAgo, int $checkInDaysFromNow): Booking
    {
        return Booking::factory()->create([
            'check_in' => today()->addDays($checkInDaysFromNow),
            'check_out' => today()->addDays($checkInDaysFromNow + 2),
            'status' => 'pending',
            'created_at' => now()->subHours($sentHoursAgo),
        ]);
    }
}
