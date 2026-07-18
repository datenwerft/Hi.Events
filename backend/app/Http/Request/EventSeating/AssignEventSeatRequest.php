<?php

namespace HiEvents\Http\Request\EventSeating;

use HiEvents\Http\Request\BaseRequest;

class AssignEventSeatRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'table_number' => ['nullable', 'integer', 'min:1'],
            'seat_number' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
