<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendBookingCancelledNotification;
use App\Jobs\SendBookingConfirmationNotification;
use App\Jobs\SendBookingRequestNotification;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * These jobs are the only thing that tells a customer what happened to their
 * booking, and they run unattended on a worker. The retry policy lives on the
 * job classes rather than on the worker's --tries flag so it cannot drift
 * between the Docker `queue` service and a hosted worker started another way —
 * which is what these guard.
 */
class NotificationJobPolicyTest extends TestCase
{
    use RefreshDatabase;

    private const JOBS = [
        SendBookingRequestNotification::class,
        SendBookingConfirmationNotification::class,
        SendBookingCancelledNotification::class,
    ];

    public function test_every_notification_job_retries_a_transient_failure(): void
    {
        $booking = Booking::factory()->create();

        foreach (self::JOBS as $job) {
            $instance = new $job($booking);

            $this->assertSame(3, $instance->tries, "{$job} must retry");
            $this->assertSame([10, 60], $instance->backoff, "{$job} must back off between retries");
        }
    }

    /**
     * Bookings are hard-deleted and cascade from users and hotels, so a booking
     * can legitimately be gone before its job runs. Without this the job fails
     * permanently into failed_jobs, which is noise rather than a fault.
     */
    public function test_every_notification_job_is_discarded_when_its_booking_is_gone(): void
    {
        $booking = Booking::factory()->create();

        foreach (self::JOBS as $job) {
            $this->assertTrue(
                (new $job($booking))->deleteWhenMissingModels,
                "{$job} must be discarded when its booking no longer exists",
            );
        }
    }

    /**
     * The jobs are dispatched from Booking::booted(), which fires inside the
     * caller's transaction. A worker is a separate process and would otherwise
     * be free to load a booking that has not been committed yet.
     */
    public function test_redis_dispatches_wait_for_the_transaction_to_commit(): void
    {
        $this->assertTrue(config('queue.connections.redis.after_commit'));
    }
}
