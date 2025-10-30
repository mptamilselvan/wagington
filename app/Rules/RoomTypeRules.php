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
                    $query = \App\Models\Room\RoomTypeModel::whereRaw('LOWER(name) = LOWER(?)', [$value]);
                    
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
                    $query = \App\Models\Room\RoomTypeModel::whereRaw('LOWER(slug) = LOWER(?)', [$value]);
                    
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
                'array',
                'min:1',
            ],
            'room_attributes.*'   => [
                'required',
                'string',
                'max:255',
            ],
            'room_amenities'   => [
                'nullable',
                'array',
            ],
            'room_amenities.*'   => [
                'required',
                'string',
                'max:255',
            ],
            'room_description'   => [
                'required',
                'string',
            ],
            'room_overview'   => [
                'required',
                'string',
            ],
            'images'   => array_filter([
                $editId ? 'nullable' : 'required',
                'array',
                $editId ? null : 'min:1',
                'max:4',
            ]),
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
                // 25600 KB = 25 MB
                'max:25600',
                function ($attribute, $value, $fail) {
                    // Get the index from the attribute name
                    $index = explode('.', $attribute)[1];
                    
                    // Get all request data
                    $allData = request()->all();
                    
                    // Try to get content from Livewire components data
                    $content = null;
                    $documentUrl = null;
                    
                    // Handle components data - it might be a JSON string
                    $componentsData = null;
                    if (isset($allData['components'])) {
                        if (is_string($allData['components'])) {
                            $componentsData = json_decode($allData['components'], true);
                        } else {
                            $componentsData = $allData['components'];
                        }
                    }
                    
                    // Access Livewire component data structure
                    if ($componentsData && isset($componentsData[0]['snapshot'])) {
                        // The snapshot is also a JSON string, so decode it
                        $snapshotData = null;
                        if (is_string($componentsData[0]['snapshot'])) {
                            $snapshotData = json_decode($componentsData[0]['snapshot'], true);
                        } else {
                            $snapshotData = $componentsData[0]['snapshot'];
                        }
                        
                        // Helper to unwrap Livewire's serialized arrays: [value, {"s":"arr"}]
                        $unwrapWireArray = function ($value) {
                            if (is_array($value) && count($value) === 2 && isset($value[1]['s'])) {
                                return $value[0];
                            }
                            return $value;
                        };

                        // Now access and unwrap the aggreed_terms data
                        if ($snapshotData && isset($snapshotData['data']['aggreed_terms'])) {
                            $aggreedTermsRaw = $snapshotData['data']['aggreed_terms'];
                            $aggreedTerms = $unwrapWireArray($aggreedTermsRaw);

                            if (is_array($aggreedTerms) && array_key_exists($index, $aggreedTerms)) {
                                $termDataRaw = $aggreedTerms[$index];
                                $termData = $unwrapWireArray($termDataRaw);

                                if (is_array($termData)) {
                                    $content = $termData['content'] ?? null;
                                    $documentUrl = $termData['document_url'] ?? null;
                                }
                            }
                        }
                    }
                    
                    // Debug logging with comprehensive data structure analysis
                    \Log::info('Document validation comprehensive', [
                        'attribute' => $attribute,
                        'index' => $index,
                        'content' => $content,
                        'content_length' => $content ? strlen($content) : 0,
                        'document_url' => $documentUrl,
                        'has_document' => $value ? 'yes' : 'no',
                        'all_data_keys' => array_keys($allData),
                        'components_exists' => isset($allData['components']),
                        'components_is_string' => isset($allData['components']) ? is_string($allData['components']) : false,
                        'components_count' => $componentsData ? count($componentsData) : 0,
                        'first_component_keys' => $componentsData && isset($componentsData[0]) && is_array($componentsData[0]) ? array_keys($componentsData[0]) : 'not_set',
                        'snapshot_exists' => $componentsData && isset($componentsData[0]['snapshot']),
                        'snapshot_is_string' => $componentsData && isset($componentsData[0]['snapshot']) ? is_string($componentsData[0]['snapshot']) : false,
                        'snapshot_data_exists' => isset($snapshotData),
                        'data_exists' => $snapshotData && isset($snapshotData['data']),
                        'data_keys' => $snapshotData && isset($snapshotData['data']) && is_array($snapshotData['data']) ? array_keys($snapshotData['data']) : 'not_set',
                        'aggreed_terms_exists' => $snapshotData && isset($snapshotData['data']['aggreed_terms']),
                        'aggreed_terms_keys' => $snapshotData && isset($snapshotData['data']['aggreed_terms']) && is_array($snapshotData['data']['aggreed_terms']) ? array_keys($snapshotData['data']['aggreed_terms']) : 'not_set',
                        'term_data_raw' => isset($termDataRaw) ? $termDataRaw : 'not_set',
                        'term_data_unwrapped' => isset($termData) ? $termData : 'not_set'
                    ]);
                    
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
            'room_attributes.required' => 'At least one room attribute is required.',
            'room_attributes.array' => 'Room attributes must be an array.',
            'room_attributes.min' => 'At least one room attribute is required.',
            'room_attributes.*.required' => 'Each room attribute is required.',
            'room_attributes.*.string' => 'Each room attribute must be a string.',
            'room_attributes.*.max' => 'Each room attribute must not exceed 255 characters.',
            'room_amenities.array' => 'Room amenities must be an array.',
            'room_amenities.*.required' => 'Each room amenity is required.',
            'room_amenities.*.string' => 'Each room amenity must be a string.',
            'room_amenities.*.max' => 'Each room amenity must not exceed 255 characters.',
            'room_description.nullable' => 'Room description is nullable.',
            'room_description.string' => 'Room description must be a string.',
            'room_overview.nullable' => 'Room overview is nullable.',
            'room_overview.string' => 'Room overview must be a string.',
            'images.required' => 'At least one image is required when creating a new room type.',
            'images.nullable' => 'Images are optional when editing an existing room type.',
            'images.array' => 'Images must be an array.',
            'images.min' => 'At least one image is required when creating a new room type.',
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
            'aggreed_terms.*.document.max' => 'Aggreed terms document must be less than 25600 kilobytes (25MB).',
            'aggreed_terms.*.document.required_with' => 'Aggreed terms document is nullable when content is present.',
            'aggreed_terms.*.document.required' => 'Aggreed terms document is nullable.',
            'aggreed_terms.*.document.file' => 'Aggreed terms document must be a file.',
            'aggreed_terms.*.document.mimes' => 'Aggreed terms document must be a pdf.',
            'aggreed_terms.*.document.max' => 'Aggreed terms document must be less than 25600 kilobytes (25MB).',
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

