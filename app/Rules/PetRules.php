<?php

namespace App\Rules;
use App\Rules\BreedBelongsToSpecies;
use App\Models\Species;
use Auth;

class PetRules
{
    public static function rules($species_id,$flag = false)
    {
        $dogId = Species::whereRaw("LOWER(name) = ?", ['dog'])->value('id');

        $rules = [];
        if($flag == false)
        {
            $rules['user_id'] = 'required|exists:users,id';
            $rules['name'] = 'required|string|max:50';
            $rules['gender'] = 'required';
            $rules['species_id'] = 'required|exists:species,id';
            $rules['breed_id'] = ['required','exists:breeds,id',new BreedBelongsToSpecies($species_id)];
            $rules['color'] = 'required|string|max:50';
            $rules['date_of_birth'] = 'required|date|before_or_equal:today';
            $rules['sterilisation_status'] =  'required';
            $rules['profile_image'] =  'nullable|mimes:jpg,jpeg,png|max:2048';
        }

        if($flag == true || Auth::user()->hasRole('customer'))
        {
            $rules['microchip_number'] = "nullable|string|max:50|regex:/^[a-zA-Z0-9]+$/|required_if:species_id,{$dogId}";
            $rules['length_cm'] = 'nullable|numeric';
            $rules['height_cm'] = 'nullable|numeric';
            $rules['weight_kg'] = 'nullable|numeric';
            $rules['avs_license_number'] = 'nullable|string|max:50';
            $rules['date_expiry'] = 'nullable|date|after_or_equal:today';
            $rules['document'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120';
        }

        return $rules;
    }

    public static function messages()
    {
        return [
            'microchip_number.required_if' => 'Microchip Number is required for Dogs.',
            'user_id.required' => 'Customer field is required.',
            'name.required' => 'Pet name field is required.',
            'species_id.required' => 'The species field is required.',
            'breed_id.required' => 'The breed field is required.',
        ];
    }
}
