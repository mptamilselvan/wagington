<?php

namespace App\Rules;

class DewormingRecordRules
{
    public static function rules($editId)
    {
        return [
            'pet_id'         => 'required|exists:pets,id',
            'date'           => 'required|date|before_or_equal:today',
            'document'       => $editId ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'notes'          => 'nullable|string|max:200',
            'brand_name' => 'required',
        ];
    }

    public static function messages()
    {
        return [
            'date.required' => 'The date of test field is required.',
            'document.required' => 'File upload is required.',
        ];
    }
}