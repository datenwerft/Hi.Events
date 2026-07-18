<?php

namespace HiEvents\Http\Actions\EventSeating;

use HiEvents\DomainObjects\Enums\ImageType;
use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\EventSeating\UpdateEventSeatingLayoutRequest;
use HiEvents\Services\Domain\EventSeating\EventSeatingDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateEventSeatingLayoutAction extends BaseAction
{
    public function __construct(
        private readonly EventSeatingDataService $seatingDataService,
    ) {
    }

    public function __invoke(UpdateEventSeatingLayoutRequest $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $settings = DB::table('event_seating_settings')->where('event_id', $eventId)->first();
        if (!$settings) {
            throw ValidationException::withMessages([
                'table_positions' => __('Configure event seating before editing the room plan.'),
            ]);
        }

        $positions = $request->validated('table_positions');
        foreach ($positions as $position) {
            if ((int)$position['table_number'] > (int)$settings->table_count) {
                throw ValidationException::withMessages([
                    'table_positions' => __('A table position references a table that does not exist.'),
                ]);
            }
        }

        $blueprintImageId = $request->validated('blueprint_image_id');
        if ($blueprintImageId !== null) {
            $validImage = DB::table('images')
                ->where('id', $blueprintImageId)
                ->where('entity_id', $eventId)
                ->where('type', ImageType::ROOM_BLUEPRINT->name)
                ->whereNull('deleted_at')
                ->exists();

            if (!$validImage) {
                throw ValidationException::withMessages([
                    'blueprint_image_id' => __('The selected room blueprint is invalid.'),
                ]);
            }
        }

        DB::table('event_seating_settings')
            ->where('event_id', $eventId)
            ->update([
                'blueprint_image_id' => $blueprintImageId,
                'table_positions' => json_encode($positions, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

        return $this->jsonResponse(
            $this->seatingDataService->get($eventId),
            wrapInData: true,
        );
    }
}
