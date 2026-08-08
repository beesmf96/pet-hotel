<?php

namespace App\Models;

use App\Support\PhotoUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['hotel_id', 'url', 'sort_order'])]
class PetHotelPhoto extends Model
{
    /**
     * The `url` column is a misnomer: Filament writes a storage path into it,
     * and only the seeder's demo rows hold real URLs. This is the value
     * templates can actually bind to :src.
     *
     * @var list<string>
     */
    protected $appends = ['photo_url'];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(PetHotel::class, 'hotel_id');
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => PhotoUrl::resolve($this->url));
    }
}
