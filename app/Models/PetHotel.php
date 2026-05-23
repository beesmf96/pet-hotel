<?php

namespace App\Models;

use Database\Factories\PetHotelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'slug', 'description', 'address', 'city', 'lat', 'lng', 'cover_photo', 'is_active'])]
class PetHotel extends Model
{
    /** @use HasFactory<PetHotelFactory> */
    use HasFactory;

    public function facilities(): HasMany
    {
        return $this->hasMany(PetHotelFacility::class, 'hotel_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PetHotelPhoto::class, 'hotel_id')->orderBy('sort_order');
    }

    public function policy(): HasOne
    {
        return $this->hasOne(PetHotelPolicy::class, 'hotel_id');
    }

    public function pricing(): HasMany
    {
        return $this->hasMany(PetHotelPricing::class, 'hotel_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'hotel_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'hotel_id')->where('is_visible', true);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hotel_owner', 'hotel_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }
}
