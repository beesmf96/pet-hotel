<?php

namespace App\Filament\HotelOwner\Concerns;

use App\Models\PetHotel;

/**
 * Everything in the owner panel is scoped to the signed-in owner's hotel.
 * An owner attached to several hotels manages the first one.
 */
trait ResolvesOwnerHotel
{
    protected static function ownerHotel(): PetHotel
    {
        $hotel = auth()->user()?->ownedHotels()->first();

        if (! $hotel) {
            abort(403, 'No hotel assigned to your account. Contact the administrator.');
        }

        return $hotel;
    }
}
