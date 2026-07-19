<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Attendees;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Services\Application\Handlers\Attendee\DeleteAttendeeHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DeleteAttendeeAction extends BaseAction
{
    public function __construct(
        private readonly DeleteAttendeeHandler $deleteAttendeeHandler,
    ) {}

    public function __invoke(Request $request, int $eventId, int $attendeeId): JsonResponse|Response
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        try {
            $this->deleteAttendeeHandler->handle($attendeeId, $eventId);
        } catch (ResourceConflictException $exception) {
            return $this->errorResponse($exception->getMessage(), HttpResponse::HTTP_CONFLICT);
        }

        return $this->deletedResponse();
    }
}
