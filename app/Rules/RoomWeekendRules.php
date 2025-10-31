<?php

namespace App\Rules;


class RoomWeekendRules
{
    public static function rules($editId = null)
    {

        return [
            'weekend_price_variation'   => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    public static function messages()
    {
        return [
            'weekend_price_variation.required' => 'Room weekend price variation is required.',
            'weekend_price_variation.numeric' => 'Room weekend price variation must be a number.',
            'weekend_price_variation.min' => 'Room weekend price variation must be greater than 0.',
        ];
    }
}

