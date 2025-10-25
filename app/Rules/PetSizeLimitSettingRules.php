<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class PetSizeLimitSettingRules
{
    public static function rules($editId = null)
    {
        return [
            'room_type_id'   => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ]
           
            
        ];
    }

    public static function messages()
    {
        return [
            'room_type_id.required' => 'Room Type is required.',
            'room_type_id.integer' => 'Room Type must be an integer.'
            
        ];
    }
}

