<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requests past their answer deadline (Booking::answerBy()). Deadlines fall
// at any minute, so this runs often: a guest hears within five minutes.
Schedule::command('bookings:expire-pending')->everyFiveMinutes();
