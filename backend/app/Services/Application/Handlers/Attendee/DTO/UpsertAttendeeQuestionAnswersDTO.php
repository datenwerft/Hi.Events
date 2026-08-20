<?php

namespace HiEvents\Services\Application\Handlers\Attendee\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;

class UpsertAttendeeQuestionAnswersDTO extends BaseDataObject
{
    public function __construct(
        public readonly int $event_id,
        public readonly int $attendee_id,
        public readonly array $question_answers,
    ) {}
}
