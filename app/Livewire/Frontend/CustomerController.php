<?php

namespace App\Livewire\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\CustomerAuthValidation;
use App\Models\User;
use App\Services\OtpService;
use App\Traits\HandlesAuthServiceResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AuthService; // Import AuthService as you'll use it for login OTP
use App\Services\CustomerService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    use HandlesAuthServiceResponses;
    
    protected $otpService;
    protected $authService; // Inject AuthService
    protected $customerService;
    protected $paymentService;

    public function __construct(OtpService $otpService, AuthService $authService, CustomerService $customerService, PaymentService $paymentService)
    {
        $this->otpService = $otpService;
        $this->authService = $authService; // Initialize AuthService
        $this->customerService = $customerService;
        $this->paymentService = $paymentService;
    }

    public function showLoginForm()
    {
        // If a customer is already logged in, redirect them to the dashboard
        if (Auth::check() && Auth::user()->hasRole('customer')) {
            return redirect()->route('customer.dashboard');
        }
        return view('frontend.login');
    }

    public function sendOtp(Request $request)
    {
        Log::info('CustomerController sendOtp - Raw request data:', $request->all());
        
        $validated = CustomerAuthValidation::validateLogin($request->all());

        $identifier = $validated['identifier'];
        $type = $validated['type']; // Should be 'phone' based on your logic
        
        Log::info('CustomerController sendOtp - Validated data:', ['identifier' => $identifier, 'type' => $type]);

        // Use AuthService to send login OTP
        $result = $this->authService->sendLoginOtp($identifier, $type);

        // Use trait to handle response with session data
        return $this->handleAuthServiceResponseWithSession(
            $result,
            [
                'otp_identifier' => $identifier,
                'otp_type' => $type,
            ],
            'customer.showOtpForm',
            'OTP sent successfully.'
        );
    }

    public function showOtpForm()
    {
        if (!session()->has('otp_identifier') || !session()->has('otp_type')) {
            return redirect()->route('customer.login')->with('error', 'Please initiate login first.');
        }

        return view('frontend.verify-otp', ['identifier' => session('otp_identifier')]); // Pass identifier to view
    }

    public function verifyOtp(Request $request)
    {
        logger("Verifying OTP for customer login");
        logger("Request data: " . json_encode($request->all()));
        logger("Verifying OTP for identifier: " . session('otp_identifier') . ", type: " . session('otp_type'));
        $validated = CustomerAuthValidation::validateVerifyOtp($request->all());

        $identifier = session('otp_identifier');
        $type = session('otp_type');
        $otp = $validated['otp'];



        if (!$identifier || !$type) {
            return redirect()->route('customer.login')->with('error', 'Login session expired. Please try again.');
        }

        // Use AuthService for login OTP verification
        logger("About to call verifyLoginOtp with identifier: $identifier, otp: $otp, type: $type");
        $result = $this->authService->verifyLoginOtp($identifier, $otp, $type);
        logger("AuthService result: " . json_encode($result));

        if ($result['status'] === 'success') {
            logger("OTP verification successful, attempting to log in user");
            $user = User::find($result['user']['id']); ; // Get the user object from AuthService
            logger("Found user: " . $user->email . " with ID: " . $user->id);

            // Ensure the user has the customer role before logging them in
            if (!$user->hasRole('customer')) {
                logger("User does not have customer role");
                return back()->withErrors(['otp' => 'Access denied. You are not a customer.']);
            }

            logger("User has customer role, logging in");
            Auth::login($user); // Log in the user using Laravel's session auth
            logger("User logged in successfully, Auth::check(): " . (Auth::check() ? 'true' : 'false'));
            $request->session()->regenerate(); // Regenerate session ID
            session()->forget(['otp_identifier', 'otp_type']); // Clear OTP session data
            logger("About to redirect to dashboard");

            return redirect()->intended(route('customer.dashboard'))->with('success', 'Logged in successfully.');
        } elseif ($result['status'] === 'invalid' || $result['status'] === 'not_found' || $result['status'] === 'inactive' || $result['status'] === 'verification_required') {
            logger("OTP verification failed with status: " . $result['status'] . ", message: " . $result['message']);
            return back()->withErrors(['otp' => $result['message']]);
        }

        logger("OTP verification failed with unknown status");
        return back()->withErrors(['otp' => 'OTP verification failed. Please try again.']);
    }

    public function cancelOtp(Request $request)
    {
        // Clear OTP session data
        session()->forget(['otp_identifier', 'otp_type', 'login_phone']);
        
        return response()->json(['success' => true]);
    }

    public function showSuccess()
    {
        // This success page is likely for post-registration or initial login flow
        // For general customer success after full login, the dashboard is usually the target.
        return view('frontend.success');
    }

    public function dashboard()
    {
        logger("hai dashboard");
        
        // Middleware `auth` and `role:customer` will handle authorization.
        if (!Auth::check() || !Auth::user()->hasRole('customer')) {
            return redirect()->route('customer.login')->with('error', 'Unauthorized access.');
        }
        
        $user = Auth::user();
        
        // Check for a specific role
        if ($user->hasRole('admin')) {
            logger('User is admin');
        } else {
            logger('User is NOT admin');
        }

        // OR get all roles of user
        logger($user->getRoleNames()); // Returns a collection, e.g. ['admin']
        
        return view('frontend.dashboard', compact('user'));
    }

    public function logout(Request $request) // Add Request $request parameter
    {
        Auth::logout(); // Log out the user

        $request->session()->invalidate(); // Invalidate the session
        $request->session()->regenerateToken(); // Regenerate CSRF token

        return redirect()->route('customer.login')->with('success', 'Logged out successfully.');
    }

    public function profile()
    {
        // Ensure the user is authenticated and has customer role
        if (!Auth::check() || !Auth::user()->hasRole('customer')) {
            return redirect()->route('customer.login')->with('error', 'Unauthorized access.');
        }

        $user = Auth::user();
        return view('frontend.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        // Ensure the user is authenticated and has customer role
        if (!Auth::check() || !Auth::user()->hasRole('customer')) {
            return redirect()->route('customer.login')->with('error', 'Unauthorized access.');
        }

        Log::info('Profile update - Request data:', $request->all());

        try {
            // Use the existing validation from CustomerValidation (profile validation)
            $validated = \App\Http\Requests\CustomerValidation::validateProfileUpdate($request->all());
            Log::info('Profile update - Validation passed:', $validated);
            
            // Update the user profile using the existing service
            $user = $this->customerService->updateProfile(Auth::id(), $validated);
            Log::info('Profile update - User updated successfully');

            return redirect()->route('customer.profile')->with('success', 'Profile updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Profile update - Validation failed:', $e->errors());
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            Log::error('Profile update - Error occurred:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Failed to update profile: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Delete customer account (soft delete)
     */
    public function deleteAccount(Request $request)
    {
        // Ensure the user is authenticated and has customer role
        if (!Auth::check() || !Auth::user()->hasRole('customer')) {
            return redirect()->route('customer.login')->with('error', 'Unauthorized access.');
        }

        try {
            $userId = Auth::id();
            
            // Use the same service method as API
            $result = $this->customerService->deleteAccount($userId);
            
            if ($result['status'] === 'success') {
                // Log out the user after successful deletion
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('home')->with('success', $result['message']);
            }
            
            return back()->with('error', $result['message']);
            
        } catch (\Exception $e) {
            Log::error('Account deletion failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Failed to delete account. Please try again or contact support.');
        }
    }


    /**
     * Create and attach payment method for the authenticated customer (session/CSRF protected)
     */
    public function createPaymentMethod(Request $request)
    {
        // Ensure the user is authenticated and has customer role
        if (!Auth::check() || !Auth::user()->hasRole('customer')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized access.'], 401);
        }

        $request->validate([
            'token' => ['required', 'string', 'regex:/^(tok_|pm_)[A-Za-z0-9_]+$/'], // pm_* or tok_* allowed
            'default' => ['nullable', 'boolean']
        ]);

        try {
            $user = Auth::user();
            $token = $request->input('token');
            $setAsDefault = (bool) $request->boolean('default');

            $result = $this->paymentService->createPaymentMethodFromToken($user, $token, $setAsDefault);

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment method created successfully',
                    'payment_method' => $result['payment_method']
                ], 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid input',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Create payment method (web) failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create payment method. Please try again.'
            ], 500);
        }
    }
}