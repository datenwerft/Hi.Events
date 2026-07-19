<?php

namespace HiEvents\Http\Actions\EventSeating;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\EventSeating\UpdateEventSeatingRequest;
use HiEvents\Services\Domain\EventSeating\EventSeatingDataService;
use HiEvents\Services\Domain\EventSeating\EventSeatingTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateEventSeatingAction extends BaseAction
{
    public function __construct(
        private readonly EventSeatingDataService $seatingDataService,
        private readonly EventSeatingTableService $tableService,
    ) {}

    public function __invoke(UpdateEventSeatingRequest $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $tableTypes = $this->tableService->normalize($request->validated('table_types'));
        $tables = $this->tableService->expand($tableTypes);
        $tableCount = count($tables);
        $seatsPerTable = $tables === [] ? 0 : max(array_column($tables, 'seats_per_table'));
        $seatsByTable = array_column($tables, 'seats_per_table', 'table_number');

        $assignedSeats = DB::table('attendees')
            ->select(['table_number', 'seat_number'])
            ->where('event_id', $eventId)
            ->whereNull('deleted_at')
            ->whereNotNull('table_number')
            ->whereNotNull('seat_number')
            ->get();

        $outsideNewLayout = $assignedSeats->contains(static function (object $assignment) use ($seatsByTable): bool {
            $tableNumber = (int) $assignment->table_number;

            return ! isset($seatsByTable[$tableNumber])
                || (int) $assignment->seat_number > $seatsByTable[$tableNumber];
        });

        if ($outsideNewLayout) {
            throw ValidationException::withMessages([
                'table_types' => __('Move attendees from seats outside the new layout before changing or disabling seating.'),
            ]);
        }

        $settingValues = [
            'table_count' => $tableCount,
            'seats_per_table' => $seatsPerTable,
            'table_types' => json_encode($tableTypes, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ];

        if (! DB::table('event_seating_settings')->where('event_id', $eventId)->exists()) {
            $settingValues['created_at'] = now();
        }

        DB::table('event_seating_settings')->updateOrInsert(
            ['event_id' => $eventId],
            $settingValues,
        );

        return $this->jsonResponse(
            $this->seatingDataService->get($eventId),
            wrapInData: true,
        );
    }
}
