<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class BookingStatusSettingRules
{
    public static function rules($editId = null)
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('booking_status_settings', 'name')
                    ->ignore($editId) // ignore current record if editing
                    ->whereNull('deleted_at'), // only check non-deleted records
            ],
        ];
    }

    public static function messages()
    {
        return [
            'name.required' => 'Name of booking status field is required.',
        ];
    }
}