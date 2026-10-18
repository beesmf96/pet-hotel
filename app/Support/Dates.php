<?php

namespace App\Support;

/**
 * How a date is written for a person to read: in emails, panel pop-ups and
 * messages. Every user-facing date goes through one of these formats.
 */
final class Dates
{
    /** "Sat, 25 Oct 2026" */
    public const DAY = 'D, d M Y';

    /** "Sat, 25 Oct 2026, 2:00 PM" */
    public const DAY_TIME = 'D, d M Y, g:i A';
}
