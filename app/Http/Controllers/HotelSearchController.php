<?php

namespace App\Http\Controllers;

use App\Models\PetHotel;
use App\Models\PetHotelPricing;
use App\Support\Availability;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class HotelSearchController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PetHotel::query()
            ->where('is_active', true)
            ->with(['facilities'])
            ->withAvg('reviews', 'rating')
            ->addSelect([
                'price_from' => PetHotelPricing::selectRaw('MIN(price_per_night)')
                    ->whereColumn('hotel_id', 'pet_hotels.id'),
            ]);

        if ($request->filled('city')) {
            $query->whereRaw('LOWER(city) LIKE ?', ['%'.strtolower($request->city).'%']);
        }

        if ($request->filled('pet_type') || $request->filled('price_min') || $request->filled('price_max')) {
            $query->whereHas('pricing', function ($q) use ($request) {
                if ($request->filled('pet_type')) {
                    $q->where('pet_type', $request->pet_type);
                }
                if ($request->filled('price_min')) {
                    $q->where('price_per_night', '>=', (float) $request->price_min);
                }
                if ($request->filled('price_max')) {
                    $q->where('price_per_night', '<=', (float) $request->price_max);
                }
            });
        }

        if ($request->filled('facilities')) {
            $facilities = is_array($request->facilities)
                ? array_filter($request->facilities)
                : array_filter(explode(',', $request->facilities));
            foreach ($facilities as $facility) {
                $query->whereHas('facilities', fn ($q) => $q->where('type', $facility));
            }
        }

        $stay = $this->stay($request);
        if ($stay) {
            $query->whereNotIn('pet_hotels.id', Availability::unavailableHotelIds($stay['check_in'], $stay['check_out']));
        }

        match ($request->sort) {
            'price_asc' => $query->orderByRaw(
                '(SELECT MIN(price_per_night) FROM pet_hotel_pricing WHERE hotel_id = pet_hotels.id) ASC NULLS LAST'
            ),
            'price_desc' => $query->orderByRaw(
                '(SELECT MIN(price_per_night) FROM pet_hotel_pricing WHERE hotel_id = pet_hotels.id) DESC NULLS LAST'
            ),
            'distance' => $this->applyDistanceSort($query, $request),
            default => $query->latest('pet_hotels.created_at'),
        };

        $hotels = $query->paginate(15)->withQueryString();

        return Inertia::render('Hotels/SearchPage', [
            'hotels' => $hotels,
            'filters' => $request->only([
                'city', 'pet_type', 'price_min', 'price_max',
                'sort', 'facilities', 'check_in', 'check_out',
            ]),
            'stay' => $stay ? [
                'check_in' => $stay['check_in']->toDateString(),
                'check_out' => $stay['check_out']->toDateString(),
                'nights' => (int) $stay['check_in']->diffInDays($stay['check_out']),
            ] : null,
        ]);
    }

    /**
     * The stay to filter by: both dates valid and check-out after check-in.
     * Anything else filters nothing, as the dates did before.
     *
     * @return array{check_in: Carbon, check_out: Carbon}|null
     */
    private function stay(Request $request): ?array
    {
        $dates = [];
        foreach (['check_in', 'check_out'] as $key) {
            $value = $request->input($key);
            if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return null;
            }
            // Round trip: 2026-02-30 parses as 2 March, which is not what was typed.
            $date = Carbon::createFromFormat('!Y-m-d', $value);
            if ($date->toDateString() !== $value) {
                return null;
            }
            $dates[$key] = $date;
        }

        return $dates['check_out']->gt($dates['check_in']) ? $dates : null;
    }

    private function applyDistanceSort($query, Request $request): void
    {
        if ($request->filled('lat') && $request->filled('lng')) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;
            $query->orderByRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) ASC',
                [$lat, $lng, $lat]
            );
        } else {
            $query->latest('pet_hotels.created_at');
        }
    }
}
