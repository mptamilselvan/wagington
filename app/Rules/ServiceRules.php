<?php

namespace App\Rules;

class ServiceRules
{
    public static function rules($editService,$serviceTypeMap,$availableAttributes,$pricing_attributes,$pricingOptions,$items): array
    {
        // dd($serviceTypeMap);
        $attributes = [];
        $rules = [
            // Foreign keys
            'catalog_id'        => 'required|exists:catalogs,id',
            'service_type_id'   => 'required|exists:service_types,id',
            'category_id'       => 'required|exists:service_categories,id',
            'subcategory_id'    => 'required|exists:service_subcategories,id',
            'pool_id'   => [
                'nullable',
                'required_if:service_type_id,' . ($serviceTypeMap['Pool'] ?? $serviceTypeMap['pool'] ?? null),
                'exists:pool_settings,id',
            ],
            'species_id'        => 'required|exists:species,id',
            'limo_type'         => [
                'nullable',
                'required_if:service_type_id,' . ($serviceTypeMap['Limo'] ?? null),
                'in:pickup,drop_off,pickup_and_dropoff',
            ],
            'title'             => 'required|string|max:200',
            'slug' => 'required|string|max:200|unique:services,slug,' . ($editService ?? 'NULL') . ',id,deleted_at,NULL',
            'overview'          => 'required|string|max:200',
            'description'       => 'required|string|max:200',
            'highlight'         => 'nullable|string|max:200',
            'terms_and_conditions' => 'nullable|string|max:200',

            // Media / SEO
            'has_addon'          => 'boolean',
            'meta_title'        => 'required|string|max:50',
            'meta_description'  => 'required|string|max:200',
            'meta_keywords'     => 'required|string',
            'focus_keywords'    => 'required|string',

            // Configuration
            // 'items' => 'nullable|array',
            // 'items.*.content' => 'nullable|string|max:50',
            // 'items.*.document' => 'nullable|mimes:jpg,jpeg,png|max:2048',

            'pet_selection_required' => 'boolean',
            'evaluvation_required'   => 'boolean',
            'is_shippable'           => 'boolean',
            'limo_pickup_dropup_address' => 'boolean',

            // Pricing
            'pricing_type'      => 'required|in:fixed,advance,distance_based',
            'pricing_attributes'=> 'required|array',
            // 'booking_slot_flag' => 'boolean',

            // Hierarchy & details
            'lable'             => 'nullable|string|max:255',
            'price'             => 'nullable|numeric|min:0|max:9999999.99',
            'no_humans'         => 'nullable|integer|min:1',
            'no_pets'           => 'nullable|integer|min:1',
            'duration'          => 'nullable|numeric|min:0',
            'km_start'          => 'nullable|numeric|min:0',
            'km_end'            => 'nullable|numeric|gte:km_start',
        ];

        if($editService == ''){
            $rules['images'] = 'required|array|size:4';
            $rules['images.*'] = 'required|file|mimes:jpg,jpeg,png|max:2048';
        }
        else if($editService != ''){
            $rules['images'] = 'nullable|array';
            $rules['images.*'] = 'nullable|file|mimes:jpg,jpeg,png|max:2048';
        }

        $rules['items'] = 'nullable|array';

        
        foreach ($items as $index => $item) {
            $hasExistingPreview = !empty($item['preview']);
            $isNewUpload = isset($item['document']) && $item['document'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
            
            // dd($isNewUpload);
            $rules["items.$index.content"] = 'nullable|string|max:50';

            // Only validate file type if new file uploaded
            if ($isNewUpload) {
                $rules["items.$index.document"] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048';
            } else {
                // no new upload, preview exists → skip file rule
                $rules["items.$index.document"] = $hasExistingPreview ? 'nullable' : 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048';
            }
        }

        
        // Conditional rules based on pricing_attributes
        foreach ($pricingOptions as $index => $option) {
            foreach ($option as $key => $value) {
                // $attributeMap = collect($availableAttributes)->pluck('option', 'value')->toArray();
                $attr = collect($availableAttributes)->firstWhere('value', $key);
                // dd($key,$attr,$value);
                if (!$attr) continue;

                $field = "pricingOptions.$index.$key";

                switch (strtolower($attr['data_type'])) {
                    case 'text':
                        $rules[$field] = 'required|string|max:255';
                        break;

                    case 'intger': // note: you had 'Intger' in DB
                        $rules[$field] = 'required|integer|min:0';
                        break;

                    case 'decimal':
                        $rules[$field] = 'required|numeric|min:0';
                        break;

                    case 'time':
                        $rules[$field] = 'required|date_format:H:i'; // or your preferred format
                        break;
                }

                // Set friendly name for error messages
                $attributes[$field] = $attr['option'] ?? "Option #$index";
            }
        }

        return [
            'rules' => $rules,
            'attributes' => $attributes,
        ];
    }

    public static function messages(): array
    {
        return [
            'catalog_id.required' => 'Catalog selection is required.',
            'pool_id.required_if' => 'The pool id field is required when service type id is Pool.',
            'slug.unique'         => 'The slug must be unique for active records.',
            'km_end.gte'          => 'The end kilometer must be greater than or equal to the start kilometer.',
            'items.*.content.required' => 'Please enter the title.',
            'items.*.content.max' => 'Each title must not be greater than 5 characters.',
            'items.*.document.mimes' => 'Only JPG, JPEG, or PNG files are allowed.',
            'images.required' => 'Please upload exactly 4 images.',
            'images.size' => 'You must upload a exactly 4 images — no more, no less.',
            'images.*.mimes' => 'Only JPG, JPEG, or PNG files are allowed.',
            'images.*.max' => 'Each image must be less than 2MB.',
        ];
    }
}