<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Not ShouldQueue: SendBookingConfirmationNotification is the queued unit, so its retry
 * policy covers the mail send.
 */
class BookingConfirmed extends Notification
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
            ->subject('Booking Confirmed — '.$hotel->name)
            ->greeting('Great news, '.$notifiable->name.'!')
            ->line('Your booking at **'.$hotel->name.'** has been confirmed.')
            ->line('Check-in: '.$booking->check_in->format('D, d M Y').($times ? ', from '.$times['check_in'] : ''))
            ->line('Check-out: '.$booking->check_out->format('D, d M Y').($times ? ', by '.$times['check_out'] : ''))
            ->line('Total: '.Money::format($booking->total_price))
            ->action('View Booking', route('bookings.show', $booking))
            ->line('We look forward to welcoming your pet!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'booking_confirmed',
            'booking_id' => $this->booking->id,
            'hotel_name' => $this->booking->hotel->name,
            'message' => 'Your booking at '.$this->booking->hotel->name.' has been confirmed.',
            'url' => route('bookings.show', $this->booking),
        ];
    }
}
