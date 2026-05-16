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
        Schema::create('pet_hotel_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('pet_hotels')->cascadeOnDelete();
            $table->enum('pet_type', ['dog', 'cat', 'rabbit', 'bird', 'other']);
            $table->decimal('price_per_night', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_hotel_pricing');
    }
};
