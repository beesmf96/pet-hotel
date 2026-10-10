<?php

namespace App\Models;

use App\Exceptions\BookingDoesNotFit;
use App\Jobs\SendBookingCancelledNotification;
use App\Jobs\SendBookingConfirmationNotification;
use App\Support\Availability;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

#[Fillable(['user_id', 'hotel_id', 'pet_id', 'check_in', 'check_out', 'status', 'notes', 'total_price'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'total_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updated(function (Booking $booking) {
            if (! $booking->wasChanged('status')) {
                return;
            }

            $new = $booking->status;
            $original = $booking->getOriginal('status');

            if ($new === 'confirmed' && $original !== 'confirmed') {
                SendBookingConfirmationNotification::dispatch($booking);
            } elseif ($new === 'cancelled') {
                SendBookingCancelledNotification::dispatch($booking);
            }
        });
    }

    /**
     * Confirm a pending request if the hotel still has room for every night.
     *
     * Pending requests do not hold a spot, so the check that matters happens
     * here. The hotel row is locked for the duration so two confirms cannot
     * both take the last spot.
     *
     * @throws BookingDoesNotFit
     */
    public function confirm(): void
    {
        DB::transaction(function () {
            $hotel = PetHotel::whereKey($this->hotel_id)->lockForUpdate()->firstOrFail();

            if (! Availability::fits($hotel, $this->check_in, $this->check_out)) {
                throw new BookingDoesNotFit;
            }

            $this->update(['status' => 'confirmed']);
        });
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

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
