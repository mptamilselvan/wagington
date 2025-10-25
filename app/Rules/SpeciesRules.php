<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class SpeciesRules
{
    public static function rules($editId = null)
    {
        // Require file only for creation
        return [
            'name'   => [
                'required',
                'string',
                'max:255',
                Rule::unique('species')->ignore($editId, 'id'),
            ],
            'description' => 'nullable|string|max:200',
            'photo'       => $editId ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }

    public static function messages()
    {
        return [
            'photo.required' => 'File upload is required.',
        ];
    }
}
