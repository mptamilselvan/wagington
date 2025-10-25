<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class RoomTypeRules
{
    public static function rules($editId = null)
    {

        return [
            'name'   => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($editId) {
                    $query = \App\Models\RoomTypeModel::whereRaw('LOWER(name) = LOWER(?)', [$value]);
                    
                    if ($editId) {
                        $query->where('id', '!=', $editId);
                    }
                    
                    if ($query->exists()) {
                        $fail('This room type name already exists (case-insensitive).');
                    }
                },
            ],
            'slug'   => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                function ($attribute, $value, $fail) use ($editId) {
                    $query = \App\Models\RoomTypeModel::whereRaw('LOWER(slug) = LOWER(?)', [$value]);
                    
                    if ($editId) {
                        $query->where('id', '!=', $editId);
                    }
                    
                    if ($query->exists()) {
                        $fail('This slug already exists (case-insensitive).');
                    }
                },
            ],
            'species_id'   => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
            'room_attributes'   => [
                'required',
                'string',
            ],
            'room_description'   => [
                'required',
                'string',
            ],
            'room_overview'   => [
                'required',
                'string',
            ],
            'images'   => [
                $editId ? 'nullable' : 'required',
                'array',
                'max:4',
            ],
            'images.*'   => [
                $editId ? 'nullable' : 'required',
                'image',
                'mimes:jpeg,png,jpg,gif|max:4048',
            ],
            'service_addons'   => [
                'nullable',
                'array',
            ],
            'default_clean_minutes'   => [
                'nullable',
                'numeric',
            ],
            'turnover_buffer_min'   => [
                'nullable',
                'numeric',
            ],
            'aggreed_terms.*.content'   => [
                'nullable',
                'string',
                'max:200',
            ],
            'aggreed_terms.*.document'   => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
                function ($attribute, $value, $fail) {
                    // Get the index from the attribute name
                    $index = explode('.', $attribute)[1];
                    
                    // Check if content is provided
                    $contentField = "aggreed_terms.{$index}.content";
                    $content = request()->get($contentField);
                    
                    // Check if document_url already exists (for edit mode)
                    $documentUrlField = "aggreed_terms.{$index}.document_url";
                    $documentUrl = request()->get($documentUrlField);
                    
                    // If content is provided but no document is uploaded and no existing document_url
                    if ($content && !$value && !$documentUrl) {
                        $fail('A document is required when content is provided.');
                    }
                    
                    // If document is uploaded but no content
                    if ($value && !$content) {
                        $fail('Content is required when a document is uploaded.');
                    }
                },
            ],
            'price_options'   => [
                'nullable',
                'array',
            ],
            'price_options.*.label'   => [
                'nullable',
                'string',
                'max:50',
            ],
            'price_options.*.no_of_days'   => [
                'required_with:label',
                'numeric',
                'min:0',
            ],
            'price_options.*.price'   => [
                'required_with:label,no_of_days',
                'numeric',
                'min:0',
            ]
        ];
    }

    public static function messages()
    {
        return [
            'name.required' => 'Room type name is required.',
            'name.unique' => 'This room type name already exists (case-insensitive).',
            'slug.required' => 'Slug is required.',
            'slug.string' => 'Slug must be a string.',
            'slug.max' => 'Slug must not exceed 255 characters.',
            'slug.regex' => 'Slug must contain only lowercase letters, numbers, and hyphens.',
            'species_id.required' => 'Species is required.',
            'species_id.integer' => 'Species must be an integer.',
            'species_id.exists' => 'Species must exist.',
            'room_attributes.nullable' => 'Room attributes are nullable.',
            'room_attributes.string' => 'Room attributes must be a string.',
            'room_description.nullable' => 'Room description is nullable.',
            'room_description.string' => 'Room description must be a string.',
            'room_overview.nullable' => 'Room overview is nullable.',
            'room_overview.string' => 'Room overview must be a string.',
            'images.required' => 'At least one image is required when creating a new room type.',
            'images.nullable' => 'Images are optional when editing an existing room type.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'Images must be less than 4.',
            'images.*.required' => 'At least one image is required when creating a new room type.',
            'images.*.nullable' => 'Images are optional when editing an existing room type.',
            'images.*.image' => 'Images must be an image.',
            'images.*.mimes' => 'Images must be a jpeg, png, jpg, or gif.',
            'images.*.max' => 'Images must be less than 4048.',
            'service_addons.nullable' => 'Service addons are nullable.',
            'service_addons.array' => 'Service addons must be an array.',
            'default_clean_minutes.nullable' => 'Default clean minutes are nullable.',
            'default_clean_minutes.numeric' => 'Default clean minutes must be a number.',
            'turnover_buffer_min.nullable' => 'Turnover buffer minutes are nullable.',
            'turnover_buffer_min.numeric' => 'Turnover buffer minutes must be a number.',
            'aggreed_terms.*.content.nullable' => 'Aggreed terms content is nullable.',
            'aggreed_terms.*.content.string' => 'Aggreed terms content must be a string.',
            'aggreed_terms.*.document.nullable' => 'Aggreed terms document is nullable.',
            'aggreed_terms.*.document.file' => 'Aggreed terms document must be a file.',
            'aggreed_terms.*.document.mimes' => 'Aggreed terms document must be a pdf.',
            'aggreed_terms.*.document.max' => 'Aggreed terms document must be less than 10240.',
            'aggreed_terms.*.document.required_with' => 'Aggreed terms document is nullable when content is present.',
            'aggreed_terms.*.document.required' => 'Aggreed terms document is nullable.',
            'aggreed_terms.*.document.file' => 'Aggreed terms document must be a file.',
            'aggreed_terms.*.document.mimes' => 'Aggreed terms document must be a pdf.',
            'aggreed_terms.*.document.max' => 'Aggreed terms document must be less than 10240.',
            'aggreed_terms.*.document.required_with' => 'Aggreed terms document is nullable when content is present.',
            'aggreed_terms.*.document.required' => 'Aggreed terms document is nullable.',
            'price_options.nullable' => 'Price options are optional.',
            'price_options.array' => 'Price options must be an array.',
            'price_options.*.duration.required_with' => 'Duration is required when price options are provided.',
            'price_options.*.duration.string' => 'Duration must be a string.',
            'price_options.*.duration.max' => 'Duration must not exceed 50 characters.',
            'price_options.*.price.required_with' => 'Price is required when price options are provided.',
            'price_options.*.price.numeric' => 'Price must be a number.',
            'price_options.*.price.min' => 'Price must be greater than or equal to 0.',
            'price_options.*.currency.required_with' => 'Currency is required when price options are provided.',
            'price_options.*.currency.string' => 'Currency must be a string.',
            'price_options.*.currency.max' => 'Currency must not exceed 10 characters.',
        ];
    }
}

