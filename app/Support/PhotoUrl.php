<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Photo columns hold two kinds of value and always have: an absolute URL when
 * the row came from PetHotelSeeder's remote demo images, and a storage path when
 * an admin uploaded the photo through Filament. Templates bind the value into
 * :src directly, so a path has to become a URL before it leaves the model.
 */
class PhotoUrl
{
    public static function resolve(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return self::isAbsolute($value)
            ? $value
            : Storage::disk(config('filesystems.photos'))->url($value);
    }

    /**
     * Anything the browser can already fetch as-is: a full URL, a
     * protocol-relative "//host/path", or a root-relative "/path" that is
     * served by this app.
     */
    private static function isAbsolute(string $value): bool
    {
        return str_starts_with($value, '/')
            || preg_match('#^[a-z][a-z0-9+.-]*://#i', $value) === 1;
    }
}
