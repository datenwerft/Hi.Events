<?php

namespace HiEvents\Services\Domain\Attendee;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeatingAssignmentValidationService
{
    /**
     * @throws ValidationException
     */
    public function validate(
        int $eventId,
        ?int $tableNumber,
        ?int $seatNumber,
        ?int $excludeAttendeeId = null,
    ): void {
        if ($tableNumber === null && $seatNumber === null) {
            return;
        }

        if ($tableNumber === null || $seatNumber === null) {
            throw ValidationException::withMessages([
                'table_number' => __('Both table and seat number are required for a seating assignment.'),
                'seat_number' => __('Both table and seat number are required for a seating assignment.'),
            ]);
        }

        $settings = DB::table('event_seating_settings')->where('event_id', $eventId)->first();

        if (!$settings || $settings->table_count === 0 || $settings->seats_per_table === 0) {
            throw ValidationException::withMessages([
                'table_number' => __('Configure event seating before assigning attendees.'),
            ]);
        }

        if ($tableNumber > $settings->table_count) {
            throw ValidationException::withMessages([
                'table_number' => __('The selected table does not exist.'),
            ]);
        }

        if ($seatNumber > $settings->seats_per_table) {
            throw ValidationException::withMessages([
                'seat_number' => __('The selected seat does not exist at this table.'),
            ]);
        }

        $occupied = DB::table('attendees')
            ->where('event_id', $eventId)
            ->where('status', '!=', 'CANCELLED')
            ->where('table_number', $tableNumber)
            ->where('seat_number', $seatNumber)
            ->whereNull('deleted_at')
            ->when($excludeAttendeeId, fn($query) => $query->where('id', '!=', $excludeAttendeeId))
            ->exists();

        if ($occupied) {
            throw ValidationException::withMessages([
                'seat_number' => __('This seat is already assigned to another attendee.'),
            ]);
        }
    }
}
