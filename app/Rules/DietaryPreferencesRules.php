<?php

namespace App\Rules;

class DietaryPreferencesRules
{
    public static function rules()
    {
        return [
            'pet_id'         => 'required|exists:pets,id',
            'notes'          => 'nullable|string|max:200',
            'feed_time' => 'required',
            'allergies' => 'nullable|string|max:200',
        ];
    }
}