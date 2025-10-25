<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminValidation
{
    public static function validateAddUser(array $data)
    {
        return Validator::make($data, [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'passport_nric_fin_number' => 'nullable|string|max:255',
            'phone' => [
                'required',
                'string',
                Rule::unique('users', 'phone')->whereNull('deleted_at')
            ],
            'country_code' => 'nullable|string|max:5',
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|string|max:255',
            'secondary_first_name' => 'nullable|string|max:255',
            'secondary_last_name' => 'nullable|string|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'secondary_phone' => 'nullable|string|max:20',
        ])->validate();
    }

    public static function validateUpdateUser(array $data, int $userId)
    {
        return Validator::make($data, [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'dob' => 'sometimes|date',
            'passport_nric_fin_number' => 'sometimes|string|max:255',
            'phone' => [
                'sometimes',
                'string',
                Rule::unique('users', 'phone')->ignore($userId)->whereNull('deleted_at')
            ],
            'country_code' => 'sometimes|string|max:5',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at')
            ],
            'image' => 'sometimes|string|max:255',
            'secondary_first_name' => 'sometimes|string|max:255',
            'secondary_last_name' => 'sometimes|string|max:255',
            'secondary_email' => 'sometimes|email|max:255',
            'secondary_phone' => 'sometimes|string|max:20',
        ])->validate();
    }

    public static function validateSecondaryContact(array $data)
    {
        return Validator::make($data, [
            'secondary_first_name' => 'nullable|string|max:255',
            'secondary_last_name' => 'nullable|string|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'secondary_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ])->validate();
    }

    public static function validateUserStatus(array $data)
    {
        return Validator::make($data, [
            'is_active' => 'required|boolean',
        ])->validate();
    }
}
