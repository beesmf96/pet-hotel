<?php

namespace App\Http\Controllers;

use App\Models\PetHotel;
use App\Models\PetHotelPricing;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(): Response
    {
        $featuredHotels = PetHotel::withAvg('reviews', 'rating')
            ->with(['photos', 'facilities'])
            ->addSelect([
                'price_from' => PetHotelPricing::selectRaw('MIN(price_per_night)')
                    ->whereColumn('hotel_id', 'pet_hotels.id')
                    ->limit(1),
            ])
            ->where('is_active', true)
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        return Inertia::render('Landing', [
            'featuredHotels' => $featuredHotels,
        ]);
    }
}
