<?php

namespace HiEvents\Services\Domain\EventSeating;

use HiEvents\Helper\Url;
use Illuminate\Support\Facades\DB;

class EventSeatingDataService
{
    private readonly EventSeatingTableService $tableService;

    public function __construct(?EventSeatingTableService $tableService = null)
    {
        $this->tableService = $tableService ?? new EventSeatingTableService;
    }

    public function get(int $eventId): array
    {
        $settings = DB::table('event_seating_settings')->where('event_id', $eventId)->first();
        $tableCount = (int) ($settings?->table_count ?? 0);
        $seatsPerTable = (int) ($settings?->seats_per_table ?? 0);
        $tableTypes = $this->tableService->normalize(
            $settings?->table_types ?? [],
            $tableCount,
            $seatsPerTable,
        );
        $tables = $this->tableService->expand($tableTypes);
        $tablePositions = $settings?->table_positions ?? [];
        if (is_string($tablePositions)) {
            $tablePositions = json_decode($tablePositions, true) ?: [];
        }
        $blueprint = null;
        if ($settings?->blueprint_image_id) {
            $image = DB::table('images')
                ->where('id', $settings->blueprint_image_id)
                ->whereNull('deleted_at')
                ->first();
            if ($image) {
                $blueprint = [
                    'id' => (int) $image->id,
                    'url' => Url::getCdnUrl($image->path),
                    'width' => $image->width ? (int) $image->width : null,
                    'height' => $image->height ? (int) $image->height : null,
                ];
            }
        }
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
            ->where('status', '!=', 'CANCELLED')
            ->whereNull('deleted_at')
            ->whereNotNull('table_number')
            ->whereNotNull('seat_number')
            ->orderBy('table_number')
            ->orderBy('seat_number')
            ->get()
            ->map(static fn (object $attendee): array => [
                'attendee_id' => (int) $attendee->id,
                'first_name' => $attendee->first_name,
                'last_name' => $attendee->last_name,
                'email' => $attendee->email,
                'table_number' => (int) $attendee->table_number,
                'seat_number' => (int) $attendee->seat_number,
            ])
            ->all();
        $availableAttendees = DB::table('attendees')
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->where('event_id', $eventId)
            ->where('status', 'ACTIVE')
            ->whereNull('deleted_at')
            ->whereNull('table_number')
            ->whereNull('seat_number')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(static fn (object $attendee): array => [
                'attendee_id' => (int) $attendee->id,
                'first_name' => $attendee->first_name,
                'last_name' => $attendee->last_name,
                'email' => $attendee->email,
            ])
            ->all();

        return [
            'table_count' => count($tables),
            'seats_per_table' => $tables === [] ? 0 : max(array_column($tables, 'seats_per_table')),
            'table_types' => $tableTypes,
            'tables' => $tables,
            'total_seats' => array_sum(array_column($tables, 'seats_per_table')),
            'assigned_seats' => count($assignments),
            'assignments' => $assignments,
            'available_attendees' => $availableAttendees,
            'blueprint' => $blueprint,
            'table_positions' => $tablePositions,
        ];
    }
}
