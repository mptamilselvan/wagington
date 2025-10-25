<?php

namespace App\Rules;

use Illuminate\Validation\Rule;

class VaccineExemptionRules
{
    public static function rules($editId = null)
    {
        return [
            'species_id'   => 'required|exists:species,id|unique:vaccine_exemptions,species_id,' . ($editId ?? 'NULL') . ',id',
            'blood_test_id'   => 'required|array|min:1',
            'blood_test_id.*' => 'exists:blood_tests,id',
        ];
    }

    public static function messages()
    {
        return [
            'species_id.required' => 'The species field is required.',
            'blood_test_id.required' => 'The blood test field is required.',
        ];
    }
}
