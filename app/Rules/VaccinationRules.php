<?php

namespace App\Rules;

class VaccinationRules
{
    public static function rules()
    {
        return [
            'species_id'         => 'required|exists:species,id',
            'name'          => 'required|string|max:50',
            'expiry_days' => 'required|integer|min:1',
        ];
    }

    public static function messages()
    {
        return [
            'species_id.required' => 'The species field is required.',
            'name.required' => 'The name of vaccine field is required.',
        ];
    }
}