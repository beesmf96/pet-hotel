<?php

namespace App\Filament\HotelOwner\Pages;

use App\Filament\HotelOwner\Concerns\ResolvesOwnerHotel;
use App\Support\Availability;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

/**
 * One row per night of a month: capacity, confirmed bookings, spots left.
 * Read-only — dates are changed under Availability, capacity under Hotel
 * settings. Pending requests are not counted, as they hold no spot.
 */
class DailyOverview extends Page implements HasTable
{
    use InteractsWithTable;
    use ResolvesOwnerHotel;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $navigationLabel = 'Daily overview';

    protected static ?string $title = 'Daily overview';

    protected static ?int $navigationSort = 1;

    public function getSubheading(): ?string
    {
        return 'Booked counts confirmed and completed stays. Pending requests do not take a spot until you confirm them.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    /** @return array<string, string> The current month and the eleven after it. */
    protected static function monthOptions(): array
    {
        $options = [];
        $month = today()->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $options[$month->format('Y-m')] = $month->format('F Y');
            $month->addMonth();
        }

        return $options;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (array $filters): array {
                $start = Carbon::parse(($filters['month']['value'] ?? null ?: today()->format('Y-m')).'-01');
                $hotel = static::ownerHotel();
                $normal = $hotel->capacity;

                $rows = [];
                foreach (Availability::nights($hotel, $start, $start->copy()->endOfMonth()) as $date => $night) {
                    $rows[$date] = [
                        'date' => $date,
                        'capacity' => $night['capacity'],
                        'changed' => $night['capacity'] !== $normal,
                        'booked' => $night['booked'],
                        'spots_left' => $night['blocked'] ? null : $night['spots_left'],
                        'status' => Availability::status($night),
                    ];
                }

                return $rows;
            })
            ->columns([
                TextColumn::make('date')
                    ->date('D, d M Y'),
                TextColumn::make('capacity')
                    ->label('Pets per night')
                    ->description(fn (array $record): ?string => $record['changed'] ? 'Changed for this date' : null),
                TextColumn::make('booked'),
                TextColumn::make('spots_left')
                    ->label('Spots left')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'blocked' => 'Closed',
                        'full' => 'Full',
                        'limited' => 'Limited',
                        default => 'Open',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'blocked' => 'gray',
                        'full' => 'danger',
                        'limited' => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('month')
                    ->options(static::monthOptions())
                    ->default(today()->format('Y-m'))
                    ->selectablePlaceholder(false),
            ])
            ->paginated(false);
    }
}
