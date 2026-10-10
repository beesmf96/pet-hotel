<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_format_prints_the_configured_symbol_with_thousands(): void
    {
        $this->assertSame('RM 1,234.50', Money::format('1234.5'));
        $this->assertSame('RM 0.00', Money::format(null));
    }

    public function test_format_can_drop_the_decimals(): void
    {
        $this->assertSame('RM 45', Money::format(45.2, decimals: 0));
    }

    public function test_format_follows_the_currency_config(): void
    {
        config(['app.currency.symbol' => 'S$']);

        $this->assertSame('S$ 10.00', Money::format(10));
    }

    public function test_sen_round_trip_is_exact(): void
    {
        $this->assertSame(10, Money::toSen('0.10'));
        $this->assertSame(4550, Money::toSen(45.5));
        $this->assertSame('0.30', Money::fromSen(Money::toSen('0.10') * 3));
        $this->assertSame('1234.50', Money::fromSen(123450));
    }
}
