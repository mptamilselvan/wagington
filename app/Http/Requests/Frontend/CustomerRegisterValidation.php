<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Support\Facades\Validator;

class CustomerRegisterValidation
{
    public static function validateRegister(array $data)
    {
        return Validator::make($data, [
            'phone' => ['required', 'string', 'max:15'],
            'country_code' => ['required', 'string', 'max:10'],
            'referal_code' => ['nullable', 'string', 'max:20'],
        ])->validate();
    }

    public static function validateVerifyOtp(array $data)
{
    return Validator::make($data, [
        'otp' => ['required', 'string', 'digits:6'],
    ])->validate();
}


    public static function validateAttachEmail(array $data)
    {
        return Validator::make($data, [
            'email' => ['required', 'email', 'max:255'],
        ])->validate();
    }
}
