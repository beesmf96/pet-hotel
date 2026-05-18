<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
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
            ->subject('Booking Confirmed — '.$hotel->name)
            ->greeting('Great news, '.$notifiable->name.'!')
            ->line('Your booking at **'.$hotel->name.'** has been confirmed.')
            ->line('Check-in: '.$booking->check_in->format('D, d M Y'))
            ->line('Check-out: '.$booking->check_out->format('D, d M Y'))
            ->line('Total: $'.number_format($booking->total_price, 2))
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
