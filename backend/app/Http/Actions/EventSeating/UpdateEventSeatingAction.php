<?php

namespace HiEvents\Http\Actions\EventSeating;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\EventSeating\UpdateEventSeatingRequest;
use HiEvents\Services\Domain\EventSeating\EventSeatingDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateEventSeatingAction extends BaseAction
{
    public function __construct(
        private readonly EventSeatingDataService $seatingDataService,
    ) {
    }

    public function __invoke(UpdateEventSeatingRequest $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $tableCount = (int)$request->validated('table_count');
        $seatsPerTable = (int)$request->validated('seats_per_table');

        $outsideNewLayout = DB::table('attendees')
            ->where('event_id', $eventId)
            ->whereNull('deleted_at')
            ->whereNotNull('table_number')
            ->whereNotNull('seat_number')
            ->where(function ($query) use ($tableCount, $seatsPerTable) {
                $query->where('table_number', '>', $tableCount)
                    ->orWhere('seat_number', '>', $seatsPerTable);
            })
            ->exists();

        if ($outsideNewLayout) {
            throw ValidationException::withMessages([
                'table_count' => __('Move attendees from seats outside the new layout before reducing or disabling seating.'),
            ]);
        }

        $settingValues = [
            'table_count' => $tableCount,
            'seats_per_table' => $seatsPerTable,
            'updated_at' => now(),
        ];

        if (!DB::table('event_seating_settings')->where('event_id', $eventId)->exists()) {
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
