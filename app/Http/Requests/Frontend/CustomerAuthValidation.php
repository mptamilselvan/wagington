<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomerAuthValidation
{
    public static function validateLogin(array $data)
    {
        $validator = Validator::make($data, [
            'identifier' => 'required|string',
            'type' => 'required|in:phone,email',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public static function validateVerifyOtp(array $data)
    {
        $validator = Validator::make($data, [
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}