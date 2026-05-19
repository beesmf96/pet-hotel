<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingsTodayWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $count = Booking::whereDate('check_in', today())->count();

        return [
            Stat::make('Check-ins Today', $count)
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('info'),
        ];
    }
}
