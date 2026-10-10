<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Jobs\SendBookingRequestNotification;
use App\Models\Booking;
use App\Models\PetHotel;
use App\Support\Availability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function create(string $slug): Response
    {
        $hotel = PetHotel::where('slug', $slug)->with(['pricing', 'policy'])->firstOrFail();
        $pets = auth()->user()->pets()->get(['id', 'name', 'species']);

        return Inertia::render('Bookings/BookingFormPage', [
            'hotel' => $hotel,
            'pets' => $pets,
            'times' => $hotel->policy?->stayTimes(),
        ]);
    }

    public function store(StoreBookingRequest $request, string $slug): RedirectResponse
    {
        $hotel = PetHotel::where('slug', $slug)->with('pricing')->firstOrFail();
        $pet = $request->user()->pets()->findOrFail($request->pet_id);

        $checkIn = $request->date('check_in');
        $checkOut = $request->date('check_out');

        // A request does not hold a spot (confirming does, see Booking::confirm()),
        // but there is no point sending one for nights the hotel cannot take.
        if (! Availability::fits($hotel, $checkIn, $checkOut)) {
            throw ValidationException::withMessages([
                'check_in' => 'The hotel is full or closed on at least one night of this stay. Please pick other dates.',
            ]);
        }

        $pricing = $hotel->pricing->firstWhere('pet_type', $pet->species);
        $pricePerNight = $pricing ? (float) $pricing->price_per_night : 0;

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'hotel_id' => $hotel->id,
            'pet_id' => $pet->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => 'pending',
            'notes' => $request->notes,
            'total_price' => $pricePerNight * $checkIn->diffInDays($checkOut),
        ]);

        SendBookingRequestNotification::dispatch($booking);

        return redirect()->route('bookings.confirmation', $booking);
    }

    public function confirmation(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load(['hotel.policy', 'pet']);

        return Inertia::render('Bookings/BookingConfirmationPage', [
            'booking' => $booking,
            'times' => $booking->hotel->policy?->stayTimes(),
        ]);
    }

    public function index(): Response
    {
        $bookings = auth()->user()->bookings()
            ->with(['hotel', 'pet', 'review'])
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
                'has_review' => $b->review !== null,
            ]);

        return Inertia::render('Bookings/MyBookingsPage', [
            'bookings' => $bookings,
        ]);
    }

    public function show(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load(['hotel.policy', 'pet']);

        return Inertia::render('Bookings/BookingDetailPage', [
            'times' => $booking->hotel->policy?->stayTimes(),
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
