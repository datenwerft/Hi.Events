<?php

namespace HiEvents\Http\Request\EventSeating;

use HiEvents\Http\Request\BaseRequest;
use Illuminate\Validation\Validator;

class UpdateEventSeatingRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'table_types' => ['present', 'array', 'max:50'],
            'table_types.*.id' => ['required', 'string', 'max:64', 'distinct'],
            'table_types.*.name' => ['required', 'string', 'max:100'],
            'table_types.*.shape' => ['required', 'string', 'in:round,square,rectangle'],
            'table_types.*.table_count' => ['required', 'integer', 'min:1', 'max:500'],
            'table_types.*.seats_per_table' => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            static function (Validator $validator): void {
                $data = $validator->getData();
                if (! isset($data['table_types']) || ! is_array($data['table_types'])) {
                    return;
                }

                $tableCount = array_sum(array_map(
                    static fn (mixed $type): int => is_array($type) ? (int) ($type['table_count'] ?? 0) : 0,
                    $data['table_types'],
                ));
                if ($tableCount > 500) {
                    $validator->errors()->add(
                        'table_types',
                        __('The seating configuration cannot contain more than 500 tables.')
                    );
                }
            },
        ];
    }
}
