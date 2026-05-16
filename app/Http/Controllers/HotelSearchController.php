<?php

namespace App\Http\Controllers;

use App\Models\PetHotel;
use App\Models\PetHotelPricing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HotelSearchController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PetHotel::query()
            ->with(['facilities'])
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
        ]);
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
