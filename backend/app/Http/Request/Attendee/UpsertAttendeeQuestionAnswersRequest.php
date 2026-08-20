<?php

namespace HiEvents\Http\Request\Attendee;

use HiEvents\Http\Request\BaseRequest;

class UpsertAttendeeQuestionAnswersRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'question_answers' => ['required', 'array'],
            'question_answers.*.question_id' => ['required', 'integer', 'distinct'],
            'question_answers.*.answer' => ['present', 'nullable'],
        ];
    }
}
