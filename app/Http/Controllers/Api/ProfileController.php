<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Services\ProfileService;
use App\Services\OtpService;
use App\Services\CustomerService;

class ProfileController extends Controller
{
    protected $profileService;
    protected $otpService;
    protected $customerService;

    public function __construct(ProfileService $profileService, OtpService $otpService, CustomerService $customerService)
    {
        $this->profileService = $profileService;
        $this->otpService = $otpService;
        $this->customerService = $customerService;
    }

        /**
     * Save complete profile data to database (Mobile API - Single endpoint)
     * This is the ONLY method that hits the database for mobile
     */
    public function saveProfile(Request $request)
    {
        try {
            $request->validate([
                // Step 1: Customer Profile
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'country_code' => 'required|string',
                'phone' => 'required|string|max:20',
                'dob' => 'nullable|date',
                'passport_nric_fin_number' => 'nullable|string|max:50',
                
                // Step 2: Addresses
                'addresses' => 'required|array',
                'addresses.*.address_type_id' => 'required|integer',
                'addresses.*.country' => 'required|string|max:255',
                'addresses.*.postal_code' => 'required|string|max:20',
                'addresses.*.address_line1' => 'required|string|max:255',
                'addresses.*.address_line2' => 'nullable|string|max:255',
                'addresses.*.label' => 'nullable|string|max:100',
                'addresses.*.is_billing_address' => 'boolean',
                'addresses.*.is_shipping_address' => 'boolean',
                
                // Step 3: Secondary Contact
                'secondary_first_name' => 'required|string|max:255',
                'secondary_last_name' => 'required|string|max:255',
                'secondary_email' => 'required|email|max:255',
                'secondary_phone' => 'required|string|max:20'
            ]);

            // Prepare all data (Form1 + Form2 + Form3)
            $profileData = $request->only([
                'first_name', 'last_name', 'email', 'country_code', 
                'phone', 'dob', 'passport_nric_fin_number',
                'addresses',
                'secondary_first_name', 'secondary_last_name', 
                'secondary_email', 'secondary_phone'
            ]);

            // Prepare context for ProfileService
            $context = [
                'platform' => 'mobile',
                'updating_user_id' => Auth::id(),
                'current_user_id' => Auth::id()
            ];

            // Call ProfileService to save everything to database (SINGLE METHOD)
            $result = $this->profileService->updateOrCreateProfile($profileData, $context);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Profile saved successfully!',
                    'data' => $result['user']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'An error occurred while saving your profile.'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving your profile.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/profile/send-email-otp",
     *     tags={"Profile"},
     *     summary="Send OTP for email verification",
     *     description="Send OTP to email for profile verification",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"new_email"},
     *             @OA\Property(property="new_email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Verification code sent to your email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to send verification code"
     *     )
     * )
     */
    public function sendEmailOtp(Request $request)
    {
        try {
            $request->validate([
                'new_email' => 'required|email'
            ]);

            // Use ProfileService method that includes all business validation
            $result = $this->profileService->sendEmailOtpForProfileUpdate(Auth::id(), $request->new_email);

            $statusCode = $result['success'] ? 200 : 422;
            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            \Log::error('ProfileController: sendEmailOtp failed', [
                'user_id' => Auth::id(),
                'email' => $request->get('new_email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code.'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/profile/send-phone-otp",
     *     tags={"Profile"},
     *     summary="Send OTP for phone verification",
     *     description="Send OTP to phone for profile verification",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"new_phone", "country_code"},
     *             @OA\Property(property="new_phone", type="string", example="1234567890"),
     *             @OA\Property(property="country_code", type="string", example="+65")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Verification code sent to your phone")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to send verification code"
     *     )
     * )
     */
    public function sendPhoneOtp(Request $request)
    {
        try {
            $request->validate([
                'new_phone' => 'required|string',
                'country_code' => 'required|string'
            ]);

            // Use ProfileService method that includes all business validation
            $result = $this->profileService->sendPhoneOtpForProfileUpdate(
                Auth::id(), 
                $request->new_phone, 
                $request->country_code
            );

            $statusCode = $result['success'] ? 200 : 422;
            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            \Log::error('ProfileController: sendPhoneOtp failed', [
                'user_id' => Auth::id(),
                'phone' => $request->get('new_phone'),
                'country_code' => $request->get('country_code'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code.'
            ], 500);
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:email,phone',
                'otp' => 'required|string|size:6'
            ]);

            $result = $this->profileService->verifyWithOtp(
                $request->type, 
                $request->otp, 
                Auth::id()
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Verification failed. Please try again.'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/profile/verify-and-update-email",
     *     tags={"Profile"},
     *     summary="Verify OTP and update email address",
     *     description="Verifies the OTP code and updates the user's email address in the database upon successful verification",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"new_email", "otp"},
     *             @OA\Property(property="new_email", type="string", format="email", example="newemail@example.com"),
     *             @OA\Property(property="otp", type="string", example="123456", description="6-digit OTP code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation failed or OTP verification failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid OTP code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update email. Please try again.")
     *         )
     *     )
     * )
     */
    public function verifyAndUpdateEmail(Request $request)
    {
        try {
            $request->validate([
                'new_email' => 'required|email|max:255',
                'otp' => 'required|string|size:6'
            ]);

            $newEmail = $request->new_email;
            $currentUser = Auth::user();

            // Use ProfileService for validation consistency (without sending OTP)
            $validationResult = $this->profileService->validateEmailForProfileUpdate(Auth::id(), $newEmail);
            if (!$validationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message']
                ], 422);
            }

