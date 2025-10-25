<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\AuthService;
use App\Services\OtpService;
use App\Traits\HandlesAuthServiceResponses;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthModal extends Component
{
    use HandlesAuthServiceResponses;
    
    public $isOpen = false;
    public $currentStep = 'register'; // register, verify-mobile, register-success, enter-email, verify-email, email-success, login, verify-login
    public $mobileNumber = '';
    public $countryCode = '+65';
    public $referralCode = '';
    public $acceptTerms = false;
    public $otp = ['', '', '', '', '', ''];
    public $email = '';
    public $identifier = '';
    public $type = 'mobile'; // mobile or email for login
    public $statusMessage = ''; // For displaying success/error messages
    public $statusType = ''; // 'success' or 'error'

    // Keep for backward compatibility, but will use config-based countries
    public $countryCodes = [
        '+65' => ['flag' => '🇸🇬', 'name' => 'Singapore'],
        '+60' => ['flag' => '🇲🇾', 'name' => 'Malaysia'],
        '+1' => ['flag' => '🇺🇸', 'name' => 'United States'],
        '+44' => ['flag' => '🇬🇧', 'name' => 'United Kingdom'],
        '+91' => ['flag' => '🇮🇳', 'name' => 'India'],
        '+880' => ['flag' => '🇧🇩', 'name' => 'Bangladesh'],
        '+86' => ['flag' => '🇨🇳', 'name' => 'China'],
        '+81' => ['flag' => '🇯🇵', 'name' => 'Japan'],
        '+82' => ['flag' => '🇰🇷', 'name' => 'South Korea'],
        '+61' => ['flag' => '🇦🇺', 'name' => 'Australia'],
        '+49' => ['flag' => '🇩🇪', 'name' => 'Germany']
    ];
    
    // New properties for enhanced phone input
    public $phone_login_country = 'SG'; // Hidden field from phone component
    public $phone_login_full = '';      // Hidden field with full phone number
    public $mobileNumber_full = '';     // Full phone number from component



    public function mount($initialStep = null)
    {
        if ($initialStep) {
            // If we have an initial step, open the modal with that step
            $this->isOpen = true;
            $this->currentStep = $initialStep;
        } else {
            // Modal starts closed by default
            $this->isOpen = false;
            $this->currentStep = 'register';
        }
    }

    #[On('openModal')]
    public function openModal($type = 'register')
    {
        $this->isOpen = true;
        $this->currentStep = $type;
        $this->resetForm();
    }



    public function closeModal()
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->mobileNumber = '';
        $this->referralCode = '';
        $this->acceptTerms = false;
        $this->phone_login_country = 'SG';
        $this->phone_login_full = '';
        $this->otp = ['', '', '', '', '', ''];
        $this->email = '';
        $this->identifier = '';
        $this->statusMessage = '';
        $this->statusType = '';
        
        // Clear all validation errors
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // This method is called when the 'type' property is updated
    public function updatedType()
    {
        // Clear validation errors when switching between mobile and email login
        $this->resetErrorBag();
        $this->resetValidation();
        
        // Clear the input fields
        $this->mobileNumber = '';
        $this->identifier = '';
    }

    // This method is called when any OTP field is updated
    public function updatedOtp()
    {
        // Clear status message when user starts typing OTP
        $this->statusMessage = '';
        $this->statusType = '';
    }

    // Clear status messages when user types in mobile number
    public function updatedMobileNumber()
    {
        $this->statusMessage = '';
        $this->statusType = '';
    }

    // Clear status messages when user types in email
    public function updatedEmail()
    {
        $this->statusMessage = '';
        $this->statusType = '';
    }

    // Clear status messages when user types in identifier (login)
    public function updatedIdentifier()
    {
        $this->statusMessage = '';
        $this->statusType = '';
    }

    // Track country code changes
    public function updatedCountryCode()
    {
        Log::info('Country code updated in Livewire', [
            'new_country_code' => $this->countryCode,
            'mobile_number' => $this->mobileNumber
        ]);
        $this->statusMessage = '';
        $this->statusType = '';
    }

    public function sendRegistrationOtp()
    {
        Log::info('Registration OTP request started', [
            'mobile' => $this->mobileNumber,
            'country_code' => $this->countryCode,
            'terms_accepted' => $this->acceptTerms,
        ]);

        // Validate mobile number - removed minimum length requirement to support countries with shorter phone numbers
        $maxLength = 15;
        
        $this->validate([
            'mobileNumber' => "required|string|max:{$maxLength}|regex:/^[0-9]+$/",
            'acceptTerms' => 'accepted',
        ], [
            'mobileNumber.max' => "Mobile number cannot exceed {$maxLength} digits.",
            'mobileNumber.regex' => 'Mobile number must contain only digits.',
            'acceptTerms.accepted' => 'You must accept the terms and conditions.',
        ]);

        try {
            $fullPhone = $this->countryCode . $this->mobileNumber;
            
            // Use AuthService for consistent registration logic
            $authService = app(AuthService::class);
            $result = $authService->registerWithPhone(
                $fullPhone, 
                $this->referralCode, 
                $this->countryCode, 
                $this->mobileNumber
            );
            
            if ($result['status'] === 'success') {
                // Store registration data for verification step
                session([
                    'livewire_registration_phone' => $fullPhone,
                    'livewire_registration_country_code' => $this->countryCode,
                    'livewire_registration_mobile' => $this->mobileNumber,
                    'livewire_registration_referral' => $this->referralCode
                ]);
                
                $this->currentStep = 'verify-mobile';
                $this->statusMessage = $result['message'];
                $this->statusType = 'success';
            } else {
                // Handle errors using the trait's error mapping
                $errorField = $this->mapErrorStatusToField($result['status']);
                
                Log::info('Registration failed', [
                    'status' => $result['status'],
                    'message' => $result['message'],
                    'mapped_field' => $errorField
                ]);
                
                if ($errorField === 'phone') {
                    $this->addError('mobileNumber', $result['message']);
                } elseif ($errorField === 'referal_code') {
                    $this->addError('referralCode', $result['message']);
                } else {
                    $this->addError('mobileNumber', $result['message']);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Registration OTP send failed: ' . $e->getMessage());
            $this->addError('mobileNumber', 'Failed to send OTP. Please try again.');
        }
    }



    public function verifyMobileOtp()
    {
        // Clear previous errors and status messages
        $this->resetErrorBag();
        $this->statusMessage = '';
        $this->statusType = '';
        
        $otpString = implode('', $this->otp);
        
        // Validate that we have a 6-digit OTP
        if (strlen($otpString) !== 6 || !ctype_digit($otpString)) {
            $this->addError('otp', 'Please enter a valid 6-digit OTP.');
            $this->resetOtp();
            $this->dispatch('otp-error-clear-and-focus');
            return;
        }

        try {
            $fullPhone = session('livewire_registration_phone');
            $countryCode = session('livewire_registration_country_code');
            $mobileNumber = session('livewire_registration_mobile');
            
            // Use AuthService for consistent verification logic
            $authService = app(AuthService::class);
            $result = $authService->verifyRegistrationOtp(
                $fullPhone, 
                $otpString, 
                'phone', 
                $countryCode, 
                $mobileNumber
            );
            
            if ($result['status'] === 'success') {
                // Store user ID for email attachment step
                session(['livewire_phone_verified_user_id' => $result['user']['id']]);
                
                $this->resetOtp(); // Only reset on success
                $this->currentStep = 'register-success';
                $this->statusMessage = 'Mobile number verified successfully!';
                $this->statusType = 'success';
            } else {
                $this->addError('otp', $result['message']);
                $this->resetOtp();
                $this->dispatch('otp-error-clear-and-focus');
            }
        } catch (\Exception $e) {
            Log::error('Mobile OTP verification failed: ' . $e->getMessage());
            $this->addError('otp', 'Verification failed. Please try again.');
            $this->resetOtp();
            $this->dispatch('otp-error-clear-and-focus');
        }
    }

    public function continueToEmail()
    {
        $this->currentStep = 'enter-email';
        // Don't reset OTP here - it's for navigation between steps
    }

    public function sendEmailOtp()
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        Log::info('Email OTP request started', [
            'email' => $this->email,
            'has_phone_verified_user_id' => session()->has('livewire_phone_verified_user_id'),
            'has_registration_phone' => session()->has('livewire_registration_phone'),
            'phone_verified_user_id' => session('livewire_phone_verified_user_id'),
            'registration_phone' => session('livewire_registration_phone')
        ]);

        try {
            // Try to get user from phone verification session first
            $userId = session('livewire_phone_verified_user_id');
            $fullPhone = session('livewire_registration_phone');
            
            if ($userId) {
                // User completed phone verification - use user ID
                $user = User::find($userId);
                if (!$user) {
                    $this->addError('email', 'User not found.');
                    return;
                }
                $phoneForAttachment = $user->full_phone;
            } elseif ($fullPhone) {
                // User hasn't completed phone verification - use phone from registration session
                $phoneForAttachment = $fullPhone;
            } else {
                $this->addError('email', 'Please complete phone registration first.');
                return;
            }

            // Use AuthService for consistent email attachment logic
            $authService = app(AuthService::class);
            $result = $authService->attachEmailToUser($phoneForAttachment, $this->email);
            
            if ($result['status'] === 'success') {
                session(['livewire_registration_email' => $this->email]);
                $this->currentStep = 'verify-email';
                $this->statusMessage = $result['message'];
                $this->statusType = 'success';
            } else {
                $this->addError('email', $result['message']);
            }
            
        } catch (\Exception $e) {
            Log::error('Email OTP send failed: ' . $e->getMessage());
            $this->addError('email', 'Failed to send OTP. Please try again.');
        }
    }

    public function verifyEmailOtp()
    {
        // Clear previous errors and status messages
        $this->resetErrorBag();
        $this->statusMessage = '';
        $this->statusType = '';
        
        $otpString = implode('', $this->otp);
        
        // Validate that we have a 6-digit OTP
        if (strlen($otpString) !== 6 || !ctype_digit($otpString)) {
            $this->addError('otp', 'Please enter a valid 6-digit OTP.');
            $this->resetOtp();
            $this->dispatch('otp-error-clear-and-focus');
            return;
        }
        
        try {
            $email = session('livewire_registration_email');
            
            // Use AuthService for consistent email verification logic
            $authService = app(AuthService::class);
            $result = $authService->verifyRegistrationOtp($email, $otpString, 'email');
            
            if ($result['status'] === 'success') {
                $this->resetOtp(); // Only reset on success
                $this->currentStep = 'email-success';
                $this->statusMessage = 'Email verified successfully!';
                $this->statusType = 'success';
                
                // Clean up session data
                session()->forget([
                    'livewire_registration_phone',
                    'livewire_registration_country_code', 
                    'livewire_registration_mobile',
                    'livewire_registration_referral',
                    'livewire_registration_email',
                    'livewire_phone_verified_user_id'
                ]);
            } else {
                $this->addError('otp', $result['message']);
                $this->resetOtp();
                $this->dispatch('otp-error-clear-and-focus');
            }
        } catch (\Exception $e) {
            Log::error('Email OTP verification failed: ' . $e->getMessage());
            $this->addError('otp', 'Verification failed. Please try again.');
            $this->resetOtp();
            $this->dispatch('otp-error-clear-and-focus');
        }
    }



    public function completeRegistration()
    {
        // Complete registration and redirect to dashboard
        $this->closeModal();
        $this->redirectRoute('customer.dashboard');
    }

    public function sendLoginOtp()
    {
        Log::info('Login OTP request started', [
            'type' => $this->type,
            'mobile' => $this->mobileNumber,
            'phone_full' => $this->phone_login_full,
            'mobileNumber_full' => $this->mobileNumber_full,
            'identifier' => $this->identifier
        ]);

        if ($this->type === 'mobile') {
            // Basic validation for mobile number - removed minimum length to support shorter phone numbers
            $this->validate([
                'mobileNumber' => 'required|string|max:15|regex:/^[0-9]+$/',
                'countryCode' => 'required|string',
            ], [
                'mobileNumber.required' => 'Please enter your mobile number.',
                'mobileNumber.max' => 'Mobile number cannot exceed 15 digits.',
                'mobileNumber.regex' => 'Mobile number must contain only digits.',
                'countryCode.required' => 'Please select a country code.',
            ]);
            
            // Construct full phone number
            $this->identifier = $this->countryCode . $this->mobileNumber;
        } else {
            $this->validate([
                'identifier' => 'required|email',
            ]);
        }

        try {
            // Use AuthService for consistent login OTP logic
            $authService = app(AuthService::class);
            $loginType = $this->type === 'mobile' ? 'phone' : 'email';
            $result = $authService->sendLoginOtp($this->identifier, $loginType);
            
            if ($result['status'] === 'success') {
                // Store login data for verification step
                session([
                    'livewire_login_identifier' => $this->identifier,
                    'livewire_login_type' => $loginType
                ]);
                
                $this->currentStep = 'verify-login';
                $this->statusMessage = $result['message'];
                $this->statusType = 'success';
            } else {
                // Handle errors using the trait's error mapping
                $field = $this->type === 'mobile' ? 'mobileNumber' : 'identifier';
                $this->addError($field, $result['message']);
            }
            
        } catch (\Exception $e) {
            Log::error('Login OTP send failed: ' . $e->getMessage());
            $field = $this->type === 'mobile' ? 'mobileNumber' : 'identifier';
            $this->addError($field, 'Failed to send OTP. Please try again.');
        }
    }

    public function testMethod()
    {
        $this->statusMessage = 'Test method called successfully!';
        $this->statusType = 'success';
    }

    public function verifyLoginOtp()
    {
        Log::info('AuthModal: verifyLoginOtp method called', [
            'identifier' => $this->identifier,
            'type' => $this->type
        ]);
        
        // Clear previous errors and status messages
        $this->resetErrorBag();
        $this->statusMessage = '';
        $this->statusType = '';
        
        $otpString = implode('', $this->otp);
        
        // Log the OTP values for debugging
        Log::info('AuthModal: OTP verification attempt', [
            'otp_array' => $this->otp,
            'otp_string' => $otpString,
            'identifier' => $this->identifier,
            'type' => $this->type
        ]);
        
        // Validate that we have a 6-digit OTP
        if (strlen($otpString) !== 6 || !ctype_digit($otpString)) {
            $this->addError('otp', 'Please enter a valid 6-digit OTP.');
            $this->resetOtp();
            $this->dispatch('otp-error-clear-and-focus');
            return;
        }
        
        try {
            $authService = app(AuthService::class);
            $otpType = $this->type === 'mobile' ? 'phone' : 'email';
            
            // Use AuthService to verify OTP and login user
            $result = $authService->verifyLoginOtp($this->identifier, $otpString, $otpType);
            
            Log::info('AuthModal: AuthService result', [
                'result' => $result
            ]);
            
            if ($result['status'] === 'success') {
                // Successful login
                Log::info('Login successful via Livewire AuthModal - attempting redirect');
                $this->resetOtp(); // Only reset on success
                $this->closeModal();
                
                // Try multiple redirect approaches
                Log::info('Attempting redirect to dashboard');
                
                // Method 1: Livewire redirect
                $this->redirectRoute('customer.dashboard');
                
                // Method 2: JavaScript redirect as backup
                $this->dispatch('redirectToDashboard');
            } elseif ($result['status'] === 'invalid') {
                if (strpos($result['message'], 'expired') !== false) {
                    $this->addError('otp', 'OTP has expired. Please request a new one.');
                } else {
                    $this->addError('otp', $result['message']);
                }
                $this->resetOtp();
                $this->dispatch('otp-error-clear-and-focus');
            } elseif ($result['status'] === 'not_found') {
                $this->addError('otp', 'User not found. Please register first.');
                $this->resetOtp();
                $this->dispatch('otp-error-clear-and-focus');
            } elseif ($result['status'] === 'inactive') {
                $this->addError('otp', 'Your account is inactive. Please contact support.');
                $this->resetOtp();
                $this->dispatch('otp-error-clear-and-focus');
            } elseif ($result['status'] === 'verification_required') {
                $this->addError('otp', $result['message']);
                $this->resetOtp();
                $this->dispatch('otp-error-clear-and-focus');
            } else {
                $this->addError('otp', 'Login failed. Please try again.');
                $this->resetOtp();
                $this->dispatch('otp-error-clear-and-focus');
            }
        } catch (\Exception $e) {
            Log::error('Login OTP verification failed: ' . $e->getMessage());
            $this->addError('otp', 'Verification failed. Please try again.');
            $this->resetOtp();
            $this->dispatch('otp-error-clear-and-focus');
        }
    }

    public function resendOtp()
    {
        // Clear previous errors and status messages
        $this->resetErrorBag();
        $this->statusMessage = '';
        $this->statusType = '';
        
        try {
            $identifier = '';
            $otpType = '';
            $context = '';
            
            // Determine the identifier, type, and context based on current step
            if ($this->currentStep === 'verify-mobile') {
                $identifier = session('livewire_registration_phone');
                $otpType = 'phone';
                $context = 'registration';
            } elseif ($this->currentStep === 'verify-email') {
                $identifier = session('livewire_registration_email');
                $otpType = 'email';
                $context = 'registration';
            } elseif ($this->currentStep === 'verify-login') {
                $identifier = session('livewire_login_identifier');
                $otpType = session('livewire_login_type');
                $context = 'login';
            } else {
                throw new \Exception('Invalid step for OTP resend');
            }
            
            // Use AuthService for consistent resend logic
            $authService = app(AuthService::class);
            $result = $authService->resendOtp($identifier, $otpType, $context);
            
            if ($result['status'] === 'success') {
                $this->statusMessage = $result['message'];
                $this->statusType = 'success';
            } else {
                $this->statusMessage = $result['message'];
                $this->statusType = 'error';
            }
            
        } catch (\Exception $e) {
            Log::error('Resend OTP failed: ' . $e->getMessage());
            $this->statusMessage = 'Failed to resend OTP. Please try again.';
            $this->statusType = 'error';
        }
    }

    public function resetOtp()
    {
        $this->otp = ['', '', '', '', '', ''];
    }

    public function goBack()
    {
        switch ($this->currentStep) {
            case 'verify-mobile':
                $this->currentStep = 'register';
                break;
            case 'register-success':
                $this->currentStep = 'verify-mobile';
                break;
            case 'enter-email':
                $this->currentStep = 'register-success';
                break;
            case 'verify-email':
                $this->currentStep = 'enter-email';
                break;
            case 'verify-login':
                $this->currentStep = 'login';
                break;
            default:
                $this->currentStep = 'register';
        }
        $this->resetOtp();
        // Clear validation errors when navigating back
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function switchToLogin()
    {
        $this->currentStep = 'login';
        $this->resetForm();
    }

    public function switchToRegister()
    {
        $this->currentStep = 'register';
        $this->resetForm();
    }

    public function render()
    {
        // Ensure email property is synced with session when on verify-email step
        if ($this->currentStep === 'verify-email' && session('livewire_registration_email')) {
            $this->email = session('livewire_registration_email');
        }
        
        // Ensure phone properties are synced with session when on verify-mobile step
        if ($this->currentStep === 'verify-mobile') {
            if (session('livewire_registration_country_code')) {
                $this->countryCode = session('livewire_registration_country_code');
            }
            if (session('livewire_registration_mobile')) {
                $this->mobileNumber = session('livewire_registration_mobile');
            }
        }
        
        // Ensure login properties are synced with session when on verify-login step
        if ($this->currentStep === 'verify-login') {
            if (session('livewire_login_type') === 'email' && session('livewire_login_identifier')) {
                $this->email = session('livewire_login_identifier');
            } elseif (session('livewire_login_type') === 'phone' && session('livewire_login_identifier')) {
                // For phone, extract country code and mobile number from full phone
                $fullPhone = session('livewire_login_identifier');
                
                // Define common country codes for better extraction
                $commonCountryCodes = ['+1', '+7', '+20', '+27', '+30', '+31', '+32', '+33', '+34', '+36', '+39', '+40', '+41', '+43', '+44', '+45', '+46', '+47', '+48', '+49', '+51', '+52', '+53', '+54', '+55', '+56', '+57', '+58', '+60', '+61', '+62', '+63', '+64', '+65', '+66', '+81', '+82', '+84', '+86', '+90', '+91', '+92', '+93', '+94', '+95', '+98'];
                
                // Try to match against common country codes first
                foreach ($commonCountryCodes as $code) {
                    if (str_starts_with($fullPhone, $code)) {
                        $this->countryCode = $code;
                        $this->mobileNumber = substr($fullPhone, strlen($code));
                        break;
                    }
                }
                
                // Fallback to regex if no common country code matched
                if (empty($this->countryCode) && preg_match('/^(\+\d{1,4})(.+)$/', $fullPhone, $matches)) {
                    $this->countryCode = $matches[1];
                    $this->mobileNumber = $matches[2];
                }
            }
        }
        
        return view('livewire.frontend.auth-modal');
    }
}