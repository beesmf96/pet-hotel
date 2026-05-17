<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelAvailability extends Model
{
    protected $fillable = ['hotel_id', 'date', 'available_spots', 'is_blocked'];

    protected $casts = [
        'date' => 'date',
        'is_blocked' => 'boolean',
        'available_spots' => 'integer',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(PetHotel::class, 'hotel_id');
    }
}
