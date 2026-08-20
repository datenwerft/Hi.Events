<?php

namespace HiEvents\Services\Application\Handlers\Attendee;

use HiEvents\DomainObjects\Generated\AttendeeDomainObjectAbstract;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Services\Application\Handlers\Attendee\DTO\UpsertAttendeeQuestionAnswersDTO;
use HiEvents\Services\Domain\Question\AttendeeQuestionAnswerService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;

class UpsertAttendeeQuestionAnswersHandler
{
    public function __construct(
        private readonly AttendeeRepositoryInterface $attendeeRepository,
        private readonly AttendeeQuestionAnswerService $attendeeQuestionAnswerService,
        private readonly DatabaseManager $databaseManager,
    ) {}

    public function handle(UpsertAttendeeQuestionAnswersDTO $dto): void
    {
        $this->databaseManager->transaction(function () use ($dto) {
            $attendee = $this->attendeeRepository->findFirstWhere([
                AttendeeDomainObjectAbstract::ID => $dto->attendee_id,
                AttendeeDomainObjectAbstract::EVENT_ID => $dto->event_id,
            ]);

            if ($attendee === null) {
                throw ValidationException::withMessages([
                    'attendee_id' => __('Attendee ID is not valid'),
                ]);
            }

            $this->attendeeQuestionAnswerService->upsertProductAnswers(
                attendee: $attendee,
                eventId: $dto->event_id,
                answers: $dto->question_answers,
            );
        });
    }
}
