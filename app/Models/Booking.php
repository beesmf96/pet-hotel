<?php

namespace App\Models;

use App\Enums\CancelledBy;
use App\Exceptions\BookingDoesNotFit;
use App\Jobs\NotifyOwnersOfGuestCancellation;
use App\Jobs\SendBookingCancelledNotification;
use App\Jobs\SendBookingConfirmationNotification;
use App\Support\Availability;
use App\Support\Dates;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Fillable(['user_id', 'hotel_id', 'pet_id', 'check_in', 'check_out', 'status', 'notes', 'total_price'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /** How long a hotel has to answer a request; see answerBy(). */
    public const ANSWER_WITHIN_HOURS = 48;

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'total_price' => 'decimal:2',
    ];

    /**
     * Confirm a pending request if the hotel still has room for every night.
     *
     * Pending requests do not hold a spot, so the check that matters happens
     * here. The hotel row is locked for the duration so two confirms cannot
     * both take the last spot, and the pet row so two hotels cannot both
     * confirm the same pet for the same night. Always hotel first, then pet.
     *
     * @throws BookingDoesNotFit
     */
    public function confirm(): void
    {
        DB::transaction(function () {
            $hotel = PetHotel::whereKey($this->hotel_id)->lockForUpdate()->firstOrFail();

            if (! Availability::fits($hotel, $this->check_in, $this->check_out)) {
                throw BookingDoesNotFit::noRoom();
            }

            Pet::whereKey($this->pet_id)->lockForUpdate()->firstOrFail();

            if (self::clashFor($this->pet_id, $this->check_in, $this->check_out, Availability::HOLDING_STATUSES, $this->id)) {
                throw BookingDoesNotFit::petAlreadyBooked();
            }

            $this->update(['status' => 'confirmed']);

            SendBookingConfirmationNotification::dispatch($this);
        });
    }

    /**
     * End a booking and tell the people who need to know. The guest always
     * hears, in words that fit who cancelled and whether the stay had been
     * confirmed; the hotel's owners hear only when the guest cancelled.
     */
    public function cancel(CancelledBy $by): void
    {
        $wasConfirmed = in_array($this->status, Availability::HOLDING_STATUSES, true);

        $this->update(['status' => 'cancelled']);

        SendBookingCancelledNotification::dispatch($this, $by, $wasConfirmed);

        if ($by === CancelledBy::Guest) {
            NotifyOwnersOfGuestCancellation::dispatch($this);
        }
    }

    /**
     * When the hotel must answer this request by, or it expires
     * (bookings:expire-pending): 48 hours after it was sent, or noon the day
     * before check-in if that is sooner, so the guest learns in time to look
     * elsewhere. A request sent after that noon has until the end of its
     * check-in day. Noon and day ends are local (app.display_timezone).
     */
    public function answerBy(): Carbon
    {
        $zone = config('app.display_timezone');
        $checkInDay = Carbon::parse($this->check_in->toDateString(), $zone);
        $requested = Carbon::parse($this->created_at);

        $deadline = $requested->copy()->addHours(self::ANSWER_WITHIN_HOURS)
            ->min($checkInDay->copy()->subDay()->setTime(12, 0));

        if ($deadline->lte($requested)) {
            $deadline = $checkInDay->copy()->endOfDay();
        }

        return $deadline->copy()->utc();
    }

    /** answerBy() in local time, as people read it: "Mon, 20 Oct 2026, 2:00 PM". */
    public function answerByText(): string
    {
        return $this->answerBy()->timezone(config('app.display_timezone'))->format(Dates::DAY_TIME);
    }

    /** "Sat, 25 Oct 2026, from 2:00 PM" when the hotel has set its times. */
    public function checkInText(): string
    {
        $time = $this->hotel->policy?->stayTimes()['check_in'];

        return $this->check_in->format(Dates::DAY).($time ? ', from '.$time : '');
    }

    /** "Tue, 28 Oct 2026, by 12:00 PM" when the hotel has set its times. */
    public function checkOutText(): string
    {
        $time = $this->hotel->policy?->stayTimes()['check_out'];

        return $this->check_out->format(Dates::DAY).($time ? ', by '.$time : '');
    }

    public function nights(): int
    {
        return (int) $this->check_in->diffInDays($this->check_out);
    }

    /** "1 night", "3 nights" */
    public function nightsText(): string
    {
        $nights = $this->nights();

        return $nights.' '.str('night')->plural($nights);
    }

    /**
     * The pet's earliest booking in one of $statuses that shares a night with
     * check-in to check-out. Back-to-back stays share no night: the check-out
     * day is not a night.
     *
     * @param  list<string>  $statuses
     */
    public static function clashFor(int $petId, CarbonInterface $checkIn, CarbonInterface $checkOut, array $statuses, ?int $except = null): ?self
    {
        $checkIn = Carbon::parse($checkIn)->startOfDay();
        $checkOut = Carbon::parse($checkOut)->startOfDay();

        // A wide window, then exact dates from the casts, as in Availability:
        // SQLite keeps whatever time part was written into a date column.
        return self::where('pet_id', $petId)
            ->whereIn('status', $statuses)
            ->when($except, fn ($query) => $query->whereKeyNot($except))
            ->where('check_in', '<', $checkOut->copy()->addDay())
            ->where('check_out', '>', $checkIn->copy()->subDay())
            ->orderBy('check_in')
            ->get()
            ->first(fn (self $booking) => $booking->check_in->lt($checkOut) && $booking->check_out->gt($checkIn));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(PetHotel::class, 'hotel_id');
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
