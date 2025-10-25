<?php

namespace App\Services;

use App\Models\User;
use App\Models\Address;
use App\Services\OtpService;
use App\Traits\SyncsWithStripe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProfileService
{
    use SyncsWithStripe;
    // Platform constants
    private const PLATFORM_CUSTOMER = 'customer';
    private const PLATFORM_ADMIN = 'admin';
    private const PLATFORM_MOBILE = 'mobile';
    
    // Type constants
    private const TYPE_EMAIL = 'email';
    private const TYPE_PHONE = 'phone';
    
    // Role constants
    private const ROLE_CUSTOMER = 'customer';
    
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';
    
    // Field sets
    private const FILLABLE_FIELDS = [
        'first_name', 'last_name', 'dob', 'passport_nric_fin_number',
        'secondary_first_name', 'secondary_last_name', 'secondary_email', 
        'secondary_phone', 'secondary_country_code'
    ];

    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Universal method to update/create user profile
     * Used by: Customer, Admin, Mobile API
     */
    public function updateOrCreateProfile(array $data, array $context = []): array
    {
         // Validate Passport/NRIC/FIN number if provided
        if (isset($data['passport_nric_fin_number'])) {
            $passportValidation = $this->validatePassportNricFinNumber($data['passport_nric_fin_number']);
            if (!$passportValidation['success']) {
                return [
                    'success' => false,
                    'message' => $passportValidation['message'],
                    'error' => $passportValidation['error']
                ];
            }
        }

        try {
            DB::beginTransaction();

            $profileContext = $this->parseContext($context);
            $user = $this->findOrCreateUser($profileContext['updating_user_id']);
            $isNewUser = !$user->exists;

            $verificationNeeds = $this->processUserChanges($user, $data, $profileContext, $isNewUser);
            $this->validateSecondaryContactDetails($user, $data);
            $this->updateUserFields($user, $data);
            $this->setUserMetadata($user, $profileContext, $isNewUser);

            $user->save();

            // Handle address updates
            if (isset($data['addresses']) && is_array($data['addresses'])) {
                $this->updateUserAddresses($user, $data['addresses']);
            }

            DB::commit();
            
            // Sync with Stripe if relevant fields were updated (only for existing users)
            if (!$isNewUser) {
                $contextString = is_array($context) ? ($context['platform'] ?? 'profile_service') : 'profile_service';
                $this->syncWithStripeIfNeeded($user, $data, $contextString);
            }

            $otpSent = $this->sendVerificationOtps($user, $verificationNeeds);

            return [
                'success' => true,
                'user' => $user->fresh(),
                'is_new_user' => $isNewUser,
                'needs_verification' => $verificationNeeds,
                'otp_sent' => $otpSent,
                'message' => $isNewUser ? 'User created successfully' : 'Profile updated successfully'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Profile update failed', [
                'error' => $e->getMessage(),
                'data' => $data,
                'context' => $context
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse context parameters
     */
    private function parseContext(array $context): array
    {
        $platform = $context['platform'] ?? self::PLATFORM_CUSTOMER;
        $updatingUserId = $context['updating_user_id'] ?? null;
        $currentUserId = $context['current_user_id'] ?? Auth::id();
        
        $isSelfUpdate = in_array($platform, [self::PLATFORM_CUSTOMER, self::PLATFORM_MOBILE]) 
                       && $currentUserId == $updatingUserId;
        
        return [
            'platform' => $platform,
            'updating_user_id' => $updatingUserId,
            'current_user_id' => $currentUserId,
            'is_self_update' => $isSelfUpdate,
            'is_admin_update' => $platform === self::PLATFORM_ADMIN
        ];
    }

    /**
     * Find existing user or create new instance
     */
    private function findOrCreateUser(?int $userId): User
    {
        return $userId ? User::findOrFail($userId) : new User();
    }

    /**
     * Process user field changes and determine verification needs
     */
    private function processUserChanges(User $user, array $data, array $context, bool $isNewUser): array
    {
        $needsEmailVerification = false;
        $needsPhoneVerification = false;

        // Email changes
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $needsEmailVerification = $context['is_admin_update'] || $isNewUser;
            $user->email = $data['email'];
            
            if (!$context['is_self_update']) {
                $user->email_verified_at = null;
            }
        }

        // Phone changes
        if ($this->isPhoneChanging($data, $user)) {
            $needsPhoneVerification = $context['is_admin_update'] || $isNewUser;
            
            if (isset($data['phone'])) {
                $user->phone = $data['phone'];
            }
            if (isset($data['country_code'])) {
                $user->country_code = $data['country_code'];
            }
            
            if (!$context['is_self_update']) {
                $user->phone_verified_at = null;
            }
        }

        return [
            'email' => $needsEmailVerification,
            'phone' => $needsPhoneVerification
        ];
    }

    /**
     * Check if phone number is changing
     */
    private function isPhoneChanging(array $data, User $user): bool
    {
        return (isset($data['phone']) && $data['phone'] !== $user->phone) || 
               (isset($data['country_code']) && $data['country_code'] !== $user->country_code);
    }

    /**
     * Update user fields from data
     */
    private function updateUserFields(User $user, array $data): void
    {
        foreach (self::FILLABLE_FIELDS as $field) {
            if (isset($data[$field])) {
                $user->$field = $data[$field];
            }
        }

        // Handle password for new users
        if (!$user->exists && isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
    }

    /**
     * Set user metadata
     */
    private function setUserMetadata(User $user, array $context, bool $isNewUser): void
    {
        $user->updated_by = $context['current_user_id'];
        $user->updated_via = $context['platform'];
        
        if ($isNewUser) {
            $user->created_by = $context['current_user_id'];
            $user->created_via = $context['platform'];
        }
    }

    /**
     * Send verification OTPs if needed
     */
    private function sendVerificationOtps(User $user, array $verificationNeeds): array
    {
        $otpSent = [];
        
        if ($verificationNeeds['email'] && $user->email) {
            try {
                $this->otpService->sendOtp($user->email, self::TYPE_EMAIL);
                $otpSent['email'] = true;
            } catch (Exception $e) {
                Log::warning('Failed to send email OTP', ['email' => $user->email, 'error' => $e->getMessage()]);
            }
        }
        
        if ($verificationNeeds['phone'] && $user->phone && $user->country_code) {
            try {
                $fullPhone = $user->country_code . $user->phone;
                $this->otpService->sendOtp($fullPhone, self::TYPE_PHONE);
                $otpSent['phone'] = true;
            } catch (Exception $e) {
                Log::warning('Failed to send phone OTP', ['phone' => $fullPhone, 'error' => $e->getMessage()]);
            }
        }

        return $otpSent;
    }

    /**
     * Verify email or phone with OTP
     */
    public function verifyWithOtp(string $type, string $otp, int $userId): array
    {
        try {
            $user = User::findOrFail($userId);
            $identifier = $this->getIdentifierForType($user, $type);
            
            if (!$identifier) {
                return ['success' => false, 'message' => 'No identifier found for verification'];
            }

            $verificationResult = $this->otpService->verifyOtp($identifier, $otp, $type);
            
            if ($verificationResult === 'success') {
                $this->updateVerificationStatus($user, $type);
                $message = $type === self::TYPE_EMAIL ? 'Email verified successfully' : 'Phone verified successfully';
                return ['success' => true, 'message' => $message];
            }

            $errorMessage = $verificationResult === 'expired' ? 'Verification code expired' : 'Invalid verification code';
            return ['success' => false, 'message' => $errorMessage];

        } catch (Exception $e) {
            Log::error('OTP verification failed', [
                'error' => $e->getMessage(),
                'type' => $type,
                'user_id' => $userId
            ]);

            return ['success' => false, 'message' => 'Verification failed'];
        }
    }

    /**
     * Get identifier for verification type
     */
    private function getIdentifierForType(User $user, string $type): ?string
    {
        if ($type === self::TYPE_EMAIL) {
            return $user->email;
        }
        
        if ($type === self::TYPE_PHONE && $user->phone && $user->country_code) {
            return $user->country_code . $user->phone;
        }
        
        return null;
    }

    /**
     * Update verification status for user
     */
    private function updateVerificationStatus(User $user, string $type): void
    {
        if ($type === self::TYPE_EMAIL) {
            $user->update([
                'email_verified_at' => now()
            ]);
        } elseif ($type === self::TYPE_PHONE) {
            $user->update([
                'phone_verified_at' => now()
            ]);
        }
    }

    /**
     * Resend OTP
     */
    public function resendOtp(string $type, int $userId): array
    {
        try {
            $user = User::findOrFail($userId);
            $identifier = $this->getIdentifierForType($user, $type);
            
            if (!$identifier) {
                return ['success' => false, 'message' => 'Unable to send verification code'];
            }

            $this->otpService->sendOtp($identifier, $type);
            $message = $type === self::TYPE_EMAIL 
                ? 'Email verification code sent' 
                : 'SMS verification code sent';
                
            return ['success' => true, 'message' => $message];

        } catch (Exception $e) {
            Log::error('OTP resend failed', [
                'error' => $e->getMessage(),
                'type' => $type,
                'user_id' => $userId
            ]);

            return ['success' => false, 'message' => 'Failed to send verification code'];
        }
    }

    /**
     * Get user profile with verification status
     */
    public function getProfileWithVerificationStatus(int $userId): array
    {
        $user = User::findOrFail($userId);
        
        return [
            'user' => $user,
            'verification_status' => [
                'email_verified' => !is_null($user->email_verified_at),
                'phone_verified' => !is_null($user->phone_verified_at)
            ]
        ];
    }

    /**
     * Update user addresses
     */
    private function updateUserAddresses(User $user, array $addresses): void
    {
        if (empty($addresses)) {
            return;
        }

        // Delete existing addresses for this user
        $user->addresses()->delete();
        
        // Create new addresses
        foreach ($addresses as $index => $addressData) {
            $user->addresses()->create([
                'address_type_id' => $addressData['address_type_id'],
                'label' => $addressData['label'] ?? null,
                'country' => $addressData['country'],
                'postal_code' => $addressData['postal_code'],
                'address_line1' => $addressData['address_line1'],
                'address_line2' => $addressData['address_line2'] ?? null,
                'is_billing_address' => $addressData['is_billing_address'] ?? false,
                'is_shipping_address' => $addressData['is_shipping_address'] ?? false,
                'is_default' => $index === 0, // First address is default
            ]);
        }
    }

    /**
     * Check if user can edit field without verification
     */
    public function canEditWithoutVerification(int $userId, int $currentUserId, string $platform): bool
    {
        return in_array($platform, [self::PLATFORM_CUSTOMER, self::PLATFORM_MOBILE]) 
               && $currentUserId === $userId;
    }

    /**
     * Send email OTP for profile update with proper business validation
     * This method should be used by both web UI and API to maintain consistency
     */
    public function sendEmailOtpForProfileUpdate(int $userId, string $newEmail): array
    {
        try {
            // First validate the email using our centralized validation method
            $validationResult = $this->validateEmailForProfileUpdate($userId, $newEmail);
            if (!$validationResult['success']) {
                return $validationResult;
            }

            // Send OTP using OtpService
            $this->otpService->sendOtp($newEmail, self::TYPE_EMAIL);

            return [
                'success' => true, 
                'message' => 'Verification code sent to ' . $newEmail,
                'sent_to' => $newEmail
            ];

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle rate limiting from OtpService
            $errors = $e->errors();
            $message = $errors['email'][0] ?? 'Please wait 60 seconds before requesting another OTP.';
            return ['success' => false, 'message' => $message];
        } catch (Exception $e) {
            Log::error('ProfileService: sendEmailOtpForProfileUpdate failed', [
                'user_id' => $userId,
                'email' => $newEmail,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Failed to send verification code.'];
        }
    }

    /**
     * Send phone OTP for profile update with proper business validation
     * This method should be used by both web UI and API to maintain consistency
     */
    public function sendPhoneOtpForProfileUpdate(int $userId, string $newPhone, string $countryCode): array
    {
        try {
            // First validate the phone using our centralized validation method
            $validationResult = $this->validatePhoneForProfileUpdate($userId, $newPhone, $countryCode);
            if (!$validationResult['success']) {
                return $validationResult;
            }

            // Format full phone number and send OTP
            $fullPhone = $countryCode . $newPhone;
            $this->otpService->sendOtp($fullPhone, self::TYPE_PHONE);

            return [
                'success' => true, 
                'message' => 'Verification code sent to ' . $fullPhone,
                'country_code' => $countryCode,
                'phone' => $newPhone
            ];

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle rate limiting from OtpService
            $errors = $e->errors();
            $message = $errors['phone'][0] ?? 'Please wait 60 seconds before requesting another OTP.';
            return ['success' => false, 'message' => $message];
        } catch (Exception $e) {
            Log::error('ProfileService: sendPhoneOtpForProfileUpdate failed', [
                'user_id' => $userId,
                'phone' => $newPhone,
                'country_code' => $countryCode,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Failed to send verification code.'];
        }
    }

    /**
     * Validate email for profile update (without sending OTP)
     * This method can be used to validate before OTP verification
     */
    public function validateEmailForProfileUpdate(int $userId, string $newEmail): array
    {
        try {
            // Validate email format
            $validator = Validator::make(['email' => $newEmail], ['email' => 'required|email|max:255']);
            if ($validator->fails()) {
                return ['success' => false, 'message' => 'Please enter a valid email address'];
            }

            // For new customer creation (userId = 0), only check if email already exists
            if ($userId === 0) {
                $existingUser = User::where('email', $newEmail)
                                    ->whereNull('deleted_at')
                                    ->first();
                
                if ($existingUser) {
                    return ['success' => false, 'message' => 'This email address is already registered. Please use a different email.'];
                }
                
                return ['success' => true, 'message' => 'Email is valid for registration'];
            }

            // For existing user updates
            $user = User::findOrFail($userId);

            // Check if email is the same as current user's email and is already verified
            if ($newEmail === $user->email) {
                if ($user->email_verified_at !== null) {
                    return ['success' => false, 'message' => 'This email is already verified for your account'];
                }
                // If email is same but not verified, allow OTP to be sent for verification
                return ['success' => true, 'message' => 'Email is valid for verification'];
            }

            // Check if new primary email is already being used as secondary email for the same user
            if ($newEmail === $user->secondary_email) {
                return ['success' => false, 'message' => 'This email is already used as your secondary email'];
            }

            // Check if email is already used by another user
            $existingUser = User::where('email', $newEmail)
                                ->where('id', '!=', $userId)
                                ->whereNull('deleted_at')
                                ->first();
            
            if ($existingUser) {
                return ['success' => false, 'message' => 'This email is already used by another user'];
            }

            return ['success' => true, 'message' => 'Email is valid for update'];

        } catch (Exception $e) {
            Log::error('ProfileService: validateEmailForProfileUpdate failed', [
                'user_id' => $userId,
                'email' => $newEmail,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Validation failed.'];
        }
    }

    /**
     * Validate phone for profile update (without sending OTP)
     * This method can be used to validate before OTP verification
     */
    public function validatePhoneForProfileUpdate(int $userId, string $newPhone, string $countryCode): array
    {
        try {
            // Validate phone format
            $validator = Validator::make(
                ['phone' => $newPhone, 'country_code' => $countryCode], 
                ['phone' => 'required|string|max:20', 'country_code' => 'required|string']
            );
            if ($validator->fails()) {
                return ['success' => false, 'message' => 'Please enter valid phone details'];
            }

            // Basic phone number validation
            if (!preg_match('/^[0-9+\-\s\(\)]+$/', $newPhone)) {
                return ['success' => false, 'message' => 'Please enter a valid phone number'];
            }

            // For new customer creation (userId = 0), only check if phone already exists
            if ($userId === 0) {
                $existingUser = User::where('phone', $newPhone)
                                    ->where('country_code', $countryCode)
                                    ->whereNull('deleted_at')
                                    ->first();
                
                if ($existingUser) {
                    return ['success' => false, 'message' => 'This mobile number is already registered. Please use a different number.'];
                }
                
                return ['success' => true, 'message' => 'Phone is valid for registration'];
            }

            // For existing user updates
            $user = User::findOrFail($userId);

            // Check if phone is the same as current user's phone and is already verified
            if ($newPhone === $user->phone && $countryCode === $user->country_code) {
                if ($user->phone_verified_at !== null) {
                    return ['success' => false, 'message' => 'This phone number is already verified for your account'];
                }
                // If phone is same but not verified, allow OTP to be sent for verification
                return ['success' => true, 'message' => 'Phone is valid for verification'];
            }

            // Check if new primary phone is already being used as secondary phone for the same user
            if ($newPhone === $user->secondary_phone && $countryCode === $user->secondary_country_code) {
                return ['success' => false, 'message' => 'This phone number is already used as your secondary phone'];
            }

            // Check if phone is already used by another user
            $existingUser = User::where('phone', $newPhone)
                                ->where('country_code', $countryCode)
                                ->where('id', '!=', $userId)
                                ->whereNull('deleted_at')
                                ->first();
            
            if ($existingUser) {
                return ['success' => false, 'message' => 'This phone number is already used by another user'];
            }

            return ['success' => true, 'message' => 'Phone is valid for update'];

        } catch (Exception $e) {
            Log::error('ProfileService: validatePhoneForProfileUpdate failed', [
                'user_id' => $userId,
                'phone' => $newPhone,
                'country_code' => $countryCode,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'message' => 'Validation failed.'];
        }
    }

    /**
     * Validate that secondary contact details don't match primary details
     */
    private function validateSecondaryContactDetails(User $user, array $data): void
    {
        // Get the primary email (either from data being updated or current user)
        $primaryEmail = $data['email'] ?? $user->email;
        // Get the primary phone (either from data being updated or current user)
        $primaryPhone = $data['phone'] ?? $user->phone;
        $primaryCountryCode = $data['country_code'] ?? $user->country_code;
        
        // Check if secondary email matches primary email
        if (!empty($data['secondary_email']) && $data['secondary_email'] === $primaryEmail) {
            throw new Exception('Secondary email cannot be the same as primary email');
        }
        
        // Check if secondary phone matches primary phone
        if (!empty($data['secondary_phone'])) {
            $secondaryCountryCode = $data['secondary_country_code'] ?? $user->secondary_country_code ?? $primaryCountryCode;
            if ($data['secondary_phone'] === $primaryPhone && $secondaryCountryCode === $primaryCountryCode) {
                throw new Exception('Secondary phone cannot be the same as primary phone');
            }
        }
    }

       /**
     * Validate Passport/NRIC/FIN Number (last 4 digits)
     * Must be exactly 4 alphanumeric characters
     */
    public function validatePassportNricFinNumber(?string $passportNumber): array
    {
        // If empty, it's valid (nullable field)
        if (empty($passportNumber)) {
            return [
                'success' => true,
                'message' => 'Valid'
            ];
        }

        // Check if exactly 4 characters
        if (strlen($passportNumber) !== 4) {
            return [
                'success' => false,
                'message' => 'Passport/NRIC/FIN number must be exactly 4 characters',
                'error' => 'invalid_length'
            ];
        }

        // Check if alphanumeric only
        if (!ctype_alnum($passportNumber)) {
            return [
                'success' => false,
                'message' => 'Passport/NRIC/FIN number must contain only letters and numbers',
                'error' => 'invalid_characters'
            ];
        }

        return [
            'success' => true,
            'message' => 'Valid Passport/NRIC/FIN number'
        ];
    }

    /**
     * Get validation rules for profile data
     */
    public function getValidationRules(bool $isUpdate = false): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255' . ($isUpdate ? '' : '|unique:users,email,NULL,id,deleted_at,NULL'),
            'country_code' => 'required|string',
            'phone' => 'required|string|max:20',
            'dob' => 'nullable|date',
            'passport_nric_fin_number' => 'nullable|string|size:4|regex:/^[A-Za-z0-9]{4}$/',
            'secondary_first_name' => 'nullable|string|max:255',
            'secondary_last_name' => 'nullable|string|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'secondary_phone' => 'nullable|string|max:50',
            'secondary_country_code' => 'nullable|string'
        ];

        if (!$isUpdate) {
            $rules['password'] = 'required|string|min:8';
        }

        return $rules;
    }

    /**
     * Get validation messages for profile data
    */
    public function getValidationMessages(): array
    {
        return [
            'passport_nric_fin_number.size' => 'Enter exactly the last 4 characters of your Passport / NRIC / FIN Number.',
            'passport_nric_fin_number.regex' => 'Enter exactly 4 alphanumeric characters (A-Z and 0-9)',
        ];
    }


    /**
     * Validate profile data
     */
    public function validateProfileData(array $data, bool $isUpdate = false): array
    {
        $rules = $this->getValidationRules($isUpdate);
        $messages = $this->getValidationMessages();
        $validator = Validator::make($data, $rules, $messages);

        return [
            'valid' => $validator->passes(),
            'errors' => $validator->errors()->toArray()
        ];
    }
}