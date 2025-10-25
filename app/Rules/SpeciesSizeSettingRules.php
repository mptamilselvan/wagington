<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class SpeciesSizeSettingRules
{
    public static function rules($editId = null)
    {
        return [
            'newSize'   => [
                'required',
                'string',
                'max:55',
            ],
            'newColor'   => [
                'required',
                'string',
                'max:55',
            ]
        ];
    }

    public static function messages()
    {
        return [
            'newSize.required' => 'New size is required.',
            'newSize.string' => 'New size must be a string.',
            'newSize.max' => 'New size must be less than 55 characters.',
            'newColor.required' => 'New color is required.',
            'newColor.string' => 'New color must be a string.',
            'newColor.max' => 'New color must be less than 55 characters.',
        ];
    }
}

