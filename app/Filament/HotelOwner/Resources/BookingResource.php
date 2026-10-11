<?php

namespace App\Filament\HotelOwner\Resources;

use App\Enums\CancelledBy;
use App\Enums\PetType;
use App\Exceptions\BookingDoesNotFit;
use App\Filament\HotelOwner\Concerns\ResolvesOwnerHotel;
use App\Filament\HotelOwner\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    use ResolvesOwnerHotel;

    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?int $navigationSort = 0;

    public static function getNavigationBadge(): ?string
    {
        $pending = Booking::where('hotel_id', static::ownerHotel()->id)
            ->where('status', 'pending')
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Requests waiting for you to confirm or decline';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Guest')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pet.name')
                    ->label('Pet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('check_in')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('check_out')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => static::statusColor($state))
                    ->sortable(),
                TextColumn::make('total_price')
                    ->formatStateUsing(fn ($state): string => Money::format($state))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ]),
            ])
            ->recordAction('view')
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    // BookingPolicy::view() is the guest's own booking page;
                    // here the table query already holds only this hotel.
                    ->authorize(fn (Booking $record): bool => $record->hotel_id === static::ownerHotel()->id)
                    ->modalHeading(fn (Booking $record): string => 'Booking from '.$record->user->name)
                    ->modalWidth('2xl')
                    ->extraModalFooterActions([
                        static::confirmAction('confirmFromView')->cancelParentActions(),
                        static::declineAction('declineFromView')->cancelParentActions(),
                    ]),

                static::confirmAction('confirm'),
                static::declineAction('decline'),

                Action::make('complete')
                    ->label('Mark completed')
                    ->icon(Heroicon::OutlinedFlag)
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Booking Completed')
                    ->modalDescription('This marks the stay as finished and lets the guest leave a review.')
                    // Owners can only close a stay once its check-out date has arrived,
                    // so a guest cannot review a stay that has not happened yet.
                    ->visible(fn (Booking $record): bool => $record->status === 'confirmed' && $record->check_out->lte(today()))
                    ->action(fn (Booking $record) => $record->update(['status' => 'completed'])),
            ]);
    }

    /**
     * The booking pop-up, opened from a row or straight from a notification
     * link (OwnerBookingNotification::bookingUrl()).
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('status')
                ->badge()
                ->color(fn (string $state): string => static::statusColor($state)),
            Section::make('Stay')
                ->columns(2)
                ->schema([
                    TextEntry::make('check_in')
                        ->formatStateUsing(fn (Booking $record): string => static::stayDate($record, 'check_in')),
                    TextEntry::make('check_out')
                        ->formatStateUsing(fn (Booking $record): string => static::stayDate($record, 'check_out')),
                    TextEntry::make('nights')
                        ->state(fn (Booking $record): string => ($n = (int) $record->check_in->diffInDays($record->check_out)).' '.str('night')->plural($n)),
                    TextEntry::make('total_price')
                        ->label('Total')
                        ->formatStateUsing(fn ($state): string => Money::format($state)),
                    TextEntry::make('notes')
                        ->label("Guest's notes")
                        ->placeholder('None')
                        ->columnSpanFull(),
                ]),
            Section::make('Pet')
                ->columns(2)
                ->schema([
                    TextEntry::make('pet.name')->label('Name'),
                    TextEntry::make('pet.species')
                        ->label('Type')
                        ->formatStateUsing(fn (string $state): string => PetType::tryFrom($state)?->label() ?? $state),
                    TextEntry::make('pet.breed')->label('Breed')->placeholder('—'),
                    TextEntry::make('pet.age')->label('Age')->placeholder('—'),
                    TextEntry::make('pet.special_needs')
                        ->label('Special needs')
                        ->placeholder('None')
                        ->columnSpanFull(),
                ]),
            Section::make('Guest')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.name')->label('Name'),
                    TextEntry::make('user.email')->label('Email'),
                    TextEntry::make('user.phone')->label('Phone')->placeholder('—'),
                    TextEntry::make('created_at')->label('Requested')->dateTime('d M Y, g:i A'),
                ]),
        ]);
    }

    /** The row action and the pop-up's footer button are the same action. */
    protected static function confirmAction(string $name): Action
    {
        return Action::make($name)
            ->label('Confirm')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Confirm Booking')
            ->modalDescription('This will confirm the booking and notify the guest.')
            ->visible(fn (Booking $record): bool => $record->status === 'pending')
            ->action(fn (Booking $record) => static::confirm($record));
    }

    protected static function declineAction(string $name): Action
    {
        return Action::make($name)
            ->label('Decline')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Decline Booking')
            ->modalDescription('This will decline the booking and notify the guest.')
            ->visible(fn (Booking $record): bool => $record->status === 'pending')
            ->action(fn (Booking $record) => $record->cancel(CancelledBy::Hotel));
    }

    protected static function statusColor(string $state): string
    {
        return match ($state) {
            'pending' => 'warning',
            'confirmed' => 'success',
            'cancelled' => 'danger',
            'completed' => 'info',
            default => 'gray',
        };
    }

    /** "Sat, 25 Oct 2026, from 2:00 PM" when the hotel has set its times. */
    protected static function stayDate(Booking $record, string $field): string
    {
        $date = $record->{$field}->format('D, d M Y');
        $times = $record->hotel->policy?->stayTimes();

        if (! $times) {
            return $date;
        }

        return $field === 'check_in'
            ? $date.', from '.$times['check_in']
            : $date.', by '.$times['check_out'];
    }

    /**
     * The stay may have stopped fitting since it was requested: other bookings
     * were confirmed, the owner closed a date, or the pet was confirmed elsewhere.
     */
    protected static function confirm(Booking $record): void
    {
        try {
            $record->confirm();
        } catch (BookingDoesNotFit $e) {
            Notification::make()
                ->danger()
                ->title('Cannot confirm this booking')
                ->body($e->getMessage())
                ->send();
        }
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('hotel_id', static::ownerHotel()->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
        ];
    }
}
