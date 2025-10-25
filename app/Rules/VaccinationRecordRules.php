<?php

namespace App\Rules;

class VaccinationRecordRules
{
    public static function rules($cannot_vaccinate, $editId = null)
    {
        $cannot_vaccinate = filter_var($cannot_vaccinate, FILTER_VALIDATE_BOOLEAN);
        if ($cannot_vaccinate) {
            // When pet cannot be vaccinated, file is not required
            return [
                'pet_id'         => 'required|exists:pets,id',
                'customer_id'    => 'required|exists:users,id',
                'vaccination_id' => 'nullable',
                'date'           => 'nullable|date|before_or_equal:today',
                'document'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'notes'          => 'nullable|string|max:200',
            ];
        } else {
            // Require file only for creation
            return [
                'pet_id'         => 'required|exists:pets,id',
                'customer_id'    => 'required|exists:users,id',
                'vaccination_id' => 'required|exists:vaccinations,id',
                'date'           => 'required|date|before_or_equal:today',
                'document'       => $editId ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'notes'          => 'nullable|string|max:200',
            ];
        }
    }

    public static function messages()
    {
        return [
            'vaccination_id.required' => 'The name of vaccine field is required.',
            'date.required' => 'The date of vaccine field is required.',
            'document.required' => 'The vaccine card image is required.',
        ];
    }

}
