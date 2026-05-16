<?php

namespace App\Http\Controllers;

use App\Models\PetHotel;
use Inertia\Inertia;
use Inertia\Response;

class HotelController extends Controller
{
    public function show(string $slug): Response
    {
        $hotel = PetHotel::where('slug', $slug)
            ->with(['facilities', 'photos', 'policy', 'pricing'])
            ->firstOrFail();

        return Inertia::render('Hotels/HotelProfilePage', [
            'hotel' => $hotel,
        ]);
    }
}
