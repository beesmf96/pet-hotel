<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Just after midnight UTC (8:15 AM in Malaysia): requests whose check-in day
// has ended.
Schedule::command('bookings:expire-pending')->dailyAt('00:15');
