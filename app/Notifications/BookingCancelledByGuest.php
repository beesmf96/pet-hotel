<?php

namespace App\Notifications;

class BookingCancelledByGuest extends OwnerBookingNotification
{
    protected function heading(): string
    {
        return 'Booking request cancelled';
    }

    protected function intro(): string
    {
        return 'A guest has cancelled their booking request at **'.$this->booking->hotel->name.'**. '
            .'There is nothing for you to do.';
    }
}
