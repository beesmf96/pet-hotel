<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\BookingCancelled;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingCancelledNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function handle(): void
    {
        $this->booking->loadMissing(['hotel', 'user']);
        $this->booking->user->notify(new BookingCancelled($this->booking));
    }
}
