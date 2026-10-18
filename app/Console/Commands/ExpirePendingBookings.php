<?php

namespace App\Console\Commands;

use App\Enums\CancelledBy;
use App\Models\Booking;
use Illuminate\Console\Command;

/**
 * Ends requests the hotel did not answer in time (Booking::answerBy()), so
 * the guest hears while there is still time to book elsewhere, and the
 * owner's pending count drops a stay they did not take.
 */
class ExpirePendingBookings extends Command
{
    protected $signature = 'bookings:expire-pending';

    protected $description = 'Cancel pending booking requests the hotel did not answer in time';

    public function handle(): int
    {
        // Deadlines depend on when each request was sent, so they are worked
        // out in PHP; pending requests are few.
        $expired = Booking::where('status', 'pending')
            ->get()
            ->filter(fn (Booking $booking) => $booking->answerBy()->lte(now()));

        $expired->each(fn (Booking $booking) => $booking->cancel(CancelledBy::Expiry));

        $this->info("Expired {$expired->count()} pending booking request(s).");

        return self::SUCCESS;
    }
}
