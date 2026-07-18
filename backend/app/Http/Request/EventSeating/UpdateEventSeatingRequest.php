<?php

namespace HiEvents\Http\Request\EventSeating;

use HiEvents\Http\Request\BaseRequest;
use Illuminate\Validation\Validator;

class UpdateEventSeatingRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'table_count' => ['required', 'integer', 'min:0', 'max:500'],
            'seats_per_table' => ['required', 'integer', 'min:0', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            static function (Validator $validator): void {
                $data = $validator->getData();
                if (!array_key_exists('table_count', $data) || !array_key_exists('seats_per_table', $data)) {
                    return;
                }

                $tableCount = (int)$data['table_count'];
                $seatsPerTable = (int)$data['seats_per_table'];

                if (($tableCount === 0) !== ($seatsPerTable === 0)) {
                    $validator->errors()->add(
                        'seats_per_table',
                        __('Tables and seats per table must either both be zero or both be greater than zero.')
                    );
                }
            },
        ];
    }
}
