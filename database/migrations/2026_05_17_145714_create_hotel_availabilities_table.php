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
        Schema::create('hotel_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('pet_hotels')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('available_spots')->default(10);
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();

            $table->unique(['hotel_id', 'date']);
            $table->index(['hotel_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_availabilities');
    }
};
