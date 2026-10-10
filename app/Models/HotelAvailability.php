<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An owner's change to one date: closed, or a capacity other than the hotel's
 * normal one (null keeps the normal one). Dates without a row use the hotel's
 * capacity. Spots left are never stored — see App\Support\Availability.
 */
class HotelAvailability extends Model
{
    protected $fillable = ['hotel_id', 'date', 'capacity', 'is_blocked'];

    protected $casts = [
        'date' => 'date',
        'is_blocked' => 'boolean',
        'capacity' => 'integer',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(PetHotel::class, 'hotel_id');
    }
}
