<?php

namespace App\Support;

/**
 * How a date is written for a person to read: in emails, panel pop-ups and
 * messages. Every user-facing date goes through one of these formats.
 * The JS twin is resources/js/composables/useFormatDate.js.
 */
final class Dates
{
    /** "Sat, 25 Oct 2026" */
    public const DAY = 'D, d M Y';

    /** "25 Oct 2026" — the default for every panel table column (AppServiceProvider). */
    public const SHORT = 'd M Y';

    /** "Sat, 25 Oct 2026, 2:00 PM" */
    public const DAY_TIME = 'D, d M Y, g:i A';

    /** "25 Oct 2026, 2:00 PM" */
    public const SHORT_TIME = 'd M Y, g:i A';

    /** "25 Oct, 2:00 PM" — a moment close to now, like a deadline or a notification. */
    public const MOMENT = 'd M, g:i A';

    /** "2:00 PM" — always 12-hour, never "14:00". */
    public const TIME = 'g:i A';
}
