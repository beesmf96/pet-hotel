<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A booking cannot be confirmed: at least one of its nights is closed or has
 * no spot left, or the pet is already staying somewhere on one of them. The
 * panels show the message to the owner or admin as it is.
 */
class BookingDoesNotFit extends RuntimeException
{
    public static function noRoom(): self
    {
        return new self('The hotel has no spot left, or is closed, on at least one night of this stay. Decline it, or open more spots on those dates first.');
    }

    public static function petAlreadyBooked(): self
    {
        return new self('This pet already has a confirmed stay on at least one of these nights, here or at another hotel. A pet can only stay in one place a night, so decline this request.');
    }
}
