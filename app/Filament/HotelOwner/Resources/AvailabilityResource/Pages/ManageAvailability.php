<?php

namespace App\Filament\HotelOwner\Resources\AvailabilityResource\Pages;

use App\Filament\HotelOwner\Resources\AvailabilityResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageAvailability extends ManageRecords
{
    protected static string $resource = AvailabilityResource::class;

    protected ?string $subheading = 'Dates not listed here are open, with the normal capacity from Hotel settings.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeDates')
                ->label('Change dates')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->modalDescription('Close a range of nights, or set a different number of pets for them. For one night, pick the same date twice.')
                ->schema([
                    ...AvailabilityResource::rangeFields(),
                    ...AvailabilityResource::overrideFields(),
                ])
                ->action(function (array $data): void {
                    $count = AvailabilityResource::applyToRange($data);

                    Notification::make()->success()->title("Updated {$count} ".str('night')->plural($count))->send();
                }),

            Action::make('resetDates')
                ->label('Reset dates')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->modalDescription('Nights in this range go back to open, with the normal capacity.')
                ->schema(AvailabilityResource::rangeFields())
                ->action(function (array $data): void {
                    $count = AvailabilityResource::resetRange($data);

                    Notification::make()->success()->title("Reset {$count} ".str('night')->plural($count))->send();
                }),
        ];
    }
}
