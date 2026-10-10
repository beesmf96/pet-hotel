<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Not ShouldQueue: SendBookingRequestNotification is the queued unit, so its retry
 * policy covers the mail send.
 */
class BookingRequested extends Notification
{
    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;
        $hotel = $booking->hotel;
        $times = $hotel->policy?->stayTimes();

        return (new MailMessage)
            ->subject('Booking Request Received — '.$hotel->name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('We\'ve received your booking request for **'.$hotel->name.'**.')
            ->line('Check-in: '.$booking->check_in->format('D, d M Y').($times ? ', from '.$times['check_in'] : ''))
            ->line('Check-out: '.$booking->check_out->format('D, d M Y').($times ? ', by '.$times['check_out'] : ''))
            ->line('Total: '.Money::format($booking->total_price))
            ->action('View Booking', route('bookings.show', $booking))
            ->line('The hotel will review your request and confirm shortly.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'booking_requested',
            'booking_id' => $this->booking->id,
            'hotel_name' => $this->booking->hotel->name,
            'message' => 'Your booking request for '.$this->booking->hotel->name.' has been received.',
            'url' => route('bookings.show', $this->booking),
        ];
    }
}
