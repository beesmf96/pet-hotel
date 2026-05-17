<?php

namespace App\Http\Controllers;

use App\Models\HotelAvailability;
use App\Models\PetHotel;
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

        $rows = HotelAvailability::where('hotel_id', $hotel->id)
            ->whereBetween('date', [$start, $end])
            ->get(['date', 'available_spots', 'is_blocked'])
            ->keyBy(fn ($row) => $row->date->format('Y-m-d'));

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $row = $rows->get($key);

            if ($row) {
                $status = $row->is_blocked ? 'blocked'
                    : ($row->available_spots > 0 ? 'available' : 'full');
            } else {
                $status = 'available';
            }

            $days[$key] = [
                'date' => $key,
                'status' => $status,
                'available_spots' => $row?->available_spots ?? null,
            ];
            $cursor->addDay();
        }

        return response()->json([
            'hotel_id' => $hotel->id,
            'month' => $month,
            'days' => $days,
        ]);
    }
}
