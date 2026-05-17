<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'hotel_id', 'pet_id', 'check_in', 'check_out', 'status', 'notes', 'total_price'])]
class Booking extends Model
{
    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'total_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(function (Booking $booking) {
            if (! $booking->isDirty('status')) {
                return;
            }

            $original = $booking->getOriginal('status');
            $new = $booking->status;

            if ($new === 'confirmed' && $original !== 'confirmed') {
                self::adjustAvailability($booking, -1);
            } elseif ($new === 'cancelled' && $original === 'confirmed') {
                self::adjustAvailability($booking, 1);
            }
        });
    }

    private static function adjustAvailability(Booking $booking, int $delta): void
    {
        // Use whereBetween with Carbon so the query works across SQLite and PostgreSQL.
        // check_out is the departure date (not a night), so the range is [check_in, check_out - 1 day].
        HotelAvailability::where('hotel_id', $booking->hotel_id)
            ->whereBetween('date', [$booking->check_in, $booking->check_out->copy()->subDay()])
            ->increment('available_spots', $delta);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(PetHotel::class, 'hotel_id');
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }
}
