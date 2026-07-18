<?php

namespace HiEvents\Http\Request\EventSeating;

use HiEvents\Http\Request\BaseRequest;

class UpdateEventSeatingLayoutRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'blueprint_image_id' => ['nullable', 'integer'],
            'table_positions' => ['required', 'array', 'max:500'],
            'table_positions.*.table_number' => ['required', 'integer', 'min:1', 'distinct'],
            'table_positions.*.x' => ['required', 'numeric', 'min:0', 'max:100'],
            'table_positions.*.y' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
