<?php

namespace HiEvents\Http\Request\Attendee;

use HiEvents\Http\Request\BaseRequest;
use HiEvents\Locale;
use HiEvents\Validators\Rules\RulesHelper;
use Illuminate\Validation\Rule;

class CreateAttendeeRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['int', 'required'],
            'product_price_id' => ['int', 'nullable', 'required'],
            'email' => ['required', 'email'],
            'first_name' => ['string', 'required', 'max:40'],
            'last_name' => ['string', 'max:40'],
            'amount_paid' => ['required', ...RulesHelper::MONEY],
            'send_confirmation_email' => ['required', 'boolean'],
            'taxes_and_fees' => ['array'],
            'taxes_and_fees.*.tax_or_fee_id' => ['required', 'int'],
            'taxes_and_fees.*.amount' => ['required', ...RulesHelper::MONEY],
            'locale' => ['required', Rule::in(Locale::getSupportedLocales())],
            'printed_ticket_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('attendees')->where('event_id', $this->route('event_id')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'printed_ticket_number.max' => __('Printed ticket number must be less than 100 characters'),
            'printed_ticket_number.unique' => __('This printed ticket number is already assigned to an attendee'),
        ];
    }
}
