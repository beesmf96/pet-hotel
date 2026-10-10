<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The owner panel's notification bell finds its rows with
     * where('data->format', 'filament'). PostgreSQL only allows JSON
     * operators on a json column, so the text column Laravel's notifications
     * table ships with would make that query fail. SQLite reads JSON out of
     * text, so it needs no change.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE json USING data::json');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text');
        }
    }
};
