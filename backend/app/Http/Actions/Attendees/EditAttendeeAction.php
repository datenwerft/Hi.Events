<?php

namespace HiEvents\Http\Actions\Attendees;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\NoTicketsAvailableException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\Attendee\EditAttendeeRequest;
use HiEvents\Resources\Attendee\AttendeeResource;
use HiEvents\Services\Application\Handlers\Attendee\DTO\EditAttendeeDTO;
use HiEvents\Services\Application\Handlers\Attendee\EditAttendeeHandler;
use HiEvents\Services\Domain\Attendee\SeatingAssignmentValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditAttendeeAction extends BaseAction
{
    public function __construct(
        private readonly EditAttendeeHandler $handler,
        private readonly SeatingAssignmentValidationService $seatingAssignmentValidator,
    )
    {
    }

    /**
     * @throws ValidationException
     * @throws Throwable
     */
    public function __invoke(EditAttendeeRequest $request, int $eventId, int $attendeeId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $tableNumber = $request->filled('table_number') ? (int)$request->input('table_number') : null;
        $seatNumber = $request->filled('seat_number') ? (int)$request->input('seat_number') : null;
        $this->seatingAssignmentValidator->validate($eventId, $tableNumber, $seatNumber, $attendeeId);

        try {
            $updatedAttendee = $this->handler->handle(EditAttendeeDTO::fromArray([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'product_id' => $request->input('product_id'),
                'product_price_id' => $request->input('product_price_id'),
                'event_id' => $eventId,
                'attendee_id' => $attendeeId,
                'notes' => $request->input('notes'),
                'printed_ticket_number' => $request->filled('printed_ticket_number')
                    ? $request->input('printed_ticket_number')
                    : null,
                'table_number' => $tableNumber,
                'seat_number' => $seatNumber,
            ]));
        } catch (NoTicketsAvailableException $exception) {
            throw ValidationException::withMessages([
                'product_id' => $exception->getMessage(),
            ]);
        }

        return $this->resourceResponse(
            resource: AttendeeResource::class,
            data: $updatedAttendee,
        );
    }
}
