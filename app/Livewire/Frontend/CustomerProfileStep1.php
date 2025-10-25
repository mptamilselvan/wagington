<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\OtpService;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerProfileStep1 extends Component
{
    use WithFileUploads;

    // Profile data
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone = '';
    public $country_code = '+65';
    public $dob = '';
    public $passport_nric_fin_number = '';
    public $profile_picture;
    
    // OTP verification
    public $showOtpModal = false;
    public $otpType = '';
    public $otpDigits = ['', '', '', '', '', ''];
    public $otpMessage = '';
    public $otpMessageType = '';
    public $pendingEmail = '';
    public $pendingPhone = '';
    public $pendingCountryCode = '';
    
    // Edit mode tracking
    public $emailEditMode = false;
    public $phoneEditMode = false;
    
    protected $otpService;
    protected $customerService;
    protected $profileService;

    public function boot()
    {
        $this->otpService = app(OtpService::class);
        $this->customerService = app(CustomerService::class);
        $this->profileService = app(\App\Services\ProfileService::class);
    }

    public function mount()
    {
        // Always fetch fresh user data from database to ensure we have verified values
        $user = Auth::user()->fresh();
        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->country_code = $user->country_code ?? '+65';
        $this->dob = $user->dob ? $user->dob->format('Y-m-d') : '';
        $this->passport_nric_fin_number = $user->passport_nric_fin_number ?? '';
    }

    public function toggleEmailEdit()
    {
        $this->emailEditMode = !$this->emailEditMode;
        
        if (!$this->emailEditMode) {
            // If canceling edit, restore original email from database
            $this->email = Auth::user()->fresh()->email ?? '';
        }
    }

    public function togglePhoneEdit()
    {
        $this->phoneEditMode = !$this->phoneEditMode;
        
        if (!$this->phoneEditMode) {
            // If canceling edit, restore original phone and country code from database
            $user = Auth::user()->fresh();
            $this->phone = $user->phone ?? '';
            $this->country_code = $user->country_code ?? '+65';
        }
    }

    public function sendEmailOtp()
    {
        // Clear any existing errors
        $this->resetErrorBag('email');
        
        if (empty($this->email)) {
            $this->addError('email', 'Email is required to send OTP');
            return;
        }

        try {
            // Use ProfileService method that includes all business validation
            $result = $this->profileService->sendEmailOtpForProfileUpdate(Auth::id(), $this->email);
            
            if ($result['success']) {
                // Store the pending email for verification
                $this->pendingEmail = $this->email;
                
                // Reset OTP state
                $this->otpDigits = ['', '', '', '', '', ''];
                $this->otpMessage = '';
                $this->otpMessageType = '';
                
                $this->otpType = 'email';
                $this->showOtpModal = true;
                $this->otpMessage = 'OTP sent to ' . $this->email;
                $this->otpMessageType = 'success';
                
                // Dispatch event to initialize OTP modal
                $this->dispatch('otp-modal-opened');
                
                Log::info('Email OTP sent successfully for customer', [
                    'user_id' => Auth::id(),
                    'email' => $this->email
                ]);
            } else {
                $this->addError('email', $result['message']);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to send email OTP for customer: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'email' => $this->email
            ]);
            $this->addError('email', 'Failed to send OTP. Please try again.');
        }
    }

    public function sendPhoneOtp()
    {
        // Clear any existing errors
        $this->resetErrorBag('phone');
        
        if (empty($this->phone)) {
            $this->addError('phone', 'Phone number is required to send OTP');
            return;
        }

        if (empty($this->country_code)) {
            $this->addError('phone', 'Country code is required to send OTP');
            return;
        }

        try {
            // Use ProfileService method that includes all business validation
            $result = $this->profileService->sendPhoneOtpForProfileUpdate(Auth::id(), $this->phone, $this->country_code);
            
            if ($result['success']) {
                // Store the pending phone for verification
                $this->pendingPhone = $this->phone;
                $this->pendingCountryCode = $this->country_code;
                
                // Format full phone number for display
                $fullPhoneNumber = $this->country_code . $this->phone;
                
                // Reset OTP state
                $this->otpDigits = ['', '', '', '', '', ''];
                $this->otpMessage = '';
                $this->otpMessageType = '';
                
                $this->otpType = 'sms';
                $this->showOtpModal = true;
                $this->otpMessage = 'OTP sent to ' . $fullPhoneNumber;
                $this->otpMessageType = 'success';
                
                // Dispatch event to initialize OTP modal
                $this->dispatch('otp-modal-opened');
                
                Log::info('Phone OTP sent successfully for customer', [
                    'user_id' => Auth::id(),
                    'phone' => $fullPhoneNumber
                ]);
            } else {
                $this->addError('phone', $result['message']);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to send phone OTP for customer: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'phone' => $this->country_code . $this->phone
            ]);
            $this->addError('phone', 'Failed to send OTP. Please try again.');
        }
    }

    public function verifyOtp()
    {
        $otpCode = implode('', $this->otpDigits);
        
        Log::info('CustomerProfileStep1: verifyOtp called', [
            'user_id' => Auth::id(),
            'otpType' => $this->otpType,
            'pendingEmail' => $this->pendingEmail,
            'pendingPhone' => $this->pendingPhone,
            'pendingCountryCode' => $this->pendingCountryCode,
            'otpCode' => $otpCode,
            'otpDigits' => $this->otpDigits
        ]);
        
        if (strlen($otpCode) !== 6 || !ctype_digit($otpCode)) {
            $this->otpMessage = 'Please enter a valid 6-digit OTP.';
            $this->otpMessageType = 'error';
            // Clear all OTP digits and focus on first box
            $this->otpDigits = ['', '', '', '', '', ''];
            $this->dispatch('otp-error-clear-and-focus');
            return;
        }

        try {
            if ($this->otpType === 'email') {
                // Verify email OTP
                $result = $this->otpService->verifyOtp($this->pendingEmail, $otpCode, 'email');
                
                Log::info('CustomerProfileStep1: Email OTP verification result', [
                    'user_id' => Auth::id(),
                    'pendingEmail' => $this->pendingEmail,
                    'otpCode' => $otpCode,
                    'result' => $result
                ]);
                
                if ($result === 'success') {
                    // Update only the verified email in database immediately
                    $updateResult = $this->customerService->updateVerifiedEmail(Auth::id(), $this->pendingEmail);
                    
                    Log::info('CustomerProfileStep1: Email update result after OTP verification', [
                        'user_id' => Auth::id(),
                        'pendingEmail' => $this->pendingEmail,
                        'updateResult' => $updateResult
                    ]);
                    
                    if ($updateResult['status'] === 'success') {
                        // Update the authenticated user instance
                        if (isset($updateResult['user']) && $updateResult['user'] instanceof \App\Models\User) {
                            Auth::setUser($updateResult['user']);
                        }
                        
                        // Update component state
                        $this->email = $this->pendingEmail;
                        $this->emailEditMode = false;
                        
                        // Close modal and show success
                        $this->closeOtpModal();
                        session()->flash('success', 'Email address updated and verified successfully!');
                        
                        Log::info('Email verification and update successful', [
                            'user_id' => Auth::id(),
                            'new_email' => $this->pendingEmail
                        ]);
                    } else {
                        $this->otpMessage = $updateResult['message'] ?? 'Failed to update email address';
                        $this->otpMessageType = 'error';
                        
                        if (isset($updateResult['errors'])) {
                            Log::error('Email update failed after OTP verification', [
                                'user_id' => Auth::id(),
                                'errors' => $updateResult['errors']
                            ]);
                        }
                    }
                } else {
                    $this->otpMessage = 'Invalid OTP. Please try again.';
                    $this->otpMessageType = 'error';
                    // Clear all OTP digits and focus on first box
                    $this->otpDigits = ['', '', '', '', '', ''];
                    $this->dispatch('otp-error-clear-and-focus');
                }
                
            } elseif ($this->otpType === 'sms') {
                // Verify phone OTP
                $fullPhoneNumber = $this->pendingCountryCode . $this->pendingPhone;
                $result = $this->otpService->verifyOtp($fullPhoneNumber, $otpCode, 'phone');
                
                Log::info('CustomerProfileStep1: Phone OTP verification result', [
                    'user_id' => Auth::id(),
                    'pendingPhone' => $fullPhoneNumber,
                    'otpCode' => $otpCode,
                    'result' => $result
                ]);
                
                if ($result === 'success') {
                    // Update only the verified phone in database immediately
                    $updateResult = $this->customerService->updateVerifiedPhone(Auth::id(), $this->pendingPhone, $this->pendingCountryCode);
                    
                    Log::info('CustomerProfileStep1: Phone update result after OTP verification', [
                        'user_id' => Auth::id(),
                        'pendingPhone' => $fullPhoneNumber,
                        'updateResult' => $updateResult
                    ]);
                    
                    if ($updateResult['status'] === 'success') {
                        // Update the authenticated user instance
                        if (isset($updateResult['user']) && $updateResult['user'] instanceof \App\Models\User) {
                            Auth::setUser($updateResult['user']);
                        }
                        
                        // Update component state
                        $this->phone = $this->pendingPhone;
                        $this->country_code = $this->pendingCountryCode;
                        $this->phoneEditMode = false;
                        
                        // Close modal and show success
                        $this->closeOtpModal();
                        session()->flash('success', 'Phone number updated and verified successfully!');
                        
                        Log::info('Phone verification and update successful', [
                            'user_id' => Auth::id(),
                            'new_phone' => $fullPhoneNumber
                        ]);
                    } else {
                        $this->otpMessage = $updateResult['message'] ?? 'Failed to update phone number';
                        $this->otpMessageType = 'error';
                        
                        if (isset($updateResult['errors'])) {
                            Log::error('Phone update failed after OTP verification', [
                                'user_id' => Auth::id(),
                                'errors' => $updateResult['errors']
                            ]);
                        }
                    }
                } else {
                    $this->otpMessage = 'Invalid OTP. Please try again.';
                    $this->otpMessageType = 'error';
                    // Clear all OTP digits and focus on first box
                    $this->otpDigits = ['', '', '', '', '', ''];
                    $this->dispatch('otp-error-clear-and-focus');
                }
            }
            
        } catch (\Exception $e) {
            $contactType = $this->otpType === 'email' ? 'email' : 'phone';
            Log::error('OTP verification failed for customer: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'type' => $contactType,
                'contact' => $this->otpType === 'email' ? $this->pendingEmail : ($this->pendingCountryCode . $this->pendingPhone)
            ]);
            $this->otpMessage = 'OTP verification failed. Please try again.';
            $this->otpMessageType = 'error';
            // Clear all OTP digits and focus on first box
            $this->otpDigits = ['', '', '', '', '', ''];
            $this->dispatch('otp-error-clear-and-focus');
        }
    }

    public function closeOtpModal()
    {
        $this->showOtpModal = false;
        $this->otpDigits = ['', '', '', '', '', ''];
        $this->otpMessage = '';
        $this->otpMessageType = '';
        $this->otpType = '';
        $this->pendingEmail = '';
        $this->pendingPhone = '';
        $this->pendingCountryCode = '';
    }

    public function resendOtp()
    {
        if ($this->otpType === 'email' && !empty($this->pendingEmail)) {
            try {
                $result = $this->profileService->sendEmailOtpForProfileUpdate(Auth::id(), $this->pendingEmail);
                
                if ($result['success']) {
                    $this->otpMessage = 'OTP resent to ' . $this->pendingEmail;
                    $this->otpMessageType = 'success';
                    $this->otpDigits = ['', '', '', '', '', ''];
                } else {
                    $this->otpMessage = $result['message'] ?? 'Failed to resend OTP';
                    $this->otpMessageType = 'error';
                }
            } catch (\Exception $e) {
                $this->otpMessage = 'Failed to resend OTP. Please try again.';
                $this->otpMessageType = 'error';
            }
        } elseif ($this->otpType === 'sms' && !empty($this->pendingPhone) && !empty($this->pendingCountryCode)) {
            try {
                $result = $this->profileService->sendPhoneOtpForProfileUpdate(Auth::id(), $this->pendingPhone, $this->pendingCountryCode);
                
                if ($result['success']) {
                    $fullPhoneNumber = $this->pendingCountryCode . $this->pendingPhone;
                    $this->otpMessage = 'OTP resent to ' . $fullPhoneNumber;
                    $this->otpMessageType = 'success';
                    $this->otpDigits = ['', '', '', '', '', ''];
                } else {
                    $this->otpMessage = $result['message'] ?? 'Failed to resend OTP';
                    $this->otpMessageType = 'error';
                }
            } catch (\Exception $e) {
                $this->otpMessage = 'Failed to resend OTP. Please try again.';
                $this->otpMessageType = 'error';
            }
        }
    }

    public function saveProfile()
    {
        // Validate form data
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20|regex:/^[0-9]{5,20}$/',
            'country_code' => 'required|string|max:10',
            'dob' => 'required|date|before:today',
            'passport_nric_fin_number' => 'required|string|size:4|regex:/^[a-zA-Z0-9]{4}$/',
            'profile_picture' => 'nullable|image|max:2048'
        ], [
            'phone.required' => 'The Mobile Number field is required.',
            'phone.regex' => 'The Mobile Number must contain 5 to 20 digits.',
            'phone.max' => 'The Mobile Number must not exceed 20 digits.',
            'dob.required' => 'The Date of Birth field is required.',
            'dob.date' => 'Please enter a valid Date of Birth.',
            'dob.before' => 'Date of Birth must be in the past.',
            'passport_nric_fin_number.required' => 'The Passport / NRIC / FIN Number field is required.',
            'passport_nric_fin_number.size' => 'Enter exactly the last 4 characters of your Passport / NRIC / FIN Number.',
            'passport_nric_fin_number.regex' => 'Enter exactly 4 alphanumeric characters (A-Z and 0-9)',
        ], [
            'phone' => 'Mobile Number',
            'dob' => 'Date of Birth',
            'passport_nric_fin_number' => 'Passport / NRIC / FIN Number',
        ]);

        try {
            $updateData = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'dob' => $this->dob,
                'passport_nric_fin_number' => $this->passport_nric_fin_number,
            ];

            // Handle profile picture upload if present
            if ($this->profile_picture) {
                $url = \App\Services\ImageService::uploadCustomerProfileImage($this->profile_picture, Auth::id());
                if ($url) {
                    $updateData['image'] = $url;
                }
            }

            // Use CustomerService to update only basic info (excluding email/phone)
            // Email/phone are only updated through OTP verification
            $result = $this->customerService->updateCustomerProfileBasicInfo(Auth::id(), $updateData, 'web');
            
            Log::info('CustomerProfileStep1: Profile save attempt', [
                'user_id' => Auth::id(),
                'update_data' => $updateData,
                'result' => $result
            ]);
            
            if ($result['status'] === 'success') {
                if (isset($result['user']) && $result['user'] instanceof \App\Models\User) {
                    Auth::setUser($result['user']);
                }

                session()->flash('success', 'Profile updated successfully!');
                return redirect()->route('customer.profile.step', 2);
            }
            
            // Handle validation errors
            if (isset($result['errors'])) {
                foreach ($result['errors'] as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->addError($field, $message);
                    }
                }
                return;
            }
            
            session()->flash('error', $result['message']);
            
        } catch (\Exception $e) {
            Log::error('Customer profile update error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'An error occurred while updating your profile. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.frontend.customer-profile-step1');
    }
}