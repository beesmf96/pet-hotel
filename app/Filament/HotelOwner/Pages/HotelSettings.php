<?php

namespace App\Filament\HotelOwner\Pages;

use App\Filament\HotelOwner\Concerns\ResolvesOwnerHotel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The owner's own hotel settings: how many pets it takes on a normal night,
 * and when guests drop off and pick up. Dates that differ from the normal
 * capacity are set under Availability.
 */
class HotelSettings extends Page
{
    use ResolvesOwnerHotel;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Hotel settings';

    protected static ?string $title = 'Hotel settings';

    protected static ?int $navigationSort = 3;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $hotel = static::ownerHotel()->load('policy');

        $this->form->fill([
            'capacity' => $hotel->capacity,
            'check_in_time' => $hotel->policy?->check_in_time,
            'check_out_time' => $hotel->policy?->check_out_time,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Capacity')
                    ->description('Spots left on a night are this number minus the confirmed bookings for that night.')
                    ->schema([
                        TextInput::make('capacity')
                            ->label('Pets per night')
                            ->helperText('Close a date, or set a different number for some dates, under Availability.')
                            ->integer()
                            ->minValue(0)
                            ->maxValue(1000)
                            ->required(),
                    ]),
                Section::make('Check-in and check-out')
                    ->description('Shown to guests on the booking form, their booking, and their emails.')
                    ->schema([
                        TimePicker::make('check_in_time')
                            ->label('Check-in from')
                            ->seconds(false)
                            ->required(),
                        TimePicker::make('check_out_time')
                            ->label('Check-out by')
                            ->seconds(false)
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save')
                                ->submit('save'),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $hotel = static::ownerHotel();

        $hotel->update(['capacity' => $data['capacity']]);
        $hotel->policy()->updateOrCreate([], [
            'check_in_time' => $data['check_in_time'],
            'check_out_time' => $data['check_out_time'],
        ]);

        Notification::make()
            ->success()
            ->title('Hotel settings saved')
            ->send();
    }
}
