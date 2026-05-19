<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NewUsersThisWeekWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $count = User::where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('New Users This Week', $count)
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('success'),
        ];
    }
}
