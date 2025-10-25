<?php

namespace App\Rules;

class AdvanceDurationRules
{
    public static function rules()
    {
        return [
            'advance_days'         => 'required|integer|min:0',
            'advance_hours'          => 'required|integer|min:0|max:23',
        ];
    }
}