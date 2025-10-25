<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthValidation
{
    /**
     * Common validation rules for phone fields
     */
    private static function getPhoneRules()
    {
        return [
            'country_code' => 'nullable|string|regex:/^\+[1-9]\d{0,3}$/',
            'phone' => 'nullable|string|regex:/^[0-9]{8,15}$/',
        ];
    }

    /**
     * Common validation messages for phone fields
     */
    private static function getPhoneMessages()
    {
        return [
            'country_code.regex' => 'Country code must start with + and contain 1-4 digits.',
            'phone.regex' => 'Phone number must contain only digits and be 8-15 characters long.',
        ];
    }

    public static function validateRegister(array $data)
    {
        $rules = array_merge(self::getPhoneRules(), [
            'email' => 'nullable|email',
            'referal_code' => 'nullable|string',
        ]);

        return Validator::make($data, $rules, self::getPhoneMessages())->validate();
    }

    public static function validateLogin(array $data)
    {
        $rules = array_merge(self::getPhoneRules(), [
            'email' => 'nullable|email',
        ]);

        return Validator::make($data, $rules, self::getPhoneMessages())->validate();
    }

    /**
     * Unified OTP verification - replaces validateVerifyRegistrationOtp, validateVerifyLoginOtp, and validateUnifiedVerifyOtp
     */
    public static function validateVerifyOtp(array $data)
    {
        $rules = [
            'otp' => 'required|digits:6',
            'type' => 'required|in:phone,email',
            'context' => 'nullable|in:basic,login,registration',
        ];

        // Type-specific validation
        if (isset($data['type'])) {
            if ($data['type'] === 'phone') {
                $rules['country_code'] = 'required|string|regex:/^\+[1-9]\d{0,3}$/';
                $rules['phone'] = 'required|string|regex:/^[0-9]{8,15}$/';
                $rules['email'] = 'prohibited'; // Email should not be present for phone type
            } elseif ($data['type'] === 'email') {
                $rules['email'] = 'required|email';
                $rules['country_code'] = 'prohibited'; // Phone fields should not be present for email type
                $rules['phone'] = 'prohibited';
            }
        }

        $messages = array_merge(self::getPhoneMessages(), [
            'email.prohibited' => 'Email field should not be provided when type is phone.',
            'country_code.prohibited' => 'Country code field should not be provided when type is email.',
            'phone.prohibited' => 'Phone field should not be provided when type is email.',
        ]);

        return Validator::make($data, $rules, $messages)->validate();
    }

    public static function validateResendOtp(array $data)
    {
        $rules = [
            'type' => 'required|in:phone,email',
            'context' => 'nullable|in:login,registration',
        ];

        // Type-specific validation
        if (isset($data['type'])) {
            if ($data['type'] === 'phone') {
                $rules['country_code'] = 'required|string|regex:/^\+[1-9]\d{0,3}$/';
                $rules['phone'] = 'required|string|regex:/^[0-9]{8,15}$/';
                $rules['email'] = 'prohibited'; // Email should not be present for phone type
            } elseif ($data['type'] === 'email') {
                $rules['email'] = 'required|email';
                $rules['country_code'] = 'prohibited'; // Phone fields should not be present for email type
                $rules['phone'] = 'prohibited';
            }
        }

        $messages = array_merge(self::getPhoneMessages(), [
            'email.prohibited' => 'Email field should not be provided when type is phone.',
            'country_code.prohibited' => 'Country code field should not be provided when type is email.',
            'phone.prohibited' => 'Phone field should not be provided when type is email.',
        ]);

        return Validator::make($data, $rules, $messages)->validate();
    }

    public static function validateAdminLogin(array $data)
    {
        return Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ])->validate();
    }

    // Backward compatibility methods - these will call the new unified method
    public static function validateVerifyRegistrationOtp(array $data)
    {
        return self::validateVerifyOtp($data);
    }

    public static function validateVerifyLoginOtp(array $data)
    {
        return self::validateVerifyOtp($data);
    }

    public static function validateUnifiedVerifyOtp(array $data)
    {
        return self::validateVerifyOtp($data);
    }
}
