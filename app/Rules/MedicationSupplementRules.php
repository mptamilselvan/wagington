<?php

namespace App\Rules;

class MedicationSupplementRules
{
    public static function rules()
    {
        return [
            'pet_id'         => 'required|exists:pets,id',
            'notes'          => 'nullable|string|max:200',
            'name' => 'required|max:50',
            'dosage' => 'required|max:100',
            'type' => 'required|max:50',
        ];

    }
}