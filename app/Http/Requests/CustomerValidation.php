<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerValidation
{
    public static function validateProfileUpdate(array $data)
    {
        $userId = Auth::id();
        
        $validator = Validator::make($data, [
            'name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at')
            ],
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'dob' => 'nullable|date|before:today',
            'passport_nric_fin_number' => 'nullable|string|max:20',
            'phone' => [
                'nullable',
                'regex:/^[0-9]{5,20}$/',
                Rule::unique('users', 'phone')->ignore($userId)->whereNull('deleted_at')
            ],
            'country_code' => 'nullable|string|max:10',
            'secondary_first_name' => 'nullable|string|max:255',
            'secondary_last_name' => 'nullable|string|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'secondary_phone' => 'nullable|string|max:20',
            'secondary_country_code' => 'nullable|string|max:10',
        ]);

        // Note: Secondary field validation (ensuring they don't match primary fields) 
        // is handled by CustomerService.validateCustomerProfileData()

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        
        return $validated;
    }

    /**
     * Validate customer profile update from admin side
     */
    public static function validateAdminProfileUpdate(array $data, int $userId)
    {
        $validator = Validator::make($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at')
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($userId)->whereNull('deleted_at')
            ],
            'country_code' => 'required|string|max:10',
            'dob' => 'required|date|before:today',
            'passport_nric_fin_number' => 'required|string|max:20',
            'secondary_first_name' => 'nullable|string|max:255',
            'secondary_last_name' => 'nullable|string|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'secondary_phone' => 'nullable|string|max:20',
            'secondary_country_code' => 'nullable|string|max:10',
            'is_active' => 'boolean',
        ]);

        // Note: Secondary field validation (ensuring they don't match primary fields) 
        // is handled by CustomerService.validateCustomerProfileData()

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate new customer creation from admin side
     */
    public static function validateAdminCustomerCreation(array $data)
    {
        $validator = Validator::make($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->whereNull('deleted_at')
            ],
            'country_code' => 'required|string|max:10',
            'dob' => 'required|date|before:today',
            'passport_nric_fin_number' => 'required|string|max:20',
            'secondary_first_name' => 'nullable|string|max:255',
            'secondary_last_name' => 'nullable|string|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'secondary_phone' => 'nullable|string|max:20',
            'secondary_country_code' => 'nullable|string|max:10',
            'is_active' => 'boolean',
        ]);

        // Note: Secondary field validation (ensuring they don't match primary fields) 
        // is handled by CustomerService.validateCustomerProfileData()

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}