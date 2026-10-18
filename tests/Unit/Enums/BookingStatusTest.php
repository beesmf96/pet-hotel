<?php

namespace Tests\Unit\Enums;

use App\Enums\BookingStatus;
use PHPUnit\Framework\TestCase;

class BookingStatusTest extends TestCase
{
    public function test_each_status_has_a_badge_colour(): void
    {
        $this->assertSame('warning', BookingStatus::colorOf('pending'));
        $this->assertSame('success', BookingStatus::colorOf('confirmed'));
        $this->assertSame('danger', BookingStatus::colorOf('cancelled'));
        $this->assertSame('info', BookingStatus::colorOf('completed'));
    }

    public function test_an_unknown_status_is_gray(): void
    {
        $this->assertSame('gray', BookingStatus::colorOf('archived'));
    }

    public function test_options_list_every_status_with_its_label(): void
    {
        $this->assertSame([
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
        ], BookingStatus::options());
    }
}
