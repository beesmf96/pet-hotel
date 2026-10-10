<?php

namespace App\Enums;

/**
 * Who ended a booking. The customer's email differs: a guest cancelling their
 * own request, a hotel declining a request, and a hotel cancelling a stay it
 * had confirmed each need different words. Not stored; only passed along.
 */
enum CancelledBy: string
{
    case Guest = 'guest';
    case Hotel = 'hotel';
}
