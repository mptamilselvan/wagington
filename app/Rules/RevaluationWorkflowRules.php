<?php

namespace App\Rules;

class RevaluationWorkflowRules
{
    public static function rules()
    {
        return [
            'species_id'         => 'required|exists:species,id',
            'age_threshold_months' => 'required|integer|min:1',
        ];
    }

    public static function messages()
    {
        return [
            'species_id.required' => 'The species field is required.',
            'age_threshold_months.required' => 'The age field is required.',
        ];
    }
}