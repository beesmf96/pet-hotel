<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['hotel_id', 'check_in_time', 'check_out_time', 'cancellation_policy'])]
class PetHotelPolicy extends Model
{
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(PetHotel::class, 'hotel_id');
    }

    /**
     * Drop-off and pick-up times as customers read them, e.g. "2:00 PM".
     *
     * @return array{check_in: string, check_out: string}
     */
    public function stayTimes(): array
    {
        return [
            'check_in' => Carbon::parse($this->check_in_time)->format('g:i A'),
            'check_out' => Carbon::parse($this->check_out_time)->format('g:i A'),
        ];
    }
}
