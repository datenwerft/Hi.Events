<?php

namespace HiEvents\Http\Actions\EventSeating;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\EventSeating\AssignEventSeatRequest;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Services\Domain\Attendee\SeatingAssignmentValidationService;
use HiEvents\Services\Domain\EventSeating\EventSeatingDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AssignEventSeatAction extends BaseAction
{
    public function __construct(
        private readonly AttendeeRepositoryInterface $attendeeRepository,
        private readonly SeatingAssignmentValidationService $seatingAssignmentValidator,
        private readonly EventSeatingDataService $seatingDataService,
    ) {
    }

    public function __invoke(AssignEventSeatRequest $request, int $eventId, int $attendeeId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $attendee = $this->attendeeRepository->findFirstWhere([
            'id' => $attendeeId,
            'event_id' => $eventId,
        ]);
        if (!$attendee) {
            throw ValidationException::withMessages([
                'attendee_id' => __('The selected attendee does not belong to this event.'),
            ]);
        }

        $tableNumber = $request->filled('table_number') ? (int)$request->input('table_number') : null;
        $seatNumber = $request->filled('seat_number') ? (int)$request->input('seat_number') : null;
        $this->seatingAssignmentValidator->validate($eventId, $tableNumber, $seatNumber, $attendeeId);

        $this->attendeeRepository->updateWhere([
            'table_number' => $tableNumber,
            'seat_number' => $seatNumber,
        ], [
            'id' => $attendeeId,
            'event_id' => $eventId,
        ]);

        return $this->jsonResponse(
            $this->seatingDataService->get($eventId),
            wrapInData: true,
        );
    }
}
