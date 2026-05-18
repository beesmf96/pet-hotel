<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Booking;
use App\Models\PetHotel;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(string $slug): Response
    {
        $hotel = PetHotel::where('slug', $slug)->firstOrFail();

        $reviews = $hotel->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate(10)
            ->through(fn (Review $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'user_name' => $r->user->name,
                'created_at' => $r->created_at->toDateString(),
            ]);

        $count = $hotel->reviews()->count();
        $avg = $count > 0 ? round($hotel->reviews()->avg('rating'), 1) : null;

        return Inertia::render('Hotels/ReviewsPage', [
            'hotel' => ['name' => $hotel->name, 'slug' => $hotel->slug],
            'reviews' => $reviews,
            'average_rating' => $avg,
            'reviews_count' => $count,
        ]);
    }

    public function store(StoreReviewRequest $request, string $slug): RedirectResponse
    {
        $hotel = PetHotel::where('slug', $slug)->firstOrFail();

        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->where('hotel_id', $hotel->id)
            ->where('status', 'completed')
            ->doesntHave('review')
            ->firstOrFail();

        Review::create([
            'user_id' => $request->user()->id,
            'hotel_id' => $hotel->id,
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_visible' => true,
        ]);

        return back()->with('success', 'Your review has been submitted.');
    }
}
