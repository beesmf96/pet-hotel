<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Availability used to be a stored "spots left" counter per date, adjusted
     * on confirm and cancel. It is now worked out from capacity minus bookings,
     * so each hotel gets a normal capacity and a date row becomes an override.
     *
     * An existing row's new capacity is its old spots left plus the bookings
     * that had already taken a spot that night, so the spots customers see do
     * not change.
     */
    public function up(): void
    {
        Schema::table('pet_hotels', function (Blueprint $table) {
            $table->unsignedSmallInteger('capacity')->default(10);
        });

        Schema::table('hotel_availabilities', function (Blueprint $table) {
            $table->renameColumn('available_spots', 'capacity');
        });

        Schema::table('hotel_availabilities', function (Blueprint $table) {
            $table->unsignedSmallInteger('capacity')->nullable()->default(null)->change();
        });

        $this->shiftByBookedNights(1);
    }

    public function down(): void
    {
        $this->shiftByBookedNights(-1);

        DB::table('hotel_availabilities')
            ->whereNull('capacity')
            ->update(['capacity' => DB::raw(
                '(SELECT capacity FROM pet_hotels WHERE pet_hotels.id = hotel_availabilities.hotel_id)'
            )]);

        Schema::table('hotel_availabilities', function (Blueprint $table) {
            $table->unsignedSmallInteger('capacity')->nullable(false)->default(10)->change();
        });

        Schema::table('hotel_availabilities', function (Blueprint $table) {
            $table->renameColumn('capacity', 'available_spots');
        });

        Schema::table('pet_hotels', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });
    }

    /**
     * Confirmed and completed bookings were the ones holding a spot under the
     * old counter (see the removed Booking::adjustAvailability()).
     */
    private function shiftByBookedNights(int $direction): void
    {
        DB::table('bookings')
            ->whereIn('status', ['confirmed', 'completed'])
            ->orderBy('id')
            ->each(function (object $booking) use ($direction) {
                $night = Carbon::parse($booking->check_in)->startOfDay();
                $checkOut = Carbon::parse($booking->check_out)->startOfDay();

                while ($night->lt($checkOut)) {
                    $row = DB::table('hotel_availabilities')
                        ->where('hotel_id', $booking->hotel_id)
                        ->whereDate('date', $night->toDateString())
                        ->whereNotNull('capacity');

                    $direction > 0 ? $row->increment('capacity') : $row->decrement('capacity');

                    $night->addDay();
                }
            });
    }
};
