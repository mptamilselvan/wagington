<?php

namespace App\Rules;

class PetTagRules
{
    public static function rules()
    {
        return [
            'species_id'         => 'required|exists:species,id',
            'tag'          => 'required|string|max:50',
            'from_age' => 'required|integer|min:0',
            'to_age' => 'required|integer|min:1',
        ];
    }

    public static function messages()
    {
        return [
            'species_id.required' => 'The species field is required.',
            'tag.required' => 'The pet tag field is required.',
        ];
    }
}