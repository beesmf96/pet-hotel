<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\HotelOwnerPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    HotelOwnerPanelProvider::class,
];
