<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Validator;

class OtpValidation
{
    public static function validateSend(array $data)
    {
        return Validator::make($data, [
            'identifier' => 'required|string',
            'type' => 'required|in:phone,email',
        ])->validate();
    }

    public static function validateVerify(array $data)
    {
        return Validator::make($data, [
            'identifier' => 'required|string',
            'otp' => 'required|digits:6',
            'type' => 'required|in:phone,email',
        ])->validate();
    }
}
