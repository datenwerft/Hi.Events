<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_seating_settings', function (Blueprint $table) {
            $table->jsonb('table_types')->default(DB::raw("'[]'::jsonb"));
        });

        DB::table('event_seating_settings')
            ->where('table_count', '>', 0)
            ->where('seats_per_table', '>', 0)
            ->orderBy('id')
            ->eachById(function (object $settings): void {
                DB::table('event_seating_settings')
                    ->where('id', $settings->id)
                    ->update([
                        'table_types' => json_encode([[
                            'id' => 'default',
                            'name' => 'Table type 1',
                            'shape' => 'round',
                            'table_count' => (int) $settings->table_count,
                            'seats_per_table' => (int) $settings->seats_per_table,
                        ]], JSON_THROW_ON_ERROR),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('event_seating_settings', function (Blueprint $table) {
            $table->dropColumn('table_types');
        });
    }
};
