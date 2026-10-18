<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The stay dates a customer typed into search, carried in the query string
 * from the results to the hotel page and on to the booking form.
 */
final class Stay
{
    public function __construct(
        public readonly Carbon $checkIn,
        public readonly Carbon $checkOut,
    ) {}

    /**
     * Both dates valid and check-out after check-in, or null. Anything else
     * is ignored rather than rejected: these are hints, not a booking.
     */
    public static function fromRequest(Request $request): ?self
    {
        $dates = [];
        foreach (['check_in', 'check_out'] as $key) {
            $value = $request->query($key);
            if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return null;
            }
            // Round trip: 2026-02-30 parses as 2 March, which is not what was typed.
            $date = Carbon::createFromFormat('!Y-m-d', $value);
            if ($date->toDateString() !== $value) {
                return null;
            }
            $dates[] = $date;
        }

        return $dates[1]->gt($dates[0]) ? new self(...$dates) : null;
    }

    /** Only a stay that has not started can still be booked. */
    public static function upcomingFromRequest(Request $request): ?self
    {
        $stay = self::fromRequest($request);

        return $stay?->checkIn->gte(today()) ? $stay : null;
    }

    public function nights(): int
    {
        return (int) $this->checkIn->diffInDays($this->checkOut);
    }

    /** @return array{check_in: string, check_out: string, nights: int} */
    public function toArray(): array
    {
        return [
            'check_in' => $this->checkIn->toDateString(),
            'check_out' => $this->checkOut->toDateString(),
            'nights' => $this->nights(),
        ];
    }
}
