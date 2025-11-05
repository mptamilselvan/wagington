<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class ServiceTypeRules
{
    public static function rules($editId = null)
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('service_types', 'name')
                    ->ignore($editId) // ignore current record if editing
                    ->whereNull('deleted_at'), // only check non-deleted records
            ],
            'image'       => ($editId)
            ? 'nullable|file|mimes:jpg,jpeg,png|max:5120'
            : 'required|file|mimes:jpg,jpeg,png|max:5120',
            'description'       =>  'nullable|string|max:200',
            'meta_title'       =>  'nullable|string|max:50',
            'meta_keywords'       =>  'nullable|string',
            'focus_keywords'       =>  'nullable|string',
            'meta_description'       =>  'nullable|string|max:200',
        ];
    }

    public static function messages()
    {
        return [
            'name.required' => 'Name of service type field is required.',
        ];
    }
}