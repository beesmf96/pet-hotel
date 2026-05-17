<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBookingRequestNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function handle(): void
    {
        // Module 8 wires in BookingRequestedMail (user + admin)
        Log::info('Booking request received', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->booking->user_id,
            'hotel_id' => $this->booking->hotel_id,
        ]);
    }
}
