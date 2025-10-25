<?php

namespace App\Rules;

class BloodTestRecordRules
{
    public static function rules($editId = null)
    {
        return  [
            'pet_id'         => 'required|exists:pets,id',
            'blood_test_id' => 'required|exists:blood_tests,id',
            'date'           => 'required|date|before_or_equal:today',
            'document'       => $editId ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'notes'          => 'nullable|string|max:200',
            'status' => 'required',
        ];
    }

    public static function messages()
    {
        return [
            'blood_test_id.required' => 'The name of test field is required.',
            'status.required' => 'The test status field is required.',
            'date.required' => 'The date of test field is required.',
            'document.required' => 'The test image is required.',
        ];
    }
}