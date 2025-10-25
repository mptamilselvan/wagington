<?php

namespace App\Livewire\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CustomerProfileController extends Controller
{
    // No longer using MultiStepProfileService - removed dependency

    /**
     * Show single-page profile form - redirect to multi-step form
     */
    public function showProfile()
    {
        // Redirect to multi-step profile form instead
        return redirect()->route('customer.profile.step', 1);
    }

    /**
     * Update profile from single-page form
     */
    public function updateProfile(Request $request)
    {
        try {
            // Use secure method that excludes email/phone updates
            // Email/phone should only be updated after successful OTP verification
            $result = app(\App\Services\CustomerService::class)->updateCustomerProfileBasicInfo(Auth::id(), $request->all(), 'web');
            
            if ($result['status'] === 'success') {
                return redirect()->route('customer.profile')->with('success', 'Profile updated successfully!');
            }
            
            // Handle validation errors
            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }
            
            return back()->with('error', $result['message'])->withInput();
        } catch (\Exception $e) {
            Log::error('CustomerProfileController: Error updating profile', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'An error occurred while updating your profile. Please try again.')->withInput();
        }
    }

    /**
     * Show specific step of the profile form
     */
    public function showStep($step)
    {
        $user = Auth::user();
        
        // No longer using session storage - templates get data directly from auth()->user()
        
        // Get saved addresses and address types for step 3
        $savedAddresses = [];
        $addressTypes = [];
        if ($step == 3) {
            $savedAddresses = $user->addresses()->with('addressType')->get();
            $addressTypes = \App\Models\AddressType::active()->ordered()->get();
        }
        
        return view("frontend.profile.step{$step}-livewire", [
            'context' => 'customer',
            'userId' => $user->id,
            'savedAddresses' => $savedAddresses,
            'addressTypes' => $addressTypes
        ]);
    }

    /**
     * Save step data
     */
    public function saveStep(Request $request, $step)
    {
        Log::info('CustomerProfileController: saveStep called', [
            'step' => $step,
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);
        
        try {
            if ($step == 1) {
                // Validate step 1 data before saving
                $validatedData = $this->validateStep($request, $step);
                return $this->saveStep1($request, $validatedData);
            } elseif ($step == 2) {
                Log::info('CustomerProfileController: Calling saveStep2');
                // Validate step 2 data before saving
                $validatedData = $this->validateStep($request, $step);
                return $this->saveStep2($request, $validatedData);
            } elseif ($step == 3) {
                // Only step 3 needs local validation as it's address-specific
                $validatedData = $this->validateStep($request, $step);
                return $this->saveStep3($request, $validatedData);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('CustomerProfileController: Validation error', [
                'step' => $step,
                'errors' => $e->errors()
            ]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('CustomerProfileController: Error saving step', [
                'step' => $step,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'An error occurred while saving your data. Please try again.')->withInput();
        }
    }

    /**
     * Save Step 1 - Customer Info (Direct DB Save)
     */
    private function saveStep1(Request $request, array $validatedData)
    {
        // Validate step 1 data using CustomerService validation
        $userId = Auth::id();
        
        // Handle profile picture upload if present
        if ($request->hasFile('profile_picture')) {
            $profilePicture = $request->file('profile_picture');
            $url = \App\Services\ImageService::uploadCustomerProfileImage($profilePicture, $userId);
            if ($url) {
                $validatedData['image'] = $url;
            }
            unset($validatedData['profile_picture']);
        }
        
        // Use secure method that excludes email/phone updates  
        // Email/phone should only be updated after successful OTP verification
        $customerService = app(\App\Services\CustomerService::class);
        $result = $customerService->updateCustomerProfileBasicInfo($userId, $validatedData, 'web');
        
        if ($result['status'] === 'success') {
            if (isset($result['user']) && $result['user'] instanceof \App\Models\User) {
                Auth::setUser($result['user']);
            }

            return redirect()->route('customer.profile.step', 2)
                ->with('success', 'Customer information saved successfully!');
        }
        
        // Handle validation errors
        if (isset($result['errors'])) {
            return back()->withErrors($result['errors'])->withInput();
        }
        
        return back()->with('error', $result['message'])->withInput();
    }

    /**
     * Save Step 2 - Secondary Contact (Direct DB Save)
     * Uses dedicated secondary contact update method
     */
    private function saveStep2(Request $request, array $requestData)
    {
        $userId = Auth::id();
        
        // Debug logging
        Log::info('Web Form Step2 Save Debug', [
            'user_id' => $userId,
            'request_data' => $requestData
        ]);
        
        // Use CustomerService to validate and save only secondary contact data
        $customerService = app(\App\Services\CustomerService::class);
        $result = $customerService->updateSecondaryContact($userId, $requestData, 'web');
        
        // Debug the result
        Log::info('Web Form Step2 Save Result', [
            'result' => $result
        ]);
        
        if ($result['status'] === 'success') {
            return redirect()->route('customer.profile.step', 3)
                ->with('success', 'Secondary contact information saved successfully!');
        }
        
        // Handle validation errors
        if (isset($result['errors'])) {
            return back()->withErrors($result['errors'])->withInput();
        }
        
        return back()->with('error', $result['message'])->withInput();
    }

    /**
     * Save Step 3 - Address
     */
    private function saveStep3(Request $request, array $validatedData)
    {
        Log::info('CustomerProfileController: saveStep3 called', [
            'user_id' => Auth::id(),
            'validated_data' => $validatedData
        ]);
        
        // Prepare address data in API format
        $addressData = [
            'address_type_id' => $this->getAddressTypeId($validatedData['address_type']),
            'label' => $validatedData['label'] ?? 'Home',
            'country' => $validatedData['country'],
            'postal_code' => $validatedData['postal_code'],
            'address_line_1' => $validatedData['address_line1'], // Convert to API format
            'address_line_2' => $validatedData['address_line2'] ?? null, // Convert to API format
            'is_billing_address' => isset($validatedData['is_billing_address']) && $validatedData['is_billing_address'],
            'is_shipping_address' => isset($validatedData['is_shipping_address']) && $validatedData['is_shipping_address'],
        ];
        
        // Add ID if this is an edit operation
        $addressId = $request->input('address_id');
        if ($addressId) {
            $addressData['id'] = $addressId;
        }
        
        Log::info('CustomerProfileController: Address data prepared', [
            'address_data' => $addressData
        ]);
        
        // Use the same CustomerService method as API for consistency
        $customerService = app(\App\Services\CustomerService::class);
        $result = $customerService->saveCustomerAddress(Auth::id(), $addressData);
        
        Log::info('CustomerProfileController: Address save result', [
            'result' => $result
        ]);
        
        if ($result['status'] === 'success') {
            return redirect()->route('customer.profile.step', 3)
                ->with('success', $result['message']);
        }
        
        return back()->with('error', $result['message'])->withInput();
    }

    /**
     * Get address for editing
     */
    public function getAddress($addressId)
    {
        try {
            $address = Address::where('id', $addressId)
                ->where('user_id', Auth::id())
                ->firstOrFail();
            
            return response()->json([
                'success' => true,
                'address' => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }
    }

    /**
     * Delete address
     */
    public function deleteAddress($addressId)
    {
        // Use the same CustomerService method as API for consistency
        Log::info('Deleteadds: Calling delet method');
        $customerService = app(\App\Services\CustomerService::class);
        $result = $customerService->deleteCustomerAddress(Auth::id(), $addressId);
        
        if ($result['status'] === 'success') {
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    /**
     * Validate step data for all steps
     */
    private function validateStep(Request $request, $step)
    {
        if ($step == 1) {
            // Step 1 validation rules
            $messages = [
                'phone.required' => 'The Mobile Number field is required.',
                'phone.regex' => 'The Mobile Number must contain 5 to 20 digits.',
                'phone.max' => 'The Mobile Number must not exceed 20 digits.',
                'dob.required' => 'The Date of Birth field is required.',
                'dob.date' => 'Please enter a valid Date of Birth.',
                'dob.before' => 'Date of Birth must be in the past.',
                'passport_nric_fin_number.required' => 'The Passport / NRIC / FIN Number field is required.',
                'passport_nric_fin_number.size' => 'Enter exactly the last 4 characters of your Passport / NRIC / FIN Number.',
                'passport_nric_fin_number.regex' => 'Enter the last 3 digits followed by a letter (e.g. 123A).',
            ];

            $attributes = [
                'phone' => 'Mobile Number',
                'dob' => 'Date of Birth',
                'passport_nric_fin_number' => 'Passport / NRIC / FIN Number',
            ];

            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20|regex:/^[0-9]{5,20}$/',
                'country_code' => 'required|string|max:10',
                'dob' => 'required|date|before:today',
                'passport_nric_fin_number' => 'required|string|size:4|regex:/^[A-Za-z0-9]{4}$/',
                'profile_picture' => 'nullable|image|max:2048'
            ];
            
            return $request->validate($rules, $messages, $attributes);
        } elseif ($step == 2) {
            // Step 2 validation rules
            $rules = [
                'secondary_first_name' => 'required|string|max:255',
                'secondary_last_name' => 'required|string|max:255',
                'secondary_email' => 'required|email|max:255',
                'secondary_phone' => 'required|regex:/^[0-9]{5,20}$/',
                'secondary_country_code' => 'required|string|max:10'
            ];
            
            return $request->validate($rules);
        } elseif ($step == 3) {
            // Step 3 is address-specific, keep local validation
            $rules = [
                'address_type' => 'required|string|in:home,office,pickup_dropoff,billing,shipping',
                'label' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'postal_code' => 'required|string|max:20',
                'address_line1' => 'required|string|max:255',
                'address_line2' => 'nullable|string|max:255',
                'is_billing_address' => 'nullable|boolean',
                'is_shipping_address' => 'nullable|boolean'
            ];
            
            return $request->validate($rules);
        }
        
        return [];
    }

    /**
     * Get address type ID based on type string
     */
    private function getAddressTypeId($type)
    {
        // Look up the actual address type from database
        $addressType = \App\Models\AddressType::where('name', $type)->first();
        
        if ($addressType) {
            return $addressType->id;
        }
        
        // Fallback to hardcoded mapping if database lookup fails
        $typeMap = [
            'home' => 1,
            'office' => 2,
            'pickup_dropoff' => 3,
            'billing' => 4,
            'shipping' => 5
        ];
        
        return $typeMap[$type] ?? 1; // Default to home
    }
}