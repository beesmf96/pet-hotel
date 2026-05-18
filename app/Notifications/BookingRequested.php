<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRequested extends Notification implements ShouldQueue
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
            ->subject('Booking Request Received — '.$hotel->name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('We\'ve received your booking request for **'.$hotel->name.'**.')
            ->line('Check-in: '.$booking->check_in->format('D, d M Y'))
            ->line('Check-out: '.$booking->check_out->format('D, d M Y'))
            ->line('Total: $'.number_format($booking->total_price, 2))
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
