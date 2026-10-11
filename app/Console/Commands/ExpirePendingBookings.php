<?php

namespace App\Console\Commands;

use App\Enums\CancelledBy;
use App\Models\Booking;
use Illuminate\Console\Command;

/**
 * Ends requests the hotel never answered. Once the check-in day has passed a
 * pending request can only mislead: the customer is still waiting, and the
 * owner's pending count includes a stay that cannot happen.
 */
class ExpirePendingBookings extends Command
{
    protected $signature = 'bookings:expire-pending';

    protected $description = 'Cancel pending booking requests whose check-in day has passed';

    public function handle(): int
    {
        $today = today();

        // Exact dates from the casts after the query, as in Availability:
        // SQLite keeps whatever time part was written into a date column.
        $expired = Booking::where('status', 'pending')
            ->where('check_in', '<', $today->copy()->addDay())
            ->get()
            ->filter(fn (Booking $booking) => $booking->check_in->lt($today));

        $expired->each(fn (Booking $booking) => $booking->cancel(CancelledBy::Expiry));

        $this->info("Expired {$expired->count()} pending booking request(s).");

        return self::SUCCESS;
    }
}
