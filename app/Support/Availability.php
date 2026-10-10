<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\HotelAvailability;
use App\Models\PetHotel;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The one place that decides how many pets a hotel can still take on a night.
 *
 * Nothing stores a "spots left" number. A night's capacity is the hotel's
 * normal capacity unless the owner set a different one for that date, and
 * spots left is that capacity minus the bookings holding a spot. A night is a
 * date the pet sleeps over: a stay from the 25th to the 28th covers the 25th,
 * 26th and 27th, never the check-out day.
 */
class Availability
{
    /** Statuses that hold a spot. Pending requests do not; cancelled ones never did. */
    public const HOLDING_STATUSES = ['confirmed', 'completed'];

    /**
     * Every night from $first to $last inclusive, keyed by Y-m-d.
     *
     * @return array<string, array{capacity: int, booked: int, spots_left: int, blocked: bool}>
     */
    public static function nights(PetHotel $hotel, CarbonInterface $first, CarbonInterface $last): array
    {
        $first = Carbon::parse($first)->startOfDay();
        $last = Carbon::parse($last)->startOfDay();

        $overrides = HotelAvailability::where('hotel_id', $hotel->id)
            ->whereBetween('date', [$first, $last])
            ->get()
            ->keyBy(fn (HotelAvailability $row) => $row->date->toDateString());

        $booked = self::bookedPerNight($hotel, $first, $last);

        $nights = [];
        for ($night = $first->copy(); $night->lte($last); $night->addDay()) {
            $key = $night->toDateString();
            $override = $overrides->get($key);
            $capacity = $override?->capacity ?? $hotel->capacity;
            $taken = $booked[$key] ?? 0;

            $nights[$key] = [
                'capacity' => $capacity,
                'booked' => $taken,
                'spots_left' => max(0, $capacity - $taken),
                'blocked' => (bool) $override?->is_blocked,
            ];
        }

        return $nights;
    }

    /**
     * Whether one more pet can stay every night from check-in up to, but not
     * including, check-out.
     */
    public static function fits(PetHotel $hotel, CarbonInterface $checkIn, CarbonInterface $checkOut): bool
    {
        $lastNight = Carbon::parse($checkOut)->subDay();

        foreach (self::nights($hotel, $checkIn, $lastNight) as $night) {
            if ($night['blocked'] || $night['spots_left'] < 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, int>
     */
    private static function bookedPerNight(PetHotel $hotel, Carbon $first, Carbon $last): array
    {
        // A wide window, then exact nights below from the date casts: SQLite
        // keeps whatever time part was written into a date column, so a
        // check-in on the last night may sort after midnight of that night.
        $bookings = Booking::where('hotel_id', $hotel->id)
            ->whereIn('status', self::HOLDING_STATUSES)
            ->where('check_in', '<', $last->copy()->addDay())
            ->where('check_out', '>', $first)
            ->get(['check_in', 'check_out']);

        $booked = [];
        foreach ($bookings as $booking) {
            // max()/min() hand back one of the two instances, so copy after.
            $night = $booking->check_in->max($first)->copy();
            $end = $booking->check_out->min($last->copy()->addDay())->copy();

            for (; $night->lt($end); $night->addDay()) {
                $key = $night->toDateString();
                $booked[$key] = ($booked[$key] ?? 0) + 1;
            }
        }

        return $booked;
    }
}
