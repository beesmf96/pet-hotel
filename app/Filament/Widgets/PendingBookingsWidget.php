<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingBookingsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $count = Booking::where('status', 'pending')->count();

        return [
            Stat::make('Pending Bookings', $count)
                ->icon(Heroicon::OutlinedClock)
                ->color('warning'),
        ];
    }
}
