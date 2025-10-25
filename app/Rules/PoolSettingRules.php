<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class PoolSettingRules
{
    public static function rules($editId = null)
    {
        return [
            'name' => ['required','string','max:50'],
            'type' => ['required','string','max:50'],
            'allowed_pet' => 'required|integer|min:1',
        ];
    }

    public static function messages()
    {
        return [
            'name.required' => 'Name of pool field is required.',
            'type.required' => 'Type of pool field is required.',
        ];
    }
}