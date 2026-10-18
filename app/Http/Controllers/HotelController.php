<?php

namespace App\Http\Controllers;

use App\Models\PetHotel;
use App\Models\Review;
use App\Support\Stay;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HotelController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $hotel = PetHotel::where('slug', $slug)
            ->where('is_active', true)
            ->with(['facilities', 'photos', 'policy', 'pricing'])
            ->firstOrFail();

        $stats = $hotel->reviews()
            ->selectRaw('COUNT(*) as count, AVG(rating) as avg_rating')
            ->first();
        $reviewsCount = (int) $stats->count;
        $averageRating = $reviewsCount > 0 ? round((float) $stats->avg_rating, 1) : null;

        $reviews = $hotel->reviews()
            ->with('user:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Review $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'user_name' => $r->user->name,
                'created_at' => $r->created_at->toDateString(),
            ]);

        return Inertia::render('Hotels/HotelProfilePage', [
            'hotel' => $hotel,
            // The same "2:00 PM" wording the booking pages and emails use.
            'times' => $hotel->policy?->stayTimes(),
            'stay' => Stay::upcomingFromRequest($request)?->toArray(),
            'reviews' => $reviews,
            'reviewsCount' => $reviewsCount,
            'averageRating' => $averageRating,
        ]);
    }
}
