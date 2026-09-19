<?php

use App\Enums\PetType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Species used to be free text, so "Dog" or "Golden Retriever" never
     * matched a hotel's per-type pricing and booked at RM 0. Fold existing
     * values onto the fixed list: case-insensitive matches keep their type,
     * anything else becomes "other".
     */
    public function up(): void
    {
        DB::table('pets')->update(['species' => DB::raw('LOWER(species)')]);

        DB::table('pets')
            ->whereNotIn('species', array_column(PetType::cases(), 'value'))
            ->update(['species' => PetType::Other->value]);
    }

    public function down(): void
    {
        // The original free-text values are gone; nothing to restore.
    }
};
