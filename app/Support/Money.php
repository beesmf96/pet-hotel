<?php

namespace App\Support;

/**
 * Prices are stored as decimal ringgit (decimal(10,2)), but sums are done in
 * whole sen so a float never rounds a total. Every price shown to a person is
 * printed by format(), so the currency is set in one place
 * (config('app.currency')). The JS twin is resources/js/money.js.
 */
class Money
{
    public static function toSen(string|int|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /** A sen amount as the decimal string the database column stores. */
    public static function fromSen(int $sen): string
    {
        return number_format($sen / 100, 2, '.', '');
    }

    /** "RM 1,234.50"; $decimals = 0 gives "RM 1,235" for "from" prices. */
    public static function format(string|int|float|null $amount, int $decimals = 2): string
    {
        return config('app.currency.symbol').' '.number_format((float) $amount, $decimals);
    }
}
