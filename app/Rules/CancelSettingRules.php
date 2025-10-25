<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class CancelSettingRules
{
    public static function rules($editId = null)
    {
        return [
            'before_6_hour_percentage'   => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
            'before_24_hour_percentage'   => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
            'before_72_hour_percentage'   => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
            'admin_cancel_percentage'   => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
        ];
    }

    public static function messages()
    {
        return [
            'before_6_hour_percentage.required' => 'Before 6 hour percentage is required.',
            'before_6_hour_percentage.integer' => 'Before 6 hour percentage must be an integer.',
            'before_6_hour_percentage.min' => 'Before 6 hour percentage must be greater than 0.',
            'before_6_hour_percentage.max' => 'Before 6 hour percentage must be less than 100.',
            'before_24_hour_percentage.required' => 'Before 24 hour percentage is required.',
            'before_24_hour_percentage.integer' => 'Before 24 hour percentage must be an integer.',
            'before_24_hour_percentage.min' => 'Before 24 hour percentage must be greater than 0.',
            'before_24_hour_percentage.max' => 'Before 24 hour percentage must be less than 100.',
            'before_72_hour_percentage.required' => 'Before 72 hour percentage is required.',
            'before_72_hour_percentage.integer' => 'Before 72 hour percentage must be an integer.',
            'before_72_hour_percentage.min' => 'Before 72 hour percentage must be greater than 0.',
            'before_72_hour_percentage.max' => 'Before 72 hour percentage must be less than 100.',
            'admin_cancel_percentage.required' => 'Admin cancel percentage is required.',
            'admin_cancel_percentage.integer' => 'Admin cancel percentage must be an integer.',
            'admin_cancel_percentage.min' => 'Admin cancel percentage must be greater than 0.',
            'admin_cancel_percentage.max' => 'Admin cancel percentage must be less than 100.',
        ];
    }
}

