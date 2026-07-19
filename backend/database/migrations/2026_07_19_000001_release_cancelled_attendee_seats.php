<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attendees')
            ->where('status', 'CANCELLED')
            ->where(function ($query) {
                $query->whereNotNull('table_number')
                    ->orWhereNotNull('seat_number');
            })
            ->update([
                'table_number' => null,
                'seat_number' => null,
            ]);
    }

    public function down(): void
    {
        // Cancelled seating assignments cannot be reconstructed safely.
    }
};
