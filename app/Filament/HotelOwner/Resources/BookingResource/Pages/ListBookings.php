<?php

namespace App\Filament\HotelOwner\Resources\BookingResource\Pages;

use App\Filament\HotelOwner\Resources\BookingResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;
}
