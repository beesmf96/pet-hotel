<?php

namespace App\Notifications;

class NewBookingRequest extends OwnerBookingNotification
{
    protected function heading(): string
    {
        return 'New booking request';
    }

    protected function intro(): string
    {
        return 'A guest has asked to book a stay at **'.$this->booking->hotel->name.'**. '
            .'It does not hold a spot until you confirm it. '
            .'Please answer by '.$this->booking->answerByText().', or the request expires.';
    }
}