            // Verify the OTP for the new email
            $verificationResult = $this->otpService->verifyOtp($newEmail, $request->otp, 'email');
            
            if ($verificationResult !== 'success') {
                $errorMessage = $verificationResult === 'expired' ? 'OTP code expired' : 'Invalid OTP code';
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            // Use CustomerService to securely update the verified email
            $updateResult = $this->customerService->updateVerifiedEmail(Auth::id(), $newEmail);

            return response()->json([
                'success' => $updateResult['status'] === 'success',
                'message' => $updateResult['message']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update email. Please try again.'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/profile/verify-and-update-phone",
     *     tags={"Profile"},
     *     summary="Verify OTP and update phone number",
     *     description="Verifies the OTP code and updates the user's phone number in the database upon successful verification",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"new_phone", "country_code", "otp"},
     *             @OA\Property(property="new_phone", type="string", example="91234567"),
     *             @OA\Property(property="country_code", type="string", example="+65"),
     *             @OA\Property(property="otp", type="string", example="123456", description="6-digit OTP code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phone number updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Phone number updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation failed or OTP verification failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid OTP code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update phone number. Please try again.")
     *         )
     *     )
     * )
     */
    public function verifyAndUpdatePhone(Request $request)
    {
        try {
            $request->validate([
                'new_phone' => 'required|string|max:20',
                'country_code' => 'required|string',
                'otp' => 'required|string|size:6'
            ]);

            $newPhone = $request->new_phone;
            $countryCode = $request->country_code;

            // Use ProfileService for validation consistency (without sending OTP)
            $validationResult = $this->profileService->validatePhoneForProfileUpdate(Auth::id(), $newPhone, $countryCode);
            if (!$validationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $validationResult['message']
                ], 422);
            }

            // Create full phone number for OTP verification
            $fullPhone = $countryCode . $newPhone;
            
            // First verify the OTP for the new phone
            $verificationResult = $this->otpService->verifyOtp($fullPhone, $request->otp, 'phone');
            
            if ($verificationResult !== 'success') {
                $errorMessage = $verificationResult === 'expired' ? 'OTP code expired' : 'Invalid OTP code';
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            // Use CustomerService to securely update the verified phone
            $updateResult = $this->customerService->updateVerifiedPhone(Auth::id(), $newPhone, $countryCode);

            return response()->json([
                'success' => $updateResult['status'] === 'success',
                'message' => $updateResult['message']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update phone number. Please try again.'
            ], 500);
        }
    }


}