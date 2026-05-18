<?php

namespace App\Http\Controllers;

use App\Models\PetHotel;
use App\Models\Review;
use Inertia\Inertia;
use Inertia\Response;

class HotelController extends Controller
{
    public function show(string $slug): Response
    {
        $hotel = PetHotel::where('slug', $slug)
            ->with(['facilities', 'photos', 'policy', 'pricing'])
            ->firstOrFail();

        $reviewsCount = $hotel->reviews()->count();
        $averageRating = $reviewsCount > 0 ? round($hotel->reviews()->avg('rating'), 1) : null;

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
            'reviews' => $reviews,
            'reviews_count' => $reviewsCount,
            'average_rating' => $averageRating,
        ]);
    }
}
