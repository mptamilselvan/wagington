<?php

namespace App\Services;

use App\Models\User;
use App\Models\Address;
use App\Traits\SyncsWithStripe;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Exception;

class CustomerService
{
    use SyncsWithStripe;
    
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';
    
    // Default values
    private const DEFAULT_PER_PAGE = 15;
    private const DEFAULT_PAGE = 1;
    private const ROLE_CUSTOMER = 'customer';

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): User
    {
        $user = User::findOrFail($userId);
        $user->update($data);
        $updatedUser = $user->fresh();
        
        // Sync with Stripe if relevant fields were updated
        $this->syncWithStripeIfNeeded($updatedUser, $data, 'customer_service');
        
        return $updatedUser;
    }

    /**
     * Soft delete user account
     * This will set deleted_at timestamp and automatically exclude the user from queries
     */
    public function deleteAccount(int $userId): array
    {
        try {
            $user = User::findOrFail($userId);

            // Perform soft delete first
            $user->delete();

            // Optionally clean up Stripe customer if enabled
            if (config('services.stripe.cleanup_on_soft_delete', false)) {
                try {
                    $paymentService = app(\App\Services\PaymentService::class);
                    $paymentService->deleteStripeCustomer($user);
                } catch (\Throwable $t) {
                    \Log::warning('Stripe cleanup on soft delete failed', [
                        'user_id' => $user->id,
                        'error' => $t->getMessage(),
                    ]);
                    // Do not fail deletion if Stripe cleanup fails
                }
            }
            
            return [
                'status' => 'success',
                'message' => 'Account deleted successfully.'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to delete account. Please try again or contact support.'
            ];
        }
    }



    /**
     * Get customer statistics for the dashboard
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        $query = $this->getCustomerQuery();
        
        $totalUsers = $query->count();
        $activeUsers = $query->where('is_active', true)->count();
        $inactiveUsers = $totalUsers - $activeUsers;

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers
        ];
    }

    /**
     * Get base query for customers
     */
    private function getCustomerQuery()
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', self::ROLE_CUSTOMER));
    }

    /**
     * List customers with pagination and optional search/filtering
     */
    public function listCustomers(
        int $perPage = self::DEFAULT_PER_PAGE, 
        ?string $search = null, 
        array $filters = [], 
        int $page = self::DEFAULT_PAGE
    ): LengthAwarePaginator {
        $query = $this->getCustomerQuery();

        $this->applyFilters($query, $filters);
        $this->applySearch($query, $search);
        
        return $query->orderBy('created_at', 'desc')
                    ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Apply filters to customer query
     */
    private function applyFilters($query, array $filters): void
    {
        // Status filter
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Verification filters
        if (!empty($filters['mobile_verified'])) {
            $query->whereNotNull('phone_verified_at');
        }
        
        if (!empty($filters['email_verified'])) {
            $query->whereNotNull('email_verified_at');
        }

        // Modal active/inactive filters
        $this->applyModalStatusFilters($query, $filters);

        // Species filter (placeholder for future implementation)
        if (!empty($filters['species'])) {
            // Species filtering will be implemented when pet/species relationship is available
            // $query->whereHas('pets', fn($q) => $q->where('species', $filters['species']));
        }
    }

    /**
     * Apply modal status filters
     */
    private function applyModalStatusFilters($query, array $filters): void
    {
        $activeStatus = !empty($filters['active_status']);
        $inactiveStatus = !empty($filters['inactive_status']);

        if ($activeStatus && !$inactiveStatus) {
            $query->where('is_active', true);
        } elseif ($inactiveStatus && !$activeStatus) {
            $query->where('is_active', false);
        }
        // If both are selected or neither is selected, no filter is applied
    }

    /**
     * Apply search filters to customer query
     */
    private function applySearch($query, ?string $search): void
    {
        if (!$search) {
            return;
        }

        $query->where(function ($q) use ($search) {
            $searchTerm = '%' . $search . '%';
            $q->where('first_name', 'like', $searchTerm)
              ->orWhere('last_name', 'like', $searchTerm)
              ->orWhere('email', 'like', $searchTerm)
              ->orWhere('phone', 'like', $searchTerm);
        });
    }

    /**
     * Get a customer by ID
     */
    public function getCustomerById(int $id): ?User
    {
        return $this->getCustomerQuery()
                   ->where('id', $id)
                   ->first();
    }

    /**
     * Update customer status
     */
    public function updateCustomerStatus(int $id, bool $isActive): bool
    {
        $customer = $this->getCustomerById($id);
        
        if (!$customer) {
            return false;
        }
        
        return $customer->update(['is_active' => $isActive]);
    }



    /**
     * Update only verified email after successful OTP verification
     * Should be called immediately after OTP verification success
     */
    public function updateVerifiedEmail(int $userId, string $newEmail): array
    {
        try {
            // Validate the email format
            $validator = \Validator::make(['email' => $newEmail], [
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at')
                ]
            ]);

            if ($validator->fails()) {
                return [
                    'status' => self::STATUS_ERROR,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ];
            }

            $user = User::findOrFail($userId);
            
            // Update only the verified email and verification timestamp
            $user->update([
                'email' => $newEmail,
                'email_verified_at' => now()
            ]);
            
            $updatedUser = $user->fresh();
            
            \Log::info('CustomerService: Email updated after OTP verification', [
                'user_id' => $userId,
                'new_email' => $newEmail,
                'verified_at' => $updatedUser->email_verified_at
            ]);
            
            // Sync with Stripe if needed
            $this->syncWithStripeIfNeeded($updatedUser, ['email' => $newEmail], 'otp_verification');
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Email updated successfully',
                'user' => $updatedUser
            ];
            
        } catch (Exception $e) {
            \Log::error('CustomerService: Failed to update verified email', [
                'user_id' => $userId,
                'email' => $newEmail,
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update only verified phone after successful OTP verification
     * Should be called immediately after OTP verification success
     */
    public function updateVerifiedPhone(int $userId, string $newPhone, string $countryCode): array
    {
        try {
            // Validate the phone format
            $validator = \Validator::make([
                'phone' => $newPhone,
                'country_code' => $countryCode
            ], [
                'phone' => [
                    'required',
                    'regex:/^[0-9]{5,20}$/',
                    Rule::unique('users', 'phone')->ignore($userId)->whereNull('deleted_at')
                ],
                'country_code' => 'required|string|max:10'
            ]);

            if ($validator->fails()) {
                return [
                    'status' => self::STATUS_ERROR,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ];
            }

            $user = User::findOrFail($userId);
            
            // Update only the verified phone, country code and verification timestamp
            $user->update([
                'phone' => $newPhone,
                'country_code' => $countryCode,
                'phone_verified_at' => now()
            ]);
            
            $updatedUser = $user->fresh();
            
            \Log::info('CustomerService: Phone updated after OTP verification', [
                'user_id' => $userId,
                'new_phone' => $countryCode . $newPhone,
                'verified_at' => $updatedUser->phone_verified_at
            ]);
            
            // Sync with Stripe if needed
            $this->syncWithStripeIfNeeded($updatedUser, [
                'phone' => $newPhone, 
                'country_code' => $countryCode
            ], 'otp_verification');
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Phone number updated successfully',
                'user' => $updatedUser
            ];
            
        } catch (Exception $e) {
            \Log::error('CustomerService: Failed to update verified phone', [
                'user_id' => $userId,
                'phone' => $countryCode . $newPhone,
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update phone: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update customer profile excluding sensitive contact information (email/phone)
     * Contact information should only be updated after OTP verification
     */
    public function updateCustomerProfileBasicInfo(int $userId, array $profileData, string $context = 'web'): array
    {
        try {
            // Get current user to preserve verified contact information
            $user = User::findOrFail($userId);
            
            // Add current verified email/phone to profile data to avoid validation errors
            $profileData['email'] = $user->email;
            $profileData['phone'] = $user->phone;
            $profileData['country_code'] = $user->country_code;
            
            // Validate all data (including existing contact info)
            $validatedData = $this->validateCustomerProfileData($profileData, $userId, $context);
            
            // Debug logging
            \Log::info('CustomerService validation completed', [
                'user_id' => $userId,
                'context' => $context,
                'original_profile_data' => $profileData,
                'validated_data_first_name' => $validatedData['first_name'] ?? 'NOT_SET'
            ]);
            
            // Filter to only allow non-sensitive fields for update
            $allowedFields = ['first_name', 'last_name', 'dob', 'passport_nric_fin_number', 'image'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($validatedData[$field])) {
                    $updateData[$field] = $validatedData[$field];
                }
            }
            
            // Log what we're actually updating in the database
            \Log::info('CustomerService updating user basic info only', [
                'user_id' => $userId,
                'context' => $context,
                'update_data' => $updateData,
                'excluded_fields' => ['email', 'phone', 'country_code']
            ]);
            
            $user->update($updateData);
            $updatedUser = $user->fresh();
            
            // Sync with Stripe if relevant fields were updated
            $this->syncWithStripeIfNeeded($updatedUser, $updateData, $context);
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Profile updated successfully',
                'user' => $updatedUser
            ];
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Centralized validation for customer profile data
     * Used by all platforms: API, Web Customer, Admin
     */
    private function validateCustomerProfileData(array $data, int $userId, string $context = 'api'): array
    {
        // Base validation rules
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at')
            ],
            'phone' => [
                'required',
                'regex:/^[0-9]{5,20}$/',
                Rule::unique('users', 'phone')->ignore($userId)->whereNull('deleted_at')
            ],
            'country_code' => 'required|string|max:10',
            'dob' => 'required|date|before:today',
            'passport_nric_fin_number' => 'required|string|size:4|regex:/^[A-Za-z0-9]{4}$/',
            'secondary_first_name' => 'nullable|string|max:255',
            'secondary_last_name' => 'nullable|string|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'secondary_phone' => 'nullable|regex:/^[0-9]{5,20}$/',
            'secondary_country_code' => 'nullable|string|max:10',
            'image' => 'sometimes|string|max:2048',
        ];

        $customMessages = [
            'dob.required' => 'The Date of Birth field is required.',
            'phone.required' => 'The Mobile Number field is required.',
            'phone.unique' => 'This mobile number is already registered. Please use a different number.',
            'phone.regex' => 'The mobile number must contain only digits and be between 5 and 20 characters.',
            'email.unique' => 'This email address is already registered. Please use a different email.',
            'passport_nric_fin_number.required' => 'The Passport / NRIC / FIN Number field is required.',
            'passport_nric_fin_number.size' => 'Enter exactly the last 4 characters of your Passport / NRIC / FIN Number.',
            'passport_nric_fin_number.regex' => 'Enter exactly 4 alphanumeric characters (A-Z and 0-9)',
        ];

        if ($context === 'admin') {
            $rules['is_active'] = 'boolean';
        }

        if ($context === 'api') {
            $rules['first_name'] = 'sometimes|string|max:255';
            $rules['last_name'] = 'sometimes|string|max:255';
            $rules['email'] = [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at')
            ];
            $rules['phone'] = [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($userId)->whereNull('deleted_at')
            ];
            $rules['country_code'] = 'sometimes|string|max:10';
            $rules['dob'] = 'sometimes|date|before:today';
            $rules['passport_nric_fin_number'] = 'sometimes|string|size:4|regex:/^[A-Za-z0-9]{4}$/';
        }

        $validator = \Validator::make($data, $rules, $customMessages);

        // Add custom validation for secondary fields not matching primary fields
        $validator->after(function ($validator) use ($data) {
            // Check if secondary email matches primary email
            if (!empty($data['secondary_email']) && !empty($data['email']) && 
                $data['secondary_email'] === $data['email']) {
                $validator->errors()->add('secondary_email', 'Secondary email must be different from primary email.');
            }
            
            // Check if secondary phone matches primary phone
            if (!empty($data['secondary_phone']) && !empty($data['phone']) && 
                $data['secondary_phone'] === $data['phone']) {
                $validator->errors()->add('secondary_phone', 'Secondary phone must be different from primary phone.');
            }
        });

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Centralized validation for customer creation data
     * Used by all platforms: API, Web Customer, Admin
     */
    private function validateCustomerCreationData(array $data, string $context = 'api'): array
    {
        // Base validation rules for creation
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            'phone' => [
                'required',
                'regex:/^[0-9]{5,20}$/',
                Rule::unique('users', 'phone')->whereNull('deleted_at')
            ],
            'country_code' => 'required|string|max:10',
            'dob' => 'required|date|before:today',
            'passport_nric_fin_number' => 'required|string|size:4|regex:/^[A-Za-z0-9]{4}$/',
            'secondary_first_name' => 'nullable|string|max:255',
            'secondary_last_name' => 'nullable|string|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'secondary_phone' => 'nullable|string|max:20',
            'secondary_country_code' => 'nullable|string|max:10',
        ];

        $customMessages = [
            'dob.required' => 'The Date of Birth field is required.',
            'phone.required' => 'The Mobile Number field is required.',
            'phone.unique' => 'This mobile number is already registered. Please use a different number.',
            'phone.regex' => 'The mobile number must contain only digits and be between 5 and 20 characters.',
            'email.unique' => 'This email address is already registered. Please use a different email.',
            'passport_nric_fin_number.required' => 'The Passport / NRIC / FIN Number field is required.',
        ];

        if ($context === 'admin') {
            $rules['is_active'] = 'boolean';
        }

        $validator = \Validator::make($data, $rules, $customMessages);

        // Add custom validation for secondary fields not matching primary fields
        $validator->after(function ($validator) use ($data) {
            // Check if secondary email matches primary email
            if (!empty($data['secondary_email']) && !empty($data['email']) && 
                $data['secondary_email'] === $data['email']) {
                $validator->errors()->add('secondary_email', 'Secondary email must be different from primary email.');
            }
            
            // Check if secondary phone matches primary phone
            if (!empty($data['secondary_phone']) && !empty($data['phone']) && 
                $data['secondary_phone'] === $data['phone']) {
                $validator->errors()->add('secondary_phone', 'Secondary phone must be different from primary phone.');
            }
        });

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }



    /**
     * Reset verification status immediately when email/phone is changed by admin
     * This method updates the database immediately without waiting for form submission
     */
    public function resetVerificationStatus(int $userId, string $field, string $newValue): array
    {
        try {
            $user = User::findOrFail($userId);
            $updateData = [];
            $resetField = '';
            
            // Determine which verification field to reset based on the changed field
            switch ($field) {
                case 'email':
                    if ($user->email !== $newValue) {
                        $updateData['email_verified_at'] = null;
                        $resetField = 'email_verified_at';
                    }
                    break;
                    
                case 'phone':
                    if ($user->phone !== $newValue) {
                        $updateData['phone_verified_at'] = null;
                        $resetField = 'phone_verified_at';
                    }
                    break;
                    
                case 'secondary_email':
                    if ($user->secondary_email !== $newValue) {
                        $updateData['secondary_email_verified_at'] = null;
                        $resetField = 'secondary_email_verified_at';
                    }
                    break;
                    
                case 'secondary_phone':
                    if ($user->secondary_phone !== $newValue) {
                        $updateData['secondary_phone_verified_at'] = null;
                        $resetField = 'secondary_phone_verified_at';
                    }
                    break;
            }
            
            // Update database if verification needs to be reset
            if (!empty($updateData)) {
                $user->update($updateData);
                
                return [
                    'status' => self::STATUS_SUCCESS,
                    'message' => 'Verification status reset successfully',
                    'reset_field' => $resetField,
                    'verification_reset' => true
                ];
            }
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'No verification reset needed',
                'verification_reset' => false
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to reset verification status: ' . $e->getMessage(),
                'verification_reset' => false
            ];
        }
    }

    /**
     * Mark field as verified after successful OTP verification
     */
    public function markFieldAsVerified(int $userId, string $field): array
    {
        try {
            $user = User::findOrFail($userId);
            $updateData = [];
            $verifiedField = '';
            
            // Log what we're trying to update
            \Log::info('CustomerService: markFieldAsVerified called', [
                'user_id' => $userId,
                'field' => $field,
                'current_primary_email' => $user->email,
                'current_secondary_email' => $user->secondary_email
            ]);
            
            // Determine which verification field to update
            switch ($field) {
                case 'email':
                    $updateData['email_verified_at'] = now();
                    $verifiedField = 'email_verified_at';
                    break;
                    
                case 'phone':
                    $updateData['phone_verified_at'] = now();
                    $verifiedField = 'phone_verified_at';
                    break;
                    
                case 'secondary_email':
                    $updateData['secondary_email_verified_at'] = now();
                    $verifiedField = 'secondary_email_verified_at';
                    break;
                    
                case 'secondary_phone':
                    $updateData['secondary_phone_verified_at'] = now();
                    $verifiedField = 'secondary_phone_verified_at';
                    break;
            }
            
            if (!empty($updateData)) {
                \Log::info('CustomerService: Updating verification timestamp only', [
                    'user_id' => $userId,
                    'update_data' => $updateData,
                    'field' => $verifiedField
                ]);
                
                $user->update($updateData);
                
                return [
                    'status' => self::STATUS_SUCCESS,
                    'message' => 'Field marked as verified successfully',
                    'verified_field' => $verifiedField,
                    'verification_completed' => true
                ];
            }
            
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Invalid field specified',
                'verification_completed' => false
            ];
            
        } catch (Exception $e) {
            \Log::error('CustomerService: markFieldAsVerified failed', [
                'user_id' => $userId,
                'field' => $field,
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to mark field as verified: ' . $e->getMessage(),
                'verification_completed' => false
            ];
        }
    }

    /**
     * Save customer address
     * Uses the same logic as web CustomerProfileController->saveStep3()
     */
    public function saveCustomerAddress(int $userId, array $addressData): array
    {
        try {
            $user = User::findOrFail($userId);
            
            $address = isset($addressData['id']) && $addressData['id']
                ? $this->updateExistingAddress($user, $addressData)
                : $this->createNewAddress($user, $addressData);
            
            $message = isset($addressData['id']) && $addressData['id']
                ? 'Address updated successfully!'
                : 'Address added successfully!';
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => $message,
                'address' => $address->load('addressType')
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to save address: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing address
     */
    private function updateExistingAddress(User $user, array $addressData): Address
    {
        // Log the update attempt
        \Log::info('CustomerService updating existing address', [
            'user_id' => $user->id,
            'address_id' => $addressData['id'],
            'address_data' => $addressData
        ]);
        
        $address = Address::where('id', $addressData['id'])
            ->where('user_id', $user->id)
            ->first();
            
        if (!$address) {
            throw new \Exception("Address with ID {$addressData['id']} not found for user {$user->id}");
        }
        
        $address->update($this->formatAddressData($addressData));
        
        return $address;
    }

    /**
     * Create new address
     */
    private function createNewAddress(User $user, array $addressData): Address
    {
        // Log the create attempt
        \Log::info('CustomerService creating new address', [
            'user_id' => $user->id,
            'address_data' => $addressData
        ]);
        
        $addressFields = $this->formatAddressData($addressData);
        $addressFields['user_id'] = $user->id;
        $addressFields['is_default'] = $user->addresses()->count() === 0;
        
        \Log::info('CustomerService formatted address fields', [
            'address_fields' => $addressFields
        ]);
        
        return Address::create($addressFields);
    }

    /**
     * Format address data for database storage
     */
    private function formatAddressData(array $addressData): array
    {
        return [
            'address_type_id' => $addressData['address_type_id'],
            'label' => $addressData['label'] ?? 'Home',
            'country' => $addressData['country'],
            'postal_code' => $addressData['postal_code'],
            'address_line1' => $addressData['address_line_1'], // API uses address_line_1, DB uses address_line1
            'address_line2' => $addressData['address_line_2'] ?? null, // API uses address_line_2, DB uses address_line2
            'is_billing_address' => $addressData['is_billing_address'] ?? false,
            'is_shipping_address' => $addressData['is_shipping_address'] ?? false,
        ];
    }

    /**
     * Get customer addresses
     */
    public function getCustomerAddresses(int $userId): array
    {
        try {
            $user = User::findOrFail($userId);
            $addresses = $user->addresses()->with('addressType')->get();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'addresses' => $addresses
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to get addresses'
            ];
        }
    }

    /**
     * Delete customer address
     * Uses the same logic as web CustomerProfileController->deleteAddress()
     */
    public function deleteCustomerAddress(int $userId, int $addressId): array
    {
        try {
            $address = Address::where('id', $addressId)
                ->where('user_id', $userId)
                ->firstOrFail();
            
            $address->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Address deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete address'
            ];
        }
    }
    
    /**
     * Create new customer
     * Uses centralized validation for all platforms (API, Web, Admin)
     */
    public function createCustomer(array $customerData, string $context = 'api'): array
    {
        try {
            // Centralized validation - same for all platforms
            $validatedData = $this->validateCustomerCreationData($customerData, $context);
            
            DB::beginTransaction();
            
            $user = User::create($this->prepareCustomerData($validatedData, $context));
            $user->assignRole(self::ROLE_CUSTOMER);
            
            DB::commit();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Customer created successfully',
                'user_id' => $user->id,
                'user' => $user
            ];
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ];
        } catch (Exception $e) {
            DB::rollBack();
            
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to create customer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Prepare customer data for creation
     */
    private function prepareCustomerData(array $customerData, string $context): array
    {
        $data = [
            'first_name' => $customerData['first_name'],
            'last_name' => $customerData['last_name'],
            'email' => $customerData['email'],
            'phone' => $customerData['phone'],
            'country_code' => $customerData['country_code'] ?? '+65',
            'dob' => $customerData['dob'],
            'passport_nric_fin_number' => $customerData['passport_nric_fin_number'],
            'secondary_first_name' => $customerData['secondary_first_name'] ?? null,
            'secondary_last_name' => $customerData['secondary_last_name'] ?? null,
            'secondary_email' => $customerData['secondary_email'] ?? null,
            'secondary_phone' => $customerData['secondary_phone'] ?? null,
            'secondary_country_code' => $customerData['secondary_country_code'] ?? null,
            'password' => Hash::make(Str::random(12)),
            'created_by' => $context === 'admin' ? auth()->id() : null,
        ];

        // Handle is_active based on context
        if ($context === 'admin') {
            // Admin can explicitly set is_active status (calculated in Customer.php based on verification)
            $data['is_active'] = $customerData['is_active'] ?? false;
        } else {
            // For API/customer context, don't modify is_active - customer is already verified to reach here
            if (isset($customerData['is_active'])) {
                $data['is_active'] = $customerData['is_active'];
            }
        }
        return $data;
    }

    /**
     * Update only secondary contact information (Step 2)
     * Used for multi-step forms where only secondary contact fields are being updated
     */
    public function updateSecondaryContact(int $userId, array $secondaryData, string $context = 'web'): array
    {
        try {
            // Validate only secondary contact fields
            $rules = [
                'secondary_first_name' => 'required|string|max:255',
                'secondary_last_name' => 'required|string|max:255',
                'secondary_email' => 'required|email|max:255',
                'secondary_phone' => 'required|regex:/^[0-9]{5,20}$/',
                'secondary_country_code' => 'required|string|max:10',
            ];

            // Add admin-specific fields for verification timestamps
            if ($context === 'admin') {
                $rules['secondary_email_verified_at'] = 'nullable|date';
                $rules['secondary_phone_verified_at'] = 'nullable|date';
            }

            $validator = \Validator::make($secondaryData, $rules, [
                'secondary_phone.regex' => 'The mobile number must contain only digits and be between 5 and 20 characters.',
            ]);

            // Add custom validation to ensure secondary fields don't match primary fields
            $validator->after(function ($validator) use ($secondaryData, $userId) {
                $user = User::find($userId);
                if ($user) {
                    // Check if secondary email matches primary email
                    if (!empty($secondaryData['secondary_email']) && 
                        $secondaryData['secondary_email'] === $user->email) {
                        $validator->errors()->add('secondary_email', 'Secondary email must be different from primary email.');
                    }
                    
                    // Check if secondary phone matches primary phone
                    if (!empty($secondaryData['secondary_phone']) && 
                        $secondaryData['secondary_phone'] === $user->phone) {
                        $validator->errors()->add('secondary_phone', 'Secondary phone must be different from primary phone.');
                    }
                }
            });

            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            $validatedData = $validator->validated();
            
            // Filter only secondary contact fields for update
            $allowedSecondaryFields = [
                'secondary_first_name', 'secondary_last_name', 'secondary_email',
                'secondary_phone', 'secondary_country_code'
            ];

            // Add admin-specific fields
            if ($context === 'admin') {
                $allowedSecondaryFields[] = 'secondary_email_verified_at';
                $allowedSecondaryFields[] = 'secondary_phone_verified_at';
            }
            
            $updateData = array_intersect_key($validatedData, array_flip($allowedSecondaryFields));
            
            // Update user with only secondary contact fields
            $user = User::findOrFail($userId);
            $user->update($updateData);
            $updatedUser = $user->fresh();
            
            \Log::info('CustomerService: Secondary contact updated', [
                'user_id' => $userId,
                'context' => $context,
                'update_data' => $updateData
            ]);
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Secondary contact information updated successfully',
                'user' => $updatedUser
            ];
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update secondary contact: ' . $e->getMessage()
            ];
        }
    }
}
