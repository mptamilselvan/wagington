<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Mail\SignUpReferralCode;
use Illuminate\Support\Facades\Mail;
use App\Services\VoucherService;
use App\Models\BasePromotion;
use App\Models\Voucher;

class AuthService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';
    private const STATUS_CONFLICT = 'conflict';
    private const STATUS_NOT_FOUND = 'not_found';
    private const STATUS_INVALID = 'invalid';
    private const STATUS_INVALID_REFERRAL = 'invalid_referral';
    private const STATUS_INACTIVE = 'inactive';
    private const STATUS_RATE_LIMITED = 'rate_limited';
    private const STATUS_INVALID_PHONE = 'invalid_phone';
    private const STATUS_SERVICE_LIMIT = 'service_limit';
    private const STATUS_UNAUTHORIZED = 'unauthorized';
    private const STATUS_FORBIDDEN = 'forbidden';
    private const STATUS_VERIFICATION_REQUIRED = 'verification_required';

    // Type constants
    private const TYPE_PHONE = 'phone';
    private const TYPE_EMAIL = 'email';
    
    // Context constants
    private const CONTEXT_REGISTRATION = 'registration';
    private const CONTEXT_LOGIN = 'login';
    private const CONTEXT_GENERAL = 'general';

    // Role constants
    private const ROLE_CUSTOMER = 'customer';
    private const ROLE_ADMIN = 'admin';

    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Centralized OTP sending with consistent error handling
     * This ensures all OTP sending (API/Web, Login/Register) behaves the same way
     */
    private function sendOtpWithErrorHandling(string $identifier, string $type, string $context = self::CONTEXT_GENERAL): array
    {
        try {
            $this->otpService->sendOtp($identifier, $type);
            $displayType = $this->getDisplayType($type);
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => "OTP sent to your {$displayType}.",
                $type => $identifier,
            ];
        } catch (ValidationException $e) {
            return $this->handleRateLimitError($e, $type);
        } catch (Exception $e) {
            return $this->handleOtpSendingError($e, $identifier, $type, $context);
        }
    }

    /**
     * Handle rate limiting errors
     */
    private function handleRateLimitError(ValidationException $e, string $type): array
    {
        $errors = $e->errors();
        $message = $errors[$type][0] ?? 'Please wait 60 seconds before requesting another OTP.';
        
        return [
            'status' => self::STATUS_RATE_LIMITED,
            'message' => $message,
        ];
    }

    /**
     * Handle OTP sending errors with specific error mapping
     */
    private function handleOtpSendingError(Exception $e, string $identifier, string $type, string $context): array
    {
        Log::error("Failed to send OTP", [
            'type' => $type,
            'identifier' => $identifier,
            'context' => $context,
            'error' => $e->getMessage()
        ]);
        
        $errorMessage = $e->getMessage();
        
        // Map specific Twilio errors to user-friendly messages
        $errorMappings = [
            "Invalid 'To' Phone Number" => [
                'status' => self::STATUS_INVALID_PHONE,
                'message' => 'The phone number format is invalid for the selected country code. Please check your country code and phone number.',
            ],
            'exceeded' => [
                'status' => self::STATUS_SERVICE_LIMIT,
                'message' => 'SMS service daily limit exceeded. Please try again tomorrow or contact support.',
            ],  
            '429' => [
                'status' => self::STATUS_SERVICE_LIMIT,
                'message' => 'SMS service daily limit exceeded. Please try again tomorrow or contact support.',
            ],
            'not a valid phone number' => [
                'status' => self::STATUS_INVALID_PHONE,
                'message' => 'Please enter a valid phone number for the selected country.',
            ],
            'Invalid phone number' => [
                'status' => self::STATUS_INVALID_PHONE,
                'message' => 'The phone number is not valid. Please check the format and try again.',
            ],
        ];

        foreach ($errorMappings as $errorPattern => $response) {
            if (strpos($errorMessage, $errorPattern) !== false) {
                return $response;
            }
        }
        
        return [
            'status' => self::STATUS_ERROR,
            'message' => 'Failed to send OTP due to service error. Please try again.',
        ];
    }

    /**
     * Get display type for user messages
     */
    private function getDisplayType(string $type): string
    {
        return $type === self::TYPE_PHONE ? 'mobile' : $type;
    }

    /**
     * Find user by phone using different lookup methods
     */
    private function findUserByPhone(string $fullPhone, ?string $countryCode = null, ?string $phone = null): ?User
    {
        if ($countryCode && $phone) {
            return User::where('country_code', $countryCode)
                      ->where('phone', $phone)
                      ->first();
        }
        
        return User::whereRaw("CONCAT(country_code, phone) = ?", [$fullPhone])->first();
    }

    /**
     * Find user by email
     */
    private function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Find user by identifier and type
     */
    private function findUserByIdentifier(string $identifier, string $type): ?User
    {
        if ($type === self::TYPE_PHONE) {
            Log::info("Searching for user by phone", [
                'identifier' => $identifier,
                'type' => $type
            ]);
            
            $user = User::whereRaw("CONCAT(country_code, phone) = ?", [$identifier])->first();
            
            if (!$user) {
                // Try alternative formats for Indian numbers (common issue with leading zeros)
                if (str_starts_with($identifier, '+91')) {
                    // Try without leading zeros
                    $cleanIdentifier = preg_replace('/^(\+91)0+/', '$1', $identifier);
                    if ($cleanIdentifier !== $identifier) {
                        Log::info("Trying without leading zeros: " . $cleanIdentifier);
                        $user = User::whereRaw("CONCAT(country_code, phone) = ?", [$cleanIdentifier])->first();
                    }
                    
                    // Try with leading zero if not found
                    if (!$user && preg_match('/^(\+91)(\d+)$/', $identifier, $matches)) {
                        $countryCode = $matches[1];
                        $phoneNumber = $matches[2];
                        $withZero = $countryCode . '0' . $phoneNumber;
                        Log::info("Trying with leading zero: " . $withZero);
                        $user = User::whereRaw("CONCAT(country_code, phone) = ?", [$withZero])->first();
                    }
                }
            }
            
            Log::info("User search result", [
                'found' => $user ? true : false,
                'user_id' => $user ? $user->id : null,
                'final_identifier' => $identifier
            ]);
            
            return $user;
        }
        
        return User::where('email', $identifier)->first();
    }

    /**
     * Check if user exists but is soft deleted
     */
    private function isUserSoftDeleted(string $identifier, string $type): bool
    {
        if ($type === self::TYPE_PHONE) {
            $user = User::withTrashed()->whereRaw("CONCAT(country_code, phone) = ?", [$identifier])->first();
        } else {
            $user = User::withTrashed()->where('email', $identifier)->first();
        }
        
        return $user && $user->trashed();
    }

    /**
     * Register user with phone number
     */
    public function registerWithPhone(string $fullPhone, ?string $referalCode = null, ?string $countryCode = null, ?string $phone = null): array
    {
        try{
            // Validate required parameters
            if (!$countryCode || !$phone) {
                return [
                    'status' => self::STATUS_ERROR,
                    'message' => 'Country code and phone number are required.',
                ];
            }

            // Check if user already exists (including soft-deleted)
            $existingUser = $this->findUserByPhone($fullPhone, $countryCode, $phone);
            $softDeletedUser = null;
            
            // If no active user found, check for soft-deleted user with same phone
            if (!$existingUser) {
                $softDeletedUser = User::withTrashed()
                    ->where('country_code', $countryCode)
                    ->where('phone', $phone)
                    ->whereNotNull('deleted_at')
                    ->first();
                    
                if ($softDeletedUser) {
                    Log::info("Found soft-deleted user with same phone, allowing registration", [
                        'phone' => $fullPhone,
                        'soft_deleted_user_id' => $softDeletedUser->id
                    ]);
                }
            }
            
            if ($existingUser && $existingUser->phone_verified_at) {
                return [
                    'status' => self::STATUS_CONFLICT,
                    'message' => 'Phone number already registered and verified.',
                ];
            }

            // Try to send OTP BEFORE creating/updating user record
            $otpResult = $this->sendOtpWithErrorHandling($fullPhone, self::TYPE_PHONE, self::CONTEXT_REGISTRATION);
        
            if ($otpResult['status'] !== self::STATUS_SUCCESS) {
                return $otpResult;
            }

            // Create or update user record
            $user = $existingUser
                ? $this->updateExistingUser($existingUser, $referalCode, $countryCode, $phone)
                : $this->createNewUser($referalCode, $countryCode, $phone);

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'OTP sent to your mobile.',
                'country_code' => $user->country_code,
                'phone' => $user->phone,
            ];
        } catch (ValidationException $e) {
            return [
                'status' => self::STATUS_INVALID_REFERRAL,
                'message' => $e->errors()['referal_code'][0] ?? 'Invalid Referral Code.',
            ];
        }
        catch (Exception $e) {
            // dd($e->getMessage());
            return [
                'status' => self::STATUS_ERROR,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update existing unverified user
     */
    private function updateExistingUser(User $user, ?string $referalCode, string $countryCode, string $phone): User
    {
        try{

            $updateData = [
                'country_code' => $countryCode,
                'phone' => $phone,
            ];
            
            // if ($referalCode) {
            //     $updateData['referal_code'] = $referalCode;
            // }
    
            $referrer = null;
            if ($referalCode) {
                $referrer = User::where('referal_code', $referalCode)->first();
    
                if (!$referrer) {
                    throw ValidationException::withMessages([
                        'referal_code' => 'Invalid Referral Code.',
                    ]);
                }
                $updateData['referred_by_id'] = $referrer?->id;
                $this->checkReferralCode();
            }
            
            $user->update($updateData);
            return $user;
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'referal_code' => $e->errors()['referal_code'][0] ?? 'Invalid Referral Code.',
            ]);
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Create new user with phone registration
     */
    private function createNewUser(string $referalCode, string $countryCode, string $phone): User
    {
        $referrer = null;
        if ($referalCode) {
            $referrer = User::where('referal_code', $referalCode)->first();

            if (!$referrer) {
                throw ValidationException::withMessages([
                    'referal_code' => 'Invalid Referral Code.',
                ]);
            }

            $this->checkReferralCode();
        }

        $userData = [
            'referred_by_id'  => $referrer?->id,
            // 'referal_code' => $referalCode,
            'country_code' => $countryCode,
            'phone' => $phone,
        ];
        
        $user = User::create($userData);
        $this->assignCustomerRole($user);

        return $user;
    }

    /**
     * create voucher for Referee and Referrer
     */
    private function checkReferralCode()
    {
        try{
            $promotion = BasePromotion::with('referralPromotion')
            ->where('promotion', BasePromotion::TYPE_REFERRAL)
                ->where('valid_from','<=', now())
                ->where('valid_till' ,'>=', now())
                ->where('published',true)
                ->first();

            if (!$promotion) {
                throw ValidationException::withMessages([
                    // 'referal_code' => 'No query results for promotion model',
                    'referal_code' => "No promo code found",
                ]);
            }
        }catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'referal_code' => $e->errors(),
            ]);
        }catch (Exception $e) {
            throw $e;
        } 
    }

    /**
     * create voucher for Referee and Referrer
     */
    private function applyReferralCode($referrer_id, User $referee)
    {
        try{
            $promotion = BasePromotion::with('referralPromotion')
            ->where('promotion', BasePromotion::TYPE_REFERRAL)
                ->where('valid_from','<=', now())
                ->where('valid_till' ,'>=', now())
                ->where('published',true)
                ->first();

            if (!$promotion) {
                throw ValidationException::withMessages([
                    // 'referal_code' => 'No query results for promotion model',
                    'referal_code' => "No promo code found",
                ]);
            }

            $voucherService = new VoucherService();

            // Referee gets voucher immediately
            $voucherService->createVoucher($promotion, $referee, Voucher::TYPE_REFEREE_REWARD,);

            // Referrer gets pending reward
            $voucherService->createVoucher($promotion, User::find($referrer_id), Voucher::TYPE_REFERRER_REWARD, $referee);
        }catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'referal_code' => $e->errors(),
            ]);
        }catch (Exception $e) {
            throw $e;
        } 
    }


    /**
     * Assign customer role to user
     */
    private function assignCustomerRole(User $user): void
    {
        $customerRole = Role::where('name', self::ROLE_CUSTOMER)
                           ->where('guard_name', 'web')
                           ->first();
        
        if ($customerRole) {
            $user->assignRole($customerRole);
        }
    }

    /**
     * Attach email to existing phone-verified user
     */
    public function attachEmailToUser(string $fullPhone, string $email): array
    {
        $user = $this->findUserByIdentifier($fullPhone, self::TYPE_PHONE);

        if (!$user) {
            return [
                'status' => self::STATUS_NOT_FOUND,
                'message' => 'User not found for attaching email.',
            ];
        }

        if (!$user->phone_verified_at) {
            return [
                'status' => self::STATUS_INVALID,
                'message' => 'Phone verification required before attaching email.',
            ];
        }

        if ($user->email_verified_at) {
            return [
                'status' => self::STATUS_CONFLICT,
                'message' => 'Email already attached and verified for this user.',
            ];
        }

        // Check for existing users with this email (including soft-deleted ones)
        $existingEmailUser = User::withTrashed()->where('email', $email)->first();
        
        if ($existingEmailUser && $existingEmailUser->id !== $user->id) {
            if ($existingEmailUser->trashed()) {
                // If the existing user is soft-deleted, we can reuse the email
                Log::info("Email belongs to soft-deleted user, allowing reuse", [
                    'email' => $email,
                    'soft_deleted_user_id' => $existingEmailUser->id,
                    'current_user_id' => $user->id
                ]);
            } else {
                // Active user already has this email
                return [
                    'status' => self::STATUS_CONFLICT,
                    'message' => 'Email is already in use by another user.',
                ];
            }
        }

        $user->update(['email' => $email]);

        $otpResult = $this->sendOtpWithErrorHandling($email, self::TYPE_EMAIL, self::CONTEXT_REGISTRATION);
        
        if ($otpResult['status'] !== self::STATUS_SUCCESS) {
            return $otpResult;
        }

        return [
            'status' => self::STATUS_SUCCESS,
            'message' => 'OTP sent to your email.',
            'email' => $user->email,
        ];
    }

    /**
     * Verify registration OTP and update user verification status
     */
    public function verifyRegistrationOtp(string $identifier, string $otp, string $type, ?string $countryCode = null, ?string $phoneOnly = null): array
    {
        $user = $this->findUserByIdentifier($identifier, $type);

        if (!$user) {
            return [
                'status' => self::STATUS_NOT_FOUND,
                'message' => 'User not found.',
            ];
        }

        if ($type === self::TYPE_EMAIL && !$user->phone_verified_at) {
            return [
                'status' => self::STATUS_INVALID,
                'message' => 'Phone verification required before verifying email.',
            ];
        }

        $verificationResult = $this->otpService->verifyOtp($identifier, $otp, $type);

        if ($verificationResult === 'expired') {
            return [
                'status' => self::STATUS_INVALID,
                'message' => 'OTP expired.',
            ];
        }

        if ($verificationResult === 'mismatch') {
            return [
                'status' => self::STATUS_INVALID,
                'message' => 'Incorrect OTP.',
            ];
        }

        $this->updateUserVerificationStatus($user, $type);

        return [
            'status' => self::STATUS_SUCCESS,
            'message' => ucfirst($type) . ' verified successfully.',
            'user' => $this->formatUserData($user),
        ];
    }

    /**
     * Update user verification status based on type
     */
    private function updateUserVerificationStatus(User $user, string $type): void
    {
        if ($type === self::TYPE_PHONE) {
            $user->phone_verified_at = now();
            // Now apply referral code
            if ($user->referred_by_id != null) {
                $this->applyReferralCode($user->referred_by_id, $user);

                $referrer = User::find($user->referred_by_id);

                Mail::to($referrer->email)
                    ->send(new SignUpReferralCode($referrer));
            }
        } elseif ($type === self::TYPE_EMAIL) {
            $user->email_verified_at = now();
            $user->is_active = true;
        }

        $user->save();
    }

    /**
     * Send login OTP to user
     */
    public function sendLoginOtp(string $identifier, string $type): array
    {
        Log::info("Sending login OTP", ['identifier' => $identifier, 'type' => $type]);
        
        $user = $this->findUserByIdentifier($identifier, $type);

        if (!$user) {
            // Check if user exists but is soft deleted
            if ($this->isUserSoftDeleted($identifier, $type)) {
                Log::info("Soft deleted user attempted login", ['identifier' => $identifier, 'type' => $type]);
                return [
                    'status' => self::STATUS_NOT_FOUND,
                    'message' => 'Your account has been deleted. Please contact support if you need assistance.',
                ];
            }
            
            Log::info("User not found for login OTP", ['identifier' => $identifier, 'type' => $type]);
            return [
                'status' => self::STATUS_NOT_FOUND,
                'message' => "User with this {$type} not found.",
            ];
        }

        Log::info("User found for login OTP", ['user_id' => $user->id]);

        if (!$user->is_active) {
            return [
                'status' => self::STATUS_INACTIVE,
                'message' => 'Your account is inactive. Please contact support.',
            ];
        }

        $otpResult = $this->sendOtpWithErrorHandling($identifier, $type, self::CONTEXT_LOGIN);
        
        if ($otpResult['status'] !== self::STATUS_SUCCESS) {
            return $otpResult;
        }

        $displayType = $this->getDisplayType($type);
        return [
            'status' => self::STATUS_SUCCESS,
            'message' => "OTP sent to your {$displayType}. Please verify to complete login.",
            $type => $identifier,
        ];
    }

    public function resendOtp($identifier, $type, $context = 'login')
    {
        if ($type === 'phone') {
            // Find user by combining country_code + phone
            $user = User::whereRaw("CONCAT(country_code, phone) = ?", [$identifier])->first();
            Log::info("Resend OTP query result: " . ($user ? "Found user ID: {$user->id}" : "Not found"));
        } else {
            $user = User::where('email', $identifier)->first();
        }

        if (!$user) {
            return [
                'status' => 'not_found',
                'message' => "User with this {$type} not found.",
            ];
        }

        // Context-specific validations
        if ($context === 'login') {
            if (!$user->is_active) {
                return [
                    'status' => 'inactive',
                    'message' => 'Your account is inactive. Please contact support.',
                ];
            }
        } elseif ($context === 'registration') {
            // For registration context, allow resending even if user is not fully active
            // but check verification status based on type
            if ($type === 'phone' && $user->phone_verified_at) {
                return [
                    'status' => 'conflict',
                    'message' => 'Phone number already verified.',
                ];
            }
            if ($type === 'email' && $user->email_verified_at) {
                return [
                    'status' => 'conflict',
                    'message' => 'Email already verified.',
                ];
            }
            if ($type === 'email' && !$user->phone_verified_at) {
                return [
                    'status' => 'invalid',
                    'message' => 'Phone verification required before email verification.',
                ];
            }
        }

        $otpResult = $this->sendOtpWithErrorHandling($identifier, $type, "resend-{$context}");
        
        if ($otpResult['status'] !== 'success') {
            return $otpResult;
        }

        $displayType = $type === 'phone' ? 'mobile' : $type;
        return [
            'status' => 'success',
            'message' => "New OTP sent to your {$displayType}.",
            $type => $identifier,
        ];
    }

    public function verifyOtp($identifier, $otp, $type, $context = 'basic')
    {
        // First verify the OTP
        $verificationResult = $this->otpService->verifyOtp($identifier, $otp, $type);
logger("ennterd verifyiotp:" . json_encode($verificationResult));
        if ($verificationResult === 'expired') {
            return [
                'status' => 'invalid',
                'message' => 'OTP expired.',
            ];
        }

        if ($verificationResult === 'mismatch') {
            return [
                'status' => 'invalid',
                'message' => 'Incorrect OTP.',
            ];
        }

        // If context is 'basic', just return success without any business logic
        if ($context === 'basic') {
            return [
                'status' => 'success',
                'message' => 'OTP verified successfully.',
            ];
        }

        // For registration and login contexts, handle business logic
        // Note: We don't re-verify OTP since it's already verified above
        if ($context === 'registration') {
            return $this->handleRegistrationVerification($identifier, $type);
        }

        if ($context === 'login') {
            logger("Handling login verification for identifier: $identifier, type: $type");
            return $this->handleLoginVerification($identifier, $type);
        }

        return [
            'status' => 'invalid',
            'message' => 'Invalid context provided.',
        ];
    }

    private function handleRegistrationVerification($identifier, $type)
    {
        if ($type === 'phone') {
            // Find user by combining country_code + phone
            $user = User::whereRaw("CONCAT(country_code, phone) = ?", [$identifier])->first();
        } else {
            $user = User::where('email', $identifier)->first();
        }

        if (!$user) {
            return [
                'status' => 'not_found',
                'message' => "User with this {$type} not found.",
            ];
        }

        // Update verification status
        if ($type === 'phone') {
            $user->phone_verified_at = now();
        } else {
            $user->email_verified_at = now();
        }

        // Check if user should be activated
        if ($user->email_verified_at && $type === 'email') {
            $user->is_active = true;
        }

        $user->save();

        return [
            'status' => 'success',
            'message' => ucfirst($type) . ' verified successfully.',
            'user' => $this->formatUserData($user),
        ];
    }

    private function handleLoginVerification($identifier, $type)
    {
        if ($type === 'phone') {
            // Find user by combining country_code + phone
            $user = User::whereRaw("CONCAT(country_code, phone) = ?", [$identifier])->first();
        } else {
            $user = User::where('email', $identifier)->first();
        }

        if (!$user) {
            return [
                'status' => 'not_found',
                'message' => "User with this {$type} not found.",
            ];
        }

        if (!$user->is_active) {
            return [
                'status' => 'inactive',
                'message' => 'Your account is inactive. Please contact support.',
            ];
        }

        // Generate JWT token
        $token = JWTAuth::fromUser($user);

        // For API login, preserve guest session token (though JWT shouldn't regenerate session)
        $guestSessionToken = session()->get('guest.session_token');
        \Log::info('AuthService: Preserving guest session token for API login', [
            'user_id' => $user->id,
            'guest_session_token' => $guestSessionToken,
            'session_id' => session()->getId(),
        ]);

        // Merge any guest cart items tied to the current session into the user's cart
        \Log::info('AuthService: About to merge guest cart for API login', [
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'all_session_keys' => array_keys(session()->all()),
            'guest_token_available' => session()->has('guest.session_token'),
        ]);

        try {
            app(\App\Services\ECommerceService::class)->mergeSessionCartIntoDb($user->id);
            \Log::info('AuthService: Guest cart merge completed for API login', ['user_id' => $user->id]);
        } catch (\Throwable $e) {
            \Log::error('AuthService: Cart merge after API login failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return [
            'status' => 'success',
            'message' => 'Login successful.',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $this->formatUserData($user),
        ];
    }

    public function verifyLoginOtp($identifier, $otp, $type)
    {
        $verificationResult = $this->otpService->verifyOtp($identifier, $otp, $type);

        if ($verificationResult === 'expired') {
            return [
                'status' => 'invalid',
                'message' => 'OTP expired.',
            ];
        }

        if ($verificationResult === 'mismatch') {
            return [
                'status' => 'invalid',
                'message' => 'Incorrect OTP.',
            ];
        }

        if ($type === 'phone') {
            // Find user by combining country_code + phone
            $user = User::whereRaw("CONCAT(country_code, phone) = ?", [$identifier])->first();
        } else {
            $user = User::where('email', $identifier)->first();
        }

        if (!$user) {
            // Check if user exists but is soft deleted
            if ($this->isUserSoftDeleted($identifier, $type)) {
                return [
                    'status' => 'not_found',
                    'message' => 'Your account has been deleted. Please contact support if you need assistance.',
                ];
            }
            
            return [
                'status' => 'not_found',
                'message' => "User with this {$type} not found.",
            ];
        }

        if (!$user->is_active) {
            return [
                'status' => 'inactive',
                'message' => 'Your account is inactive. Please contact support.',
            ];
        }

        // Check if both phone and email are verified for login
        if (!$user->phone_verified_at) {
            return [
                'status' => 'verification_required',
                'message' => 'Phone verification required. Please verify your phone number first.',
            ];
        }

        if (!$user->email_verified_at) {
            return [
                'status' => 'verification_required',
                'message' => 'Email verification required. Please verify your email address first.',
            ];
        }

        // Preserve guest session token before Laravel auth regenerates session
        $guestSessionToken = session()->get('guest.session_token');
        \Log::info('AuthService: Preserving guest session token before login', [
            'user_id' => $user->id,
            'guest_session_token' => $guestSessionToken,
            'session_id_before_login' => session()->getId(),
        ]);

        // For web authentication, use Laravel's built-in session-based auth
        Auth::login($user);

         // Restore guest session token after login (Laravel may regenerate session)
        if ($guestSessionToken) {
            session()->put('guest.session_token', $guestSessionToken);
            \Log::info('AuthService: Restored guest session token after login', [
                'user_id' => $user->id,
                'guest_session_token' => $guestSessionToken,
                'session_id_after_login' => session()->getId(),
            ]);
        }

        // Merge any guest cart items tied to the current session into the user's cart
        \Log::info('AuthService: About to merge guest cart for WEB login', [
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'all_session_keys' => array_keys(session()->all()),
            'guest_token_restored' => session()->has('guest.session_token'),
        ]);

        try {
            app(\App\Services\ECommerceService::class)->mergeSessionCartIntoDb($user->id);
            \Log::info('AuthService: Guest cart merge completed for WEB login', ['user_id' => $user->id]);
        } catch (\Throwable $e) {
            \Log::error('AuthService: Cart merge after WEB login failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return [
            'status' => 'success',
            'message' => 'Login successful.',
            'user' => $this->formatUserData($user),
        ];
    }

    /**
     * Admin login with email and password
     */
    public function adminLogin(string $email, string $password): array
    {
        $user = $this->findUserByEmail($email);

        if (!$user) {
            return [
                'status' => self::STATUS_NOT_FOUND,
                'message' => 'Admin with this email not found.',
            ];
        }

        if (!Hash::check($password, $user->password)) {
            return [
                'status' => self::STATUS_UNAUTHORIZED,
                'message' => 'Invalid password.',
            ];
        }

        if (!$user->hasRole(self::ROLE_ADMIN)) {
            return [
                'status' => self::STATUS_FORBIDDEN,
                'message' => 'Access denied. User is not an admin.',
            ];
        }

        if (!$user->is_active) {
            return [
                'status' => self::STATUS_INACTIVE,
                'message' => 'Your account is inactive. Please contact support.',
            ];
        }

        return [
            'status' => self::STATUS_SUCCESS,
            'message' => 'Admin login successful.',
            'user' => $this->formatUserData($user),
        ];
    }

    /**
     * Format user data for API responses with separated country_code and phone
     */
    private function formatUserData(User $user): array
    {
        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'email' => $user->email,
            'country_code' => $user->country_code,
            'phone' => $user->phone,
            'dob' => $user->dob,
            'passport_nric_fin_number' => $user->passport_nric_fin_number,
            'image' => $user->image,
            'profile_picture' => $user->profile_picture,
            'secondary_first_name' => $user->secondary_first_name,
            'secondary_last_name' => $user->secondary_last_name,
            'secondary_email' => $user->secondary_email,
            'secondary_phone' => $user->secondary_phone,
            'secondary_country_code' => $user->secondary_country_code,
            'referal_code' => $user->referal_code,
            'is_active' => $user->is_active,
            'phone_verified_at' => $user->phone_verified_at,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        // Remove null values to keep response clean
        return array_filter($userData, fn($value) => $value !== null);
    }
}
