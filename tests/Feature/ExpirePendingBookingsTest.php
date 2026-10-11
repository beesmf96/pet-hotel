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

    public function test_a_request_whose_check_in_day_has_passed_expires(): void
    {
        Queue::fake();

        $booking = $this->pending(daysFromNow: -1);

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

    public function test_a_request_checking_in_today_or_later_stays_pending(): void
    {
        Queue::fake();

        $today = $this->pending(daysFromNow: 0);
        $later = $this->pending(daysFromNow: 5);

        $this->artisan('bookings:expire-pending')
            ->expectsOutput('Expired 0 pending booking request(s).')
            ->assertSuccessful();

        $this->assertSame('pending', $today->fresh()->status);
        $this->assertSame('pending', $later->fresh()->status);
        Queue::assertNothingPushed();
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

    public function test_it_runs_every_day_at_quarter_past_midnight(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'bookings:expire-pending'));

        $this->assertNotNull($event);
        $this->assertSame('15 0 * * *', $event->expression);
    }

    private function pending(int $daysFromNow): Booking
    {
        return Booking::factory()->create([
            'check_in' => today()->addDays($daysFromNow),
            'check_out' => today()->addDays($daysFromNow + 2),
            'status' => 'pending',
        ]);
    }
}
