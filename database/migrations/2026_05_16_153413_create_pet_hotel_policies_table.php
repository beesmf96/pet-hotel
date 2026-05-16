<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pet_hotel_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('pet_hotels')->cascadeOnDelete();
            $table->time('check_in_time');
            $table->time('check_out_time');
            $table->text('cancellation_policy')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_hotel_policies');
    }
};
