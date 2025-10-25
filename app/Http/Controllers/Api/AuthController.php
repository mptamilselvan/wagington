<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Requests\AuthValidation;
use App\Traits\HandlesAuthServiceResponses;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Wagington API",
 *     description="API Documentation for Wagington Project"
 * )
 *
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication APIs"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    use HandlesAuthServiceResponses;
    
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="2-Step Registration: Phone verification OR Email attachment",
     *     description="REGISTRATION FLOW: 2 steps (phone + email)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     description="STEP 1: Phone Verification Only - Field names: 'country_code' + 'phone' + 'referal_code'(optional). DO NOT include 'email' field.",
     *                     required={"country_code", "phone"},
     *                     @OA\Property(property="country_code", type="string", example="+91", description="Field name: 'country_code' - Country code with + prefix"),
     *                     @OA\Property(property="phone", type="string", example="9876543210", description="Field name: 'phone' - Phone number without country code"),
     *                     @OA\Property(property="referal_code", type="string", example="REF123", description="Field name: 'referal_code' - Optional referral code")
     *                 ),
     *                 @OA\Schema(
     *                     description="STEP 2: Add Email to Existing Phone User - Field names: 'email' + 'country_code' + 'phone'. Use this to attach email to phone user.",
     *                     required={"country_code", "phone", "email"},
     *                     @OA\Property(property="country_code", type="string", example="+91", description="Field name: 'country_code' - Country code of existing phone user"),
     *                     @OA\Property(property="phone", type="string", example="9876543210", description="Field name: 'phone' - Phone number of existing user"),
     *                     @OA\Property(property="email", type="string", example="user@example.com", description="Field name: 'email' - Email address to attach to phone user")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="OTP sent to your mobile.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function register(Request $request)
    {
        try {
            AuthValidation::validateRegister($request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Determine phone format (old vs new)
        $fullPhone = null;
        $countryCode = null;
        $phone = null;

        if ($request->filled('country_code') && $request->filled('phone')) {
            // New format: separated country code and phone number
            $countryCode = $request->country_code;
            $phone = $request->phone;
            $fullPhone = $countryCode . $phone; // Combined for OTP service
        } elseif ($request->filled('full_phone')) {
            // Old format: full phone number
            $fullPhone = $request->full_phone;
        } else {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Please provide either full_phone or country_code with phone.'
            ], 422);
        }

        if (!$request->filled('email')) {
            // Phone registration only
            $result = $this->authService->registerWithPhone(
                $fullPhone,
                $request->referal_code,
                $countryCode,
                $phone
            );
            return $this->formatResponse($result);
        } else {
            // Phone + Email registration
            $result = $this->authService->attachEmailToUser(
                $fullPhone,
                $request->email
            );
            return $this->formatResponse($result);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Login: send OTP",
     *     description="CUSTOMER LOGIN: via email or phone",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     description="Phone login - Use field names: 'country_code' + 'phone'",
     *                     required={"country_code", "phone"},
     *                     @OA\Property(property="country_code", type="string", example="+91", description="Field name: 'country_code' - Country code with + prefix"),
     *                     @OA\Property(property="phone", type="string", example="9876543210", description="Field name: 'phone' - Phone number without country code")
     *                 ),
     *                 @OA\Schema(
     *                     description="Email login - Use field name: 'email'",
     *                     required={"email"},
     *                     @OA\Property(property="email", type="string", example="user@example.com", description="Field name: 'email' - Email address")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="OTP sent to your email. Please verify to complete login.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            AuthValidation::validateLogin($request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Determine phone format (old vs new)
        if ($request->filled('country_code') && $request->filled('phone')) {
            // New format: separated country code and phone number
            $fullPhone = $request->country_code . $request->phone;
            $result = $this->authService->sendLoginOtp($fullPhone, 'phone');
            return $this->formatResponse($result);
        } elseif ($request->filled('full_phone')) {
            // Old format: full phone number
            $result = $this->authService->sendLoginOtp($request->full_phone, 'phone');
            return $this->formatResponse($result);
        } elseif ($request->filled('email')) {
            // Email login
            $result = $this->authService->sendLoginOtp($request->email, 'email');
            return $this->formatResponse($result);
        }

        return response()->json([
            'status' => 'invalid',
            'message' => 'Please provide either full_phone (or country_code with phone) or email for login.'
        ], 422);
    }

    /**
     * @OA\Post(
     *     path="/api/resend-otp",
     *     tags={"Auth"},
     *     summary="Resend OTP for registration or login",
     *     description="RESEND OTP: Sends a new OTP to the user's phone or email during login or registration",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     description="Resend OTP for phone - Use field names: 'country_code' + 'phone' + 'type'",
     *                     required={"country_code", "phone", "type"},
     *                     @OA\Property(property="country_code", type="string", example="+91", description="Field name: 'country_code' - Country code with + prefix"),
     *                     @OA\Property(property="phone", type="string", example="9876543210", description="Field name: 'phone' - Phone number without country code"),
     *                     @OA\Property(property="type", type="string", enum={"phone"}, example="phone", description="Field name: 'type' - Must be 'phone'"),
     *                     @OA\Property(property="context", type="string", enum={"login", "registration"}, example="registration", description="Field name: 'context' - Optional context")
     *                 ),
     *                 @OA\Schema(
     *                     description="Resend OTP for email - Use field names: 'email' + 'type'",
     *                     required={"email", "type"},
     *                     @OA\Property(property="email", type="string", example="user@example.com", description="Field name: 'email' - Email address"),
     *                     @OA\Property(property="type", type="string", enum={"email"}, example="email", description="Field name: 'type' - Must be 'email'"),
     *                     @OA\Property(property="context", type="string", enum={"login", "registration"}, example="registration", description="Field name: 'context' - Optional context")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP resent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="New OTP sent to your mobile.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function resendOtp(Request $request)
    {
        try {
            AuthValidation::validateResendOtp($request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Extract identifier based on type
        $identifier = null;
        
        if ($request->type === 'phone') {
            // For phone type, use country_code + phone
            $identifier = $request->country_code . $request->phone;
        } elseif ($request->type === 'email') {
            // For email type, use email field
            $identifier = $request->email;
        } else {
            return response()->json([
                'errors' => ['message' => 'Invalid type. Use "phone" or "email".']
            ], 422);
        }

        $result = $this->authService->resendOtp(
            $identifier,
            $request->type,
            $request->context ?? 'login'
        );

        return $this->formatResponse($result);
    }

    /**
     * @OA\Post(
     *     path="/api/verify-otp",
     *     tags={"Auth"},
     *     summary="Unified OTP verification for all contexts",
     *     description="VERIFY OTP RULES: Verify enterded OTP for phone or email during login or registration.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     description="Verify OTP for phone - Use field names: 'country_code' + 'phone' + 'otp' + 'type'",
     *                     required={"country_code", "phone", "otp", "type"},
     *                     @OA\Property(property="country_code", type="string", example="+91", description="Field name: 'country_code' - Country code with + prefix"),
     *                     @OA\Property(property="phone", type="string", example="9876543210", description="Field name: 'phone' - Phone number without country code"),
     *                     @OA\Property(property="otp", type="string", example="123456", description="Field name: 'otp' - 6-digit OTP code"),
     *                     @OA\Property(property="type", type="string", enum={"phone"}, example="phone", description="Field name: 'type' - Must be 'phone'"),
     *                     @OA\Property(property="context", type="string", enum={"basic", "login", "registration"}, example="registration", description="Optional: basic=just verify, login=verify+login, registration=verify+update user")
     *                 ),
     *                 @OA\Schema(
     *                     description="Verify OTP for email - Use field names: 'email' + 'otp' + 'type'",
     *                     required={"email", "otp", "type"},
     *                     @OA\Property(property="email", type="string", example="user@example.com", description="Field name: 'email' - Email address"),
     *                     @OA\Property(property="otp", type="string", example="123456", description="Field name: 'otp' - 6-digit OTP code"),
     *                     @OA\Property(property="type", type="string", enum={"email"}, example="email", description="Field name: 'type' - Must be 'email'"),
     *                     @OA\Property(property="context", type="string", enum={"basic", "login", "registration"}, example="registration", description="Optional: basic=just verify, login=verify+login, registration=verify+update user")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Login successful.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function verifyOtpUnified(Request $request)
    {
        try {
            AuthValidation::validateUnifiedVerifyOtp($request->all());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // Extract identifier based on type
            $identifier = null;
            
            if ($request->type === 'phone') {
                // For phone type, use country_code + phone
                $identifier = $request->country_code . $request->phone;
            } elseif ($request->type === 'email') {
                // For email type, use email field
                $identifier = $request->email;
            } else {
                return response()->json([
                    'status' => 'invalid',
                    'message' => 'Invalid type. Use "phone" or "email".'
                ], 422);
            }

            $result = $this->authService->verifyOtp(
                $identifier,
                $request->otp,
                $request->type,
                $request->context ?? 'basic'
            );

            return $this->formatResponse($result);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during OTP verification.'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Logout user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully"
     *     )
     * )
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to logout, token invalid'
            ], 500);
        }
    }

    /**
     * Maps AuthService structured responses to HTTP responses.
     * Now using the trait method for consistency.
     */
    private function formatResponse($result)
    {
        return $this->formatApiResponse($result);
    }
}