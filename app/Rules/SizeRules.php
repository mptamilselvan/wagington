<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class SizeRules
{
    public static function rules($editId = null)
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sizes', 'name')
                    ->ignore($editId) // ignore current record if editing
                    ->whereNull('deleted_at'), // only check non-deleted records
            ],
        ];
    }

    public static function messages()
    {
        return [
            'name.required' => 'Name of size field is required.',
        ];
    }
}