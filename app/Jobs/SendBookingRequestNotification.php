<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\BookingRequested;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingRequestNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Delivery is the whole point of the job, so a transient mail or Redis
     * failure should not drop a customer's notification. These live on the job
     * rather than the worker's --tries flag so the policy is the same however
     * each environment happens to start its worker.
     */
    public int $tries = 3;

    /** @var list<int> Seconds to wait before each retry. */
    public array $backoff = [10, 60];

    /**
     * Bookings are hard-deleted and cascade from users and hotels, so the
     * booking can legitimately be gone before the job runs. That is nothing to
     * alert on — discard the job instead of failing it.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Booking $booking) {}

    public function handle(): void
    {
        $this->booking->loadMissing(['hotel', 'user']);
        $this->booking->user->notify(new BookingRequested($this->booking));
    }
}
