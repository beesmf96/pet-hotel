<?php

namespace App\Notifications;

use App\Enums\PetType;
use App\Filament\HotelOwner\Resources\BookingResource;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Notifications\Notification as PanelNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a hotel's owners about something a guest did. Sent by email and as a
 * notification in the owner panel's bell, in Filament's database format so
 * the customer site can tell them apart (User::customerNotifications()).
 *
 * Not ShouldQueue: the job that sends it is the queued unit, so the job's
 * retry policy covers the mail send.
 */
abstract class OwnerBookingNotification extends Notification
{
    public function __construct(public Booking $booking) {}

    abstract protected function heading(): string;

    abstract protected function intro(): string;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;
        $mail = (new MailMessage)
            ->subject($this->heading().' — '.$booking->hotel->name)
            ->greeting('Hi '.$notifiable->name.',')
            ->line($this->intro())
            ->line('Guest: '.$booking->user->name)
            ->line('Pet: '.$this->petDescription())
            ->line('Stay: '.$this->stayDescription())
            ->line('Total: RM '.number_format($booking->total_price, 2));

        if ($booking->notes) {
            $mail->line('Notes: '.$booking->notes);
        }

        return $mail->action('Open bookings', $this->bookingsUrl());
    }

    public function toDatabase(object $notifiable): array
    {
        return PanelNotification::make()
            ->title($this->heading())
            ->body($this->booking->user->name.' · '.$this->petDescription().' · '.$this->stayDescription())
            ->actions([
                Action::make('open')
                    ->label('Open bookings')
                    ->url($this->bookingsUrl())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    protected function petDescription(): string
    {
        $pet = $this->booking->pet;

        return $pet->name.' ('.(PetType::tryFrom($pet->species)?->label() ?? $pet->species).')';
    }

    protected function stayDescription(): string
    {
        $booking = $this->booking;
        $nights = $booking->check_in->diffInDays($booking->check_out);

        return $booking->check_in->format('D, d M Y').' to '.$booking->check_out->format('D, d M Y')
            .' ('.$nights.' '.str('night')->plural($nights).')';
    }

    protected function bookingsUrl(): string
    {
        return BookingResource::getUrl('index', panel: 'hotel-owner');
    }
}
