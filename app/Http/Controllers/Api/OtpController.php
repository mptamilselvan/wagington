<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use App\Http\Requests\OtpValidation;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="OTP",
 *     description="Phone and Email OTP APIs"
 * )
 */
class OtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function send(Request $request)
    {
        $validated = OtpValidation::validateSend($request->all());

        $this->otpService->sendOtp($validated['identifier'], $validated['type']);

        return response()->json([
            'message' => 'OTP sent successfully'
        ]);
    }

    public function verify(Request $request)
    {
        $validated = OtpValidation::validateVerify($request->all());

        $result = $this->otpService->verifyOtp($validated['identifier'], $validated['otp'], $validated['type']);

        if ($result === 'expired') {
            return response()->json(['message' => 'OTP expired.'], 422);
        }

        if ($result === 'mismatch') {
            return response()->json(['message' => 'Incorrect OTP.'], 422);
        }

        return response()->json([
            'message' => 'OTP verified successfully'
        ]);
    }
}
