<?php

namespace Tests\Unit\Models;

use App\Models\Booking;
use Tests\TestCase;

/**
 * Booking::answerBy(): 48 hours, or noon the day before check-in if sooner,
 * or the end of the check-in day once that noon has passed. Malaysia is
 * UTC+8, so noon there is 04:00 UTC.
 */
class BookingAnswerByTest extends TestCase
{
    public function test_a_request_well_ahead_gets_48_hours(): void
    {
        $booking = $this->requested(at: '2026-10-18 02:00:00', checkIn: '2026-11-01');

        $this->assertSame('2026-10-20 02:00:00', $booking->answerBy()->toDateTimeString());
    }

    public function test_a_request_near_check_in_must_be_answered_by_noon_the_day_before(): void
    {
        $booking = $this->requested(at: '2026-10-30 02:00:00', checkIn: '2026-11-01');

        $this->assertSame('2026-10-31 04:00:00', $booking->answerBy()->toDateTimeString());
    }

    public function test_a_request_sent_after_that_noon_has_until_the_end_of_check_in_day(): void
    {
        $dayBefore = $this->requested(at: '2026-10-31 06:00:00', checkIn: '2026-11-01');
        $sameDay = $this->requested(at: '2026-11-01 01:00:00', checkIn: '2026-11-01');

        $this->assertSame('2026-11-01 15:59:59', $dayBefore->answerBy()->toDateTimeString());
        $this->assertSame('2026-11-01 15:59:59', $sameDay->answerBy()->toDateTimeString());
    }

    public function test_the_deadline_reads_in_local_time(): void
    {
        $booking = $this->requested(at: '2026-10-18 02:00:00', checkIn: '2026-11-01');

        $this->assertSame('Tue, 20 Oct 2026, 10:00 AM', $booking->answerByText());
    }

    private function requested(string $at, string $checkIn): Booking
    {
        return (new Booking)->forceFill(['check_in' => $checkIn, 'created_at' => $at]);
    }
}
