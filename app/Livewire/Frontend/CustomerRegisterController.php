<?php

namespace App\Livewire\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OtpService;
use App\Services\AuthService;
use App\Models\User;
use App\Http\Requests\Frontend\CustomerRegisterValidation;
use App\Traits\HandlesAuthServiceResponses;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CustomerRegisterController extends Controller
{
    use HandlesAuthServiceResponses;
    
    protected $otpService;
    protected $authService;

    public function __construct(OtpService $otpService, AuthService $authService)
    {
        $this->otpService = $otpService;
        $this->authService = $authService;
    }

    public function showRegisterForm()
    {
        // If already authenticated as a customer, redirect to dashboard
        if (Auth::check() && Auth::user()->hasRole('customer')) {
            return redirect()->route('customer.dashboard');
        }
        return view('frontend.register');
    }

    public function register(Request $request)
    {
        $validated = CustomerRegisterValidation::validateRegister($request->all());
        $phone = $validated['phone'];
        $country_code = $validated['country_code'];
        $referal_code = $validated['referal_code'] ?? null;
        
        // Combine country code and phone for OTP sending
        $fullPhone = $country_code . $phone;

        // Pass separated fields to AuthService
        $result = $this->authService->registerWithPhone($fullPhone, $referal_code, $country_code, $phone);

        // Use trait to handle response with session data
        return $this->handleAuthServiceResponseWithSession(
            $result,
            [
                'register_phone' => $phone,
                'register_country_code' => $country_code,
                'register_full_phone' => $fullPhone,
                'register_referal_code' => $referal_code,
            ],
            'customer.register.showOtpForm',
            'OTP sent to your mobile.'
        );
    }

    public function showOtpForm()
    {
        if (!session()->has('register_phone')) {
            return redirect()->route('customer.register.form')->with('error', 'Please start registration first.');
        }

        return view('frontend.verify-phone-otp', ['phone' => session('register_phone')]); // Pass phone to view
    }

    /**
     * Resends the OTP for phone registration.
     */
    public function resendPhoneOtp(Request $request)
    {
        $phone = session('register_phone');

        if (!$phone) {
            return redirect()->route('customer.register.form')->with('error', 'Registration session expired or invalid. Please start again.');
        }

        $full_phone = session('register_full_phone');
        $result = $this->authService->resendOtp($full_phone, 'phone', 'registration');

        // Use trait to handle response (no redirect, just back with success/error)
        return $this->handleAuthServiceResponse($result);
    }


    public function verifyOtp(Request $request)
    {
        $validated = CustomerRegisterValidation::validateVerifyOtp($request->all());

        // OTP is now a string, no need to implode
        $otp = $validated['otp'];

        $phone = session('register_phone');
        $country_code = session('register_country_code');
        $full_phone = session('register_full_phone');

        if (!$phone || !$country_code) {
             return redirect()->route('customer.register.form')->with('error', 'Registration session expired. Please start again.');
        }

        Log::info("Verifying-1 OTP for phone:{$phone}");

        // Use the full phone for OTP verification, but pass separated fields for user lookup
        $result = $this->authService->verifyRegistrationOtp($full_phone, $otp, 'phone', $country_code, $phone);

        if ($result['status'] !== 'success') {
            return back()->withErrors(['otp' => $result['message']])->withInput();
        }

        // Don't auto-login after phone verification - user needs both phone AND email verified to login
        // Auth::loginUsingId($result['user']['id']); // REMOVED
        // $request->session()->regenerate(); // REMOVED

        // Store user ID in session for email attachment flow
        session(['phone_verified_user_id' => $result['user']['id']]);
        session()->forget(['register_phone', 'register_country_code', 'register_full_phone', 'register_referal_code']);

        return redirect()->route('customer.register.phone.success')->with('success', 'Mobile number verified successfully.');
    }


    public function showPhoneSuccess()
    {
        return view('frontend.register-phone-success');
    }

    public function showAttachEmailForm()
    {
        // Check if user has completed phone verification (session-based)
        $phoneVerified = session('phone_verified_user_id');
        if (!$phoneVerified) {
            return redirect()->route('customer.register.form')->with('error', 'You must complete phone verification first.');
        }
        
        // Get user to check if email is already attached
        $user = User::find($phoneVerified);
        if ($user && $user->email_verified_at) {
            return redirect()->route('customer.register.email.success')->with('info', 'Email already verified.');
        }
        
        return view('frontend.attach-email');
    }

    public function attachEmail(Request $request)
    {
        // Add logging to debug the issue
        Log::info('CustomerRegisterController::attachEmail called');
        Log::info('Request data: ' . json_encode($request->all()));
        
        // Get user from session instead of auth
        $userId = session('phone_verified_user_id');
        if (!$userId) {
            Log::error('No phone_verified_user_id in session');
            return redirect()->route('customer.register.form')->with('error', 'Please complete phone verification first.');
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('User not found with ID: ' . $userId);
            return redirect()->route('customer.register.form')->with('error', 'User not found.');
        }

        Log::info('User found - ID: ' . $user->id . ', Phone: ' . $user->phone . ', Country Code: ' . $user->country_code . ', Full Phone: ' . $user->full_phone);

        $validated = CustomerRegisterValidation::validateAttachEmail($request->all());
        $email = $validated['email'];

        Log::info('Validated email: ' . $email);
        Log::info('Calling attachEmailToUser with full phone: ' . $user->full_phone);

        // Fix: Use full_phone (country_code + phone) instead of just phone
        $result = $this->authService->attachEmailToUser($user->full_phone, $email);

        Log::info('AuthService response: ' . json_encode($result));

        if ($result['status'] !== 'success') {
            Log::error('Failed to attach email: ' . $result['message']);
            return back()->withErrors(['email' => $result['message']])->withInput();
        }

        session(['register_email' => $email]);

        return redirect()->route('customer.register.showEmailOtpForm')
            ->with('success', 'OTP sent to your email for verification.');
    }


    public function showEmailOtpForm()
    {
        if (!session()->has('register_email')) {
            return redirect()->route('customer.register.attachEmailForm')
                ->with('error', 'Please attach your email first.');
        }

        return view('frontend.verify-email-otp', ['email' => session('register_email')]); // Pass email to view
    }

    /**
     * Resends the OTP for email registration.
     */
    public function resendEmailOtp(Request $request)
    {
        $email = session('register_email');

        if (!$email) {
            return redirect()->route('customer.register.attachEmailForm')->with('error', 'Email attachment session expired. Please try again.');
        }

        $result = $this->authService->resendOtp($email, 'email', 'registration');

        if ($result['status'] === 'success') {
            return back()->with('success', $result['message']);
        } else {
            // Handle rate limiting and other errors specifically
            if ($result['status'] === 'rate_limited') {
                return back()->withErrors(['otp' => $result['message']]);
            } else {
                return back()->with('error', $result['message']);
            }
        }
    }

    public function verifyEmailOtp(Request $request)
    {
        $validated = CustomerRegisterValidation::validateVerifyOtp($request->all());

        $otp = $validated['otp'];
        $email = session('register_email');

        if (!$email) {
             return redirect()->route('customer.register.attachEmailForm')->with('error', 'Email attachment session expired. Please try again.');
        }

        $result = $this->authService->verifyRegistrationOtp($email, $otp, 'email');

        if ($result['status'] !== 'success') {
            return back()->withErrors(['otp' => $result['message']])->withInput();
        }

        // After email verification, clean up session data
        session()->forget(['register_email', 'phone_verified_user_id']);

        return redirect()->route('customer.register.email.success')
            ->with('success', 'Congratulations! Your email address has been successfully verified. You can now login.');
    }

    public function showEmailSuccess()
    {
        return view('frontend.register-email-success');
    }
}