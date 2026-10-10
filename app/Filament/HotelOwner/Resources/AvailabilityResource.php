<?php

namespace App\Filament\HotelOwner\Resources;

use App\Filament\HotelOwner\Concerns\ResolvesOwnerHotel;
use App\Filament\HotelOwner\Resources\AvailabilityResource\Pages;
use App\Models\HotelAvailability;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Dates that differ from the hotel's normal capacity: closed, or a different
 * number of pets. A date with no row here uses the normal capacity from Hotel
 * settings. Spots left are worked out from these and the bookings, never
 * stored (App\Support\Availability).
 */
class AvailabilityResource extends Resource
{
    use ResolvesOwnerHotel;

    protected static ?string $model = HotelAvailability::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Availability';

    protected static ?string $modelLabel = 'date change';

    protected static ?int $navigationSort = 2;

    /**
     * The fields shared by the row edit form and the "Change dates" action.
     *
     * @return list<Component>
     */
    public static function overrideFields(): array
    {
        return [
            Toggle::make('is_blocked')
                ->label('Closed')
                ->helperText('No one can book these nights.')
                ->live(),
            TextInput::make('capacity')
                ->label('Pets per night')
                ->helperText(fn () => 'Leave empty to keep the normal capacity ('.static::ownerHotel()->capacity.').')
                ->integer()
                ->minValue(0)
                ->maxValue(1000)
                ->required(fn (Get $get): bool => ! $get('is_blocked'))
                ->hidden(fn (Get $get): bool => (bool) $get('is_blocked')),
        ];
    }

    /**
     * @return list<Component>
     */
    public static function rangeFields(): array
    {
        return [
            DatePicker::make('from')
                ->required()
                ->live(),
            DatePicker::make('until')
                ->label('Until (last night)')
                ->required()
                ->afterOrEqual('from')
                ->maxDate(fn (Get $get) => $get('from') ? Carbon::parse($get('from'))->addYear() : null),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')->disabled(),
            ...static::overrideFields(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date('D, d M Y')
                    ->sortable(),
                IconColumn::make('is_blocked')
                    ->label('Closed')
                    ->boolean(),
                TextColumn::make('capacity')
                    ->label('Pets per night')
                    ->placeholder('Normal'),
            ])
            ->defaultSort('date')
            ->filters([
                Filter::make('upcoming')
                    ->label('Today and later')
                    ->query(fn (Builder $query) => $query->where('date', '>=', today()))
                    ->default(),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(fn (array $data): array => static::normalise($data)),
                DeleteAction::make()
                    ->label('Reset')
                    ->modalHeading('Reset this date')
                    ->modalDescription('The date goes back to open, with the normal capacity.'),
            ]);
    }

    /**
     * A closed date keeps no capacity of its own, so reopening it falls back
     * to the normal capacity rather than a forgotten number.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalise(array $data): array
    {
        $data['is_blocked'] = (bool) ($data['is_blocked'] ?? false);

        if ($data['is_blocked']) {
            $data['capacity'] = null;
        }

        return $data;
    }

    /**
     * Apply one change to every date from $from to $until inclusive.
     * Looked up by date rather than upserted: SQLite stores the date cast with
     * a time part, so a raw "Y-m-d" key would not match existing rows.
     *
     * @param  array<string, mixed>  $data
     */
    public static function applyToRange(array $data): int
    {
        $hotel = static::ownerHotel();
        $values = static::normalise($data);
        $until = Carbon::parse($data['until']);
        $count = 0;

        for ($date = Carbon::parse($data['from']); $date->lte($until); $date->addDay()) {
            $row = HotelAvailability::where('hotel_id', $hotel->id)->whereDate('date', $date)->first()
                ?? new HotelAvailability(['hotel_id' => $hotel->id, 'date' => $date->copy()]);

            $row->fill(['is_blocked' => $values['is_blocked'], 'capacity' => $values['capacity'] ?? null])->save();
            $count++;
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function resetRange(array $data): int
    {
        return HotelAvailability::where('hotel_id', static::ownerHotel()->id)
            ->whereBetween('date', [Carbon::parse($data['from']), Carbon::parse($data['until'])])
            ->delete();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('hotel_id', static::ownerHotel()->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAvailability::route('/'),
        ];
    }
}
