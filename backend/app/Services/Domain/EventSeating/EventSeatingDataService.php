<?php

namespace HiEvents\Services\Domain\EventSeating;

use Illuminate\Support\Facades\DB;

class EventSeatingDataService
{
    public function get(int $eventId): array
    {
        $settings = DB::table('event_seating_settings')->where('event_id', $eventId)->first();
        $tableCount = (int)($settings?->table_count ?? 0);
        $seatsPerTable = (int)($settings?->seats_per_table ?? 0);
        $assignments = DB::table('attendees')
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'table_number',
                'seat_number',
            ])
            ->where('event_id', $eventId)
            ->whereNull('deleted_at')
            ->whereNotNull('table_number')
            ->whereNotNull('seat_number')
            ->orderBy('table_number')
            ->orderBy('seat_number')
            ->get()
            ->map(static fn(object $attendee): array => [
                'attendee_id' => (int)$attendee->id,
                'first_name' => $attendee->first_name,
                'last_name' => $attendee->last_name,
                'email' => $attendee->email,
                'table_number' => (int)$attendee->table_number,
                'seat_number' => (int)$attendee->seat_number,
            ])
            ->all();

        return [
            'table_count' => $tableCount,
            'seats_per_table' => $seatsPerTable,
            'total_seats' => $tableCount * $seatsPerTable,
            'assigned_seats' => count($assignments),
            'assignments' => $assignments,
        ];
    }
}
