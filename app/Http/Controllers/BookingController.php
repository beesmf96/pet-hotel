<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Jobs\SendBookingRequestNotification;
use App\Models\Booking;
use App\Models\HotelAvailability;
use App\Models\PetHotel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function create(string $slug): Response
    {
        $hotel = PetHotel::where('slug', $slug)->with('pricing')->firstOrFail();
        $pets = auth()->user()->pets()->get(['id', 'name', 'species']);

        return Inertia::render('Bookings/BookingFormPage', [
            'hotel' => $hotel,
            'pets' => $pets,
        ]);
    }

    public function store(StoreBookingRequest $request, string $slug): RedirectResponse
    {
        $booking = DB::transaction(function () use ($request, $slug): Booking {
            $hotel = PetHotel::where('slug', $slug)->with('pricing')->firstOrFail();
            $pet = $request->user()->pets()->findOrFail($request->pet_id);

            $checkIn = $request->date('check_in');
            $checkOut = $request->date('check_out');
            $nights = $checkIn->diffInDays($checkOut);

            HotelAvailability::where('hotel_id', $hotel->id)
                ->whereBetween('date', [$checkIn->toDateString(), $checkOut->copy()->subDay()->toDateString()])
                ->lockForUpdate()
                ->get();

            $pricing = $hotel->pricing->firstWhere('pet_type', $pet->species);
            $pricePerNight = $pricing ? (float) $pricing->price_per_night : 0;
            $totalPrice = $pricePerNight * $nights;

            return Booking::create([
                'user_id' => $request->user()->id,
                'hotel_id' => $hotel->id,
                'pet_id' => $pet->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => 'pending',
                'notes' => $request->notes,
                'total_price' => $totalPrice,
            ]);
        });

        SendBookingRequestNotification::dispatch($booking);

        return redirect()->route('bookings.confirmation', $booking);
    }

    public function confirmation(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        return Inertia::render('Bookings/BookingConfirmationPage', [
            'booking' => $booking->load(['hotel', 'pet']),
        ]);
    }

    public function index(): Response
    {
        $bookings = auth()->user()->bookings()
            ->with(['hotel', 'pet'])
            ->latest()
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'hotel' => ['name' => $b->hotel->name, 'slug' => $b->hotel->slug],
                'pet' => ['name' => $b->pet->name],
                'check_in' => $b->check_in->toDateString(),
                'check_out' => $b->check_out->toDateString(),
                'status' => $b->status,
                'total_price' => $b->total_price,
            ]);

        return Inertia::render('Bookings/MyBookingsPage', [
            'bookings' => $bookings,
        ]);
    }

    public function show(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load(['hotel', 'pet']);

        return Inertia::render('Bookings/BookingDetailPage', [
            'booking' => [
                'id' => $booking->id,
                'hotel' => [
                    'name' => $booking->hotel->name,
                    'slug' => $booking->hotel->slug,
                    'address' => $booking->hotel->address,
                    'city' => $booking->hotel->city,
                ],
                'pet' => [
                    'name' => $booking->pet->name,
                    'species' => $booking->pet->species,
                ],
                'check_in' => $booking->check_in->toDateString(),
                'check_out' => $booking->check_out->toDateString(),
                'status' => $booking->status,
                'notes' => $booking->notes,
                'total_price' => $booking->total_price,
                'created_at' => $booking->created_at->toDateTimeString(),
            ],
        ]);
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'Booking cancelled.');
    }
}
