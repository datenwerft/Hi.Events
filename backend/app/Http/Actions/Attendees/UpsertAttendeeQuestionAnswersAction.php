<?php

namespace HiEvents\Http\Actions\Attendees;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\Attendee\UpsertAttendeeQuestionAnswersRequest;
use HiEvents\Services\Application\Handlers\Attendee\DTO\UpsertAttendeeQuestionAnswersDTO;
use HiEvents\Services\Application\Handlers\Attendee\UpsertAttendeeQuestionAnswersHandler;
use Illuminate\Http\Response;

class UpsertAttendeeQuestionAnswersAction extends BaseAction
{
    public function __construct(
        private readonly UpsertAttendeeQuestionAnswersHandler $handler,
    ) {}

    public function __invoke(
        UpsertAttendeeQuestionAnswersRequest $request,
        int $eventId,
        int $attendeeId,
    ): Response {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $this->handler->handle(UpsertAttendeeQuestionAnswersDTO::from([
            'event_id' => $eventId,
            'attendee_id' => $attendeeId,
            'question_answers' => $request->validated('question_answers'),
        ]));

        return $this->noContentResponse();
    }
}
