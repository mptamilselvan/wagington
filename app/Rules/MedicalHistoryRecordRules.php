<?php

namespace App\Rules;

class MedicalHistoryRecordRules
{
    public static function rules($editId)
    {
        return [
            'pet_id'         => 'required|exists:pets,id',
            'document'       => $editId ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'notes'          => 'nullable|string|max:200',
            'name' => 'required',
        ];
    }

    public static function messages()
    {
        return [
            'document.required' => 'File upload is required.',
        ];
    }
}
