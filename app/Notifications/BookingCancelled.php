<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;
        $hotel = $booking->hotel;

        return (new MailMessage)
            ->subject('Booking Cancelled — '.$hotel->name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('Your booking at **'.$hotel->name.'** has been cancelled.')
            ->line('Check-in was: '.$booking->check_in->format('D, d M Y'))
            ->line('Check-out was: '.$booking->check_out->format('D, d M Y'))
            ->action('Browse Hotels', route('hotels.index'))
            ->line('If you did not request this cancellation, please contact support.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'booking_cancelled',
            'booking_id' => $this->booking->id,
            'hotel_name' => $this->booking->hotel->name,
            'message' => 'Your booking at '.$this->booking->hotel->name.' has been cancelled.',
            'url' => route('bookings.show', $this->booking),
        ];
    }
}
