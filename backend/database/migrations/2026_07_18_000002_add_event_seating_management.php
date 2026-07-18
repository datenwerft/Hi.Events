<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_seating_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('event_id')->unique();
            $table->unsignedInteger('table_count')->default(0);
            $table->unsignedInteger('seats_per_table')->default(0);
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });

        Schema::table('attendees', function (Blueprint $table) {
            $table->unsignedInteger('table_number')->nullable();
            $table->unsignedInteger('seat_number')->nullable();
        });

        DB::statement(
            'CREATE UNIQUE INDEX attendees_event_table_seat_unique '
            . 'ON attendees (event_id, table_number, seat_number) '
            . 'WHERE deleted_at IS NULL AND table_number IS NOT NULL AND seat_number IS NOT NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS attendees_event_table_seat_unique');

        Schema::table('attendees', function (Blueprint $table) {
            $table->dropColumn(['table_number', 'seat_number']);
        });

        Schema::dropIfExists('event_seating_settings');
    }
};
