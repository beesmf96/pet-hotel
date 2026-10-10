<?php

namespace App\Http\Controllers;

use App\Models\PetHotel;
use App\Support\Availability;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HotelAvailabilityController extends Controller
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $hotel = PetHotel::where('slug', $slug)->firstOrFail();

        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $days = [];
        foreach (Availability::nights($hotel, $start, $end) as $key => $night) {
            $days[$key] = [
                'date' => $key,
                'status' => Availability::status($night),
                'available_spots' => $night['spots_left'],
                'capacity' => $night['capacity'],
            ];
        }

        return response()->json([
            'hotel_id' => $hotel->id,
            'month' => $month,
            'days' => $days,
        ]);
    }
}
