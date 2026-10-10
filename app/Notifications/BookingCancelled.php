<?php

namespace App\Notifications;

use App\Enums\CancelledBy;
use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the guest their booking has ended, in one of three ways: they
 * cancelled their own request, the hotel declined it, or the hotel cancelled
 * a stay it had confirmed.
 *
 * Not ShouldQueue: SendBookingCancelledNotification is the queued unit.
 */
class BookingCancelled extends Notification
{
    public function __construct(
        public Booking $booking,
        public CancelledBy $by,
        public bool $wasConfirmed,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function declined(): bool
    {
        return $this->by === CancelledBy::Hotel && ! $this->wasConfirmed;
    }

    private function subject(): string
    {
        return match (true) {
            $this->by === CancelledBy::Guest => 'Booking Request Cancelled',
            $this->declined() => 'Booking Request Declined',
            default => 'Booking Cancelled by the Hotel',
        };
    }

    private function message(bool $bold = false): string
    {
        $hotel = $bold ? '**'.$this->booking->hotel->name.'**' : $this->booking->hotel->name;

        return match (true) {
            $this->by === CancelledBy::Guest => 'You cancelled your booking request at '.$hotel.'.',
            $this->declined() => $hotel.' could not take your booking request.',
            default => $hotel.' has cancelled your confirmed booking.',
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;

        $mail = (new MailMessage)
            ->subject($this->subject().' — '.$booking->hotel->name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line($this->message(bold: true))
            ->line('Check-in was: '.$booking->check_in->format('D, d M Y'))
            ->line('Check-out was: '.$booking->check_out->format('D, d M Y'));

        return match (true) {
            $this->by === CancelledBy::Guest => $mail
                ->action('Browse Hotels', route('hotels.index'))
                ->line('If you did not cancel it yourself, please contact support.'),
            $this->declined() => $mail
                ->line('No booking was made. You can ask for other dates, or find another hotel.')
                ->action('Find Another Stay', route('hotels.index')),
            default => $mail
                ->line('Please contact the hotel if you have any questions.')
                ->action('View Booking', route('bookings.show', $booking)),
        };
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'booking_cancelled',
            'booking_id' => $this->booking->id,
            'hotel_name' => $this->booking->hotel->name,
            'message' => $this->message(),
            'url' => route('bookings.show', $this->booking),
        ];
    }
}
