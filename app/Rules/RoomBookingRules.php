<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class RoomBookingRules
{
    public static function rules($editId = null)
    {

        return [

            'room_type_id'   => [
                'required',
                'string',
                'max:255',
            ],
            'customer_id'   => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
            'species_id'   => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
           'service_addons.*'   => [
                'required',
                'string',
            ],
            'service_addons'   => [
                'nullable',
                'array',
            ],
           
        ];
    }

    public static function messages()
    {
        return [
            'room_type_id.required' => 'Room type is required.',
            'customer_id.required' => 'Customer is required.',
            'pets_reserved.required' => 'At least one pet is required.',
            'pets_reserved.array' => 'Pets must be an array.',
            'pets_reserved.min' => 'At least one pet is required.',
            'pets_reserved.*.required' => 'Each pet is required.',
            'pets_reserved.*.string' => 'Each pet must be a string.',
            'pets_reserved.*.max' => 'Each pet must not exceed 255 characters.',
            'species_id.required' => 'Species is required.',
            'species_id.integer' => 'Species must be an integer.',
            'species_id.exists' => 'Species must exist.',
            'service_addons.required' => 'Service addons are required.',
            'service_addons.*.required' => 'Each service addon is required.',
            'service_addons.*.string' => 'Each service addon must be a string.',
            'service_addons.*.max' => 'Each service addon must not exceed 255 characters.',
        ];
    }
}

