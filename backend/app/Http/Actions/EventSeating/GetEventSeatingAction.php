<?php

namespace HiEvents\Http\Actions\EventSeating;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Services\Domain\EventSeating\EventSeatingDataService;
use Illuminate\Http\JsonResponse;

class GetEventSeatingAction extends BaseAction
{
    public function __construct(
        private readonly EventSeatingDataService $seatingDataService,
    ) {
    }

    public function __invoke(int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        return $this->jsonResponse(
            $this->seatingDataService->get($eventId),
            wrapInData: true,
        );
    }
}
