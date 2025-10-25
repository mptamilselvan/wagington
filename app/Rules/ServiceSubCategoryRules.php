<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class ServiceSubCategoryRules
{
    public static function rules($editId = null)
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('service_subcategories', 'name')
                    ->ignore($editId) // ignore current record if editing
                    ->whereNull('deleted_at'), // only check non-deleted records
            ],
            'image'       => ($editId)
            ? 'nullable|file|mimes:jpg,jpeg,png|max:2048'
            : 'required|file|mimes:jpg,jpeg,png|max:2048',
            'description'       =>  'nullable|string|max:200',
            'category_id'       =>  'required|exists:service_categories,id',
            'meta_title'       =>  'nullable|string|max:50',
            'meta_keywords'       =>  'nullable|string',
            'focus_keywords'       =>  'nullable|string',
            'meta_description'       =>  'nullable|string|max:200',
        ];
    }

    public static function messages()
    {
        return [
            'name.required' => 'Name of subcategory field is required.',
            'category_id.required' => 'Please select service category.',
        ];
    }
}