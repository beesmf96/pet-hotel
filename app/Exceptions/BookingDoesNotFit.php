<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A booking cannot be confirmed because at least one of its nights is closed
 * or has no spot left. The panels turn this into a notice for the owner.
 */
class BookingDoesNotFit extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The hotel has no spot left, or is closed, on at least one night of this stay.');
    }
}
