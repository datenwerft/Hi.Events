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
            $table->unsignedBigInteger('blueprint_image_id')->nullable();
            $table->jsonb('table_positions')->default(DB::raw("'[]'::jsonb"));
        });
    }

    public function down(): void
    {
        Schema::table('event_seating_settings', function (Blueprint $table) {
            $table->dropColumn(['blueprint_image_id', 'table_positions']);
        });
    }
};
