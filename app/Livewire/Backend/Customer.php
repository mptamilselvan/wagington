<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Models\User;
use App\Models\Address;
use App\Models\AddressType;
use App\Models\Species;
use App\Traits\SMSTrait;
use App\Services\CustomerService;
use App\Services\AuthService;
use App\Services\OtpService;
use App\Services\ProfileService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Illuminate\Http\UploadedFile;
use Exception;

class Customer extends Component
{
    use WithPagination, SMSTrait, WithFileUploads;
    
    protected $customerService;
    protected $authService;
    protected $otpService;
    protected $profileService;



    // Main state variables
    public $form = false, $list = true, $view = false, $title = 'Customers', $search = '';
    public $mode = 'add', $editId = '', $currentStep = 1;
    public $searchPlaceholder = 'Search by name, mobile number, email';
    
    // Filter variables
    public $filterStatus = '';
    public $filterVerification = [];
    public $filterSpecies = [];
    public $filterDeleted = []; // New filter for deleted customers
    public $showFilterModal = false;
    
    // Pagination
    public $perPage = 10;
    
    // Form data - Step 1 (Customer Profile)
    public $first_name = '', $last_name = '', $email = '', $phone = '', $country_code = '+65';
    public $dob = '', $passport_nric_fin_number = '', $image = '', $is_active = false, $referal_code = '';
    public $profile_photo; // temp uploaded file for admin
    
    // Form data - Step 2 (Secondary Contact)
    public $secondary_first_name = '', $secondary_last_name = '', $secondary_email = '', $secondary_phone = '', $secondary_country_code = '+65';
    
    // Form data - Step 3 (Addresses)
    public $addresses = [];
    public $savedAddresses = [];
    public $addressTypes = [];
    public $countries = [];
    public $species = [];
    public $newAddress = [
        'id' => null,
        'address_type_id' => '',
        'label' => '',
        'country' => '',
        'postal_code' => '',
        'address_line1' => '',
        'address_line2' => '',
        'is_billing_address' => false,
        'is_shipping_address' => false,
    ];
    
    // Individual address properties for better Livewire binding
    public $address_id = null;
    public $address_type_id = ''; // This should be initialized properly
    public $address_label = '';
    public $address_country = '';
    public $address_postal_code = '';
    public $address_line1 = '';
    public $address_line2 = '';
    public $is_billing_address = false;
    public $is_shipping_address = false;
    
    // OTP Verification
    public $showOtpModal = false;
    public $otpType = ''; // 'mobile', 'email', 'secondary_mobile', 'secondary_email'
    public $otpDigits = ['', '', '', '', '', ''];
    public $otpMessage = '';
    public $otpMessageType = '';
    public $emailVerified = false;
    public $phoneVerified = false;
    public $secondaryEmailVerified = false;
    public $secondaryPhoneVerified = false;
    
    // Edit mode for email and phone in update customer
    public $emailEditMode = false;
    public $phoneEditMode = false;
    
    // Original values for comparison (to handle revert scenarios)
    public $originalEmail = '';
    public $originalPhone = '';
    public $originalCountryCode = '';
    public $originalSecondaryEmail = '';
    public $originalSecondaryPhone = '';
    public $originalSecondaryCountryCode = '';
    
    // Form refresh key to force re-rendering
    public $formKey;

    
    protected $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email',
        // Only digits, length 5-20
        'phone' => 'required|regex:/^[0-9]{5,20}$/',
        'dob' => 'required|date|before:today',
        'passport_nric_fin_number' => 'required|string|size:4|regex:/^[A-Za-z0-9]{4}$/',
        'secondary_first_name' => 'required|string|max:255',
        'secondary_last_name' => 'required|string|max:255',
        'secondary_email' => 'required|email',
        // Only digits, length 5-20
        'secondary_phone' => 'required|regex:/^[0-9]{5,20}$/',
        'profile_photo' => 'nullable|image|max:2048',
    ];

    public function boot(CustomerService $customerService, AuthService $authService, OtpService $otpService, ProfileService $profileService)
    {
        $this->customerService = $customerService;
        $this->authService = $authService;
        $this->otpService = $otpService;
        $this->profileService = $profileService;
    }

    /**
     * Reset verification status when email/phone fields are updated
     * This provides immediate UI feedback while Alpine.js handles debounced database updates
     */
    public function updatedEmail($value)
    {
        // Clear validation errors for real-time feedback
        $this->resetErrorBag('email');
        
        // Handle verification status based on original value comparison
        if ($this->mode === 'edit' && $this->editId) {
            if ($value === $this->originalEmail) {
                // Reverted to original value - restore original verification status
                $customer = $this->getCustomerSafely($this->editId);
                $this->emailVerified = $customer ? !is_null($customer->email_verified_at) : false;
            } else {
                // Changed from original value - require verification
                $this->emailVerified = false;
                
                // Dispatch event for immediate UI feedback
                $this->dispatch('verification-reset-immediate', [
                    'field' => 'email',
                    'message' => 'Email changed. Verification required.'
                ]);
            }
        } elseif ($this->mode === 'add') {
            // For new customers, always require verification
            $this->emailVerified = false;
        }
    }

    public function updatedPhone($value)
    {
        // Clear validation errors for real-time feedback
        $this->resetErrorBag('phone');
        
        // Handle verification status based on original value comparison
        if ($this->mode === 'edit' && $this->editId) {
            if ($value === $this->originalPhone && $this->country_code === $this->originalCountryCode) {
                // Reverted to original value with same country code - restore original verification status
                $customer = $this->getCustomerSafely($this->editId);
                $this->phoneVerified = $customer ? !is_null($customer->phone_verified_at) : false;
            } else {
                // Changed from original value - require verification
                $this->phoneVerified = false;
                
                // Dispatch event for immediate UI feedback
                $this->dispatch('verification-reset-immediate', [
                    'field' => 'phone',
                    'message' => 'Phone changed. Verification required.'
                ]);
            }
        } elseif ($this->mode === 'add') {
            // For new customers, always require verification
            $this->phoneVerified = false;
        }
    }

    public function updatedSecondaryEmail($value)
    {
        // Clear validation errors for real-time feedback
        $this->resetErrorBag('secondary_email');
        
        // Handle verification status based on original value comparison
        if ($this->mode === 'edit' && $this->editId) {
            if ($value === $this->originalSecondaryEmail) {
                // Reverted to original value - restore original verification status
                $customer = $this->getCustomerSafely($this->editId);
                $this->secondaryEmailVerified = $customer ? !is_null($customer->secondary_email_verified_at) : false;
            } else {
                // Changed from original value - require verification
                $this->secondaryEmailVerified = false;
                
                // Dispatch event for immediate UI feedback
                $this->dispatch('verification-reset-immediate', [
                    'field' => 'secondary_email',
                    'message' => 'Secondary email changed. Verification required.'
                ]);
            }
        } elseif ($this->mode === 'add') {
            // For new customers, always require verification
            $this->secondaryEmailVerified = false;
        }
    }

    // Handle secondary phone changes and update verification status
    public function updatedSecondaryPhone($value)
    {
        // Clear validation errors
        $this->resetErrorBag('secondary_phone');
        
        // Handle verification status
        $this->handleSecondaryPhoneVerificationStatus();
    }

    public function updatedSecondaryCountryCode($value)
    {
        // Clear validation errors
        $this->resetErrorBag('secondary_country_code');
        
        // Handle verification status
        $this->handleSecondaryPhoneVerificationStatus();
    }
    
    /**
     * Handle secondary phone verification status when phone or country code changes
     */
    private function handleSecondaryPhoneVerificationStatus()
    {
        // Handle verification status based on original value comparison
        if ($this->mode === 'edit' && $this->editId) {
            if ($this->secondary_phone === $this->originalSecondaryPhone && 
                $this->secondary_country_code === $this->originalSecondaryCountryCode) {
                // Reverted to original values - restore original verification status
                $customer = $this->getCustomerSafely($this->editId);
                $this->secondaryPhoneVerified = $customer ? !is_null($customer->secondary_phone_verified_at) : false;
            } else {
                // Changed from original values - require verification
                $this->secondaryPhoneVerified = false;
            }
        } elseif ($this->mode === 'add') {
            // For new customers, always require verification
            $this->secondaryPhoneVerified = false;
        }
    }

    public function index()
    {
        return view('backend.customers');
    }

    public function mount()
    {
        $this->title = 'Customers';
        $this->form = false;
        $this->list = true;
        $this->view = false;
        $this->mode = 'list';
        $this->editId = '';
        $this->currentStep = 1;
        $this->formKey = uniqid(); // Initialize form key for unique wire:key
        $this->loadAddressTypes();
        $this->loadCountries();
        $this->loadSpecies();
        
        // Initialize address defaults as per memory specification
        $defaultAddressType = optional($this->addressTypes->where('name', 'pickup_dropoff')->first())->id;
        if ($defaultAddressType) {
            $this->address_type_id = $defaultAddressType;
        }
        $this->address_country = 'SG';
    }

    public function loadAddressTypes()
    {
        $this->addressTypes = AddressType::all();
    }

    public function loadCountries()
    {
        // For now, just keep the main countries with codes
        $this->countries = [
            'SG' => 'Singapore',
            'MY' => 'Malaysia',
            'TH' => 'Thailand', 
            'ID' => 'Indonesia',
            'PH' => 'Philippines',
            'VN' => 'Vietnam',
            'OTHER' => 'Other'
        ];
    }

    public function loadSpecies()
    {
        $this->species = Species::select('id', 'name')->orderBy('name')->get();
    }

    /**
     * Computed property to determine if customer is truly active
     * Based on: is_active = true AND both email and phone are verified
     */
    public function getIsCustomerActiveProperty()
    {
        return $this->is_active && $this->emailVerified && $this->phoneVerified;
    }

    /**
     * Safely get customer data (excludes soft deleted records)
     * @param int $id
     * @return User|null
     */
    private function getCustomerSafely($id)
    {
        return User::where('id', $id)->role('customer')->first();
    }

    // ===========================================
    // MAIN NAVIGATION METHODS
    // ===========================================

    public function showForm()
    {
        $this->resetForm();
        $this->resetErrorBag(); // Clear all validation errors
        $this->form = true;
        $this->list = false;
        $this->currentStep = 1;
        $this->mode = 'add';
        $this->editId = '';
        $this->loadAddressTypes();
        $this->loadCountries();
        
        // Extra safety: ensure address form is completely clean for new customer
        $this->resetAddressForm();
        
        // Set defaults for new customer as per memory specification
        $defaultAddressType = optional($this->addressTypes->where('name', 'pickup_dropoff')->first())->id;
        if ($defaultAddressType) {
            $this->address_type_id = $defaultAddressType;
        }
        $this->address_country = 'SG';
    }

    public function showList()
    {
        $this->form = false;
        $this->list = true;
        $this->view = false;
        $this->resetForm();
        $this->currentStep = 1;
    }

    public function toggleFilterModal()
    {
        $this->showFilterModal = !$this->showFilterModal;
    }

    public function clearFilters()
    {
        $this->filterStatus = ''; // Reset to empty string for radio button
        $this->filterVerification = [];
        $this->filterSpecies = [];
    }

    public function applyFilters()
    {
        $this->showFilterModal = false;
        // Filters will be applied automatically through the render method
    }

    public function removeSpeciesFilter($speciesId)
    {
        // Remove the specific species ID from the filter array
        $this->filterSpecies = array_values(array_filter($this->filterSpecies, function($id) use ($speciesId) {
            return $id != $speciesId;
        }));
    }

    public function resetForm()
    {
        // Step 1 fields
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone = '';
        $this->country_code = '+65';
        $this->dob = '';
        $this->passport_nric_fin_number = '';
        $this->image = '';

        // Step 2 fields  
        $this->secondary_first_name = '';
        $this->secondary_last_name = '';
        $this->secondary_email = '';
        $this->secondary_phone = '';
        $this->secondary_country_code = '+65';

        // Step 3 fields
        $this->savedAddresses = [];
        $this->newAddress = [
            'address_type_id' => '',
            'label' => '',
            'country' => '',
            'postal_code' => '',
            'address_line1' => '',
            'address_line2' => '',
            'is_billing_address' => false,
            'is_shipping_address' => false,
        ];
        
        // Reset individual address properties (used in form)
        $this->address_id = null;
        $this->address_type_id = '';
        $this->address_label = '';
        $this->address_country = '';
        $this->address_postal_code = '';
        $this->address_line1 = '';
        $this->address_line2 = '';
        $this->is_billing_address = false;
        $this->is_shipping_address = false;

        // Reset verification statuses
        $this->emailVerified = false;
        $this->phoneVerified = false;
        $this->secondaryEmailVerified = false;
        $this->secondaryPhoneVerified = false;

        // Reset original values (for revert comparison)
        $this->originalEmail = '';
        $this->originalPhone = '';
        $this->originalCountryCode = '';
        $this->originalSecondaryEmail = '';
        $this->originalSecondaryPhone = '';
        $this->originalSecondaryCountryCode = '';

        // Reset OTP
        $this->showOtpModal = false;
        $this->otpType = '';
        $this->otpDigits = ['', '', '', '', '', ''];
        $this->otpMessage = '';
        
        // Refresh form key to force re-rendering
        $this->formKey = uniqid();
        
        $this->resetErrorBag();
    }

    public function clearForm()
    {
        $this->resetForm();
    }
    
    /**
     * Reset only address form fields
     */
    public function resetAddressForm()
    {
        // Reset individual address properties (used in form)
        $this->address_id = null;
        $this->address_type_id = '';
        $this->address_label = '';
        $this->address_country = '';
        $this->address_postal_code = '';
        $this->address_line1 = '';
        $this->address_line2 = '';
        $this->is_billing_address = false;
        $this->is_shipping_address = false;

        // Apply sensible defaults only when preparing a fresh form
        if ($this->mode !== 'edit') {
            $defaultAddressType = optional($this->addressTypes->where('name', 'pickup_dropoff')->first())->id;
            if ($defaultAddressType) {
                $this->address_type_id = $defaultAddressType;
            }
            $this->address_country = 'SG';
        }
        
        // Clear address validation errors
        $this->resetErrorBag([
            'address_type_id', 'address_label', 'address_country', 
            'address_postal_code', 'address_line1', 'address_line2'
        ]);
        
        \Log::info('Address form reset for new customer', [
            'mode' => $this->mode,
            'address_id' => $this->address_id
        ]);
    }
    
    /**
     * Clear Step 1 form fields
     */
    public function clearStep1Form()
    {
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone = '';
        $this->country_code = '+65';
        $this->dob = '';
        $this->passport_nric_fin_number = '';
        
        // Clear validation errors for step 1
        $this->resetErrorBag([
            'first_name', 'last_name', 'email', 'phone', 
            'country_code', 'dob', 'passport_nric_fin_number'
        ]);
        

    }
    
    /**
     * Clear Step 2 form fields
     */
    public function clearStep2Form()
    {
        $this->secondary_phone = '';
        $this->secondary_country_code = '+65';
        $this->secondaryPhoneVerified = false;
        
        // Clear validation errors for step 2
        $this->resetErrorBag([
            'secondary_phone', 'secondary_country_code'
        ]);
        

    }

    // ===========================================
    // STEP NAVIGATION METHODS
    // ===========================================

    public function setCurrentStep(int $step)
    {
        if ($step >= 1 && $step <= 3) {
            $this->currentStep = $step;
        }
    }

    public function nextStep()
    {
        if ($this->currentStep == 1) {
            // Validate Step 1 fields before proceeding
            $emailRule = 'required|email';
            if ($this->mode === 'add') {
                $emailRule .= '|unique:users,email,NULL,id,deleted_at,NULL';
            } elseif ($this->mode === 'edit' && $this->editId) {
                $emailRule .= '|unique:users,email,' . $this->editId . ',id,deleted_at,NULL';
            }
            
            $messages = [
                'phone.required' => 'The Mobile Number field is required.',
                'dob.required' => 'The Date of Birth field is required.',
                'passport_nric_fin_number.required' => 'The Passport / NRIC / FIN Number field is required.',
                'passport_nric_fin_number.size' => 'Enter exactly the last 4 characters of your Passport / NRIC / FIN Number.',
                'passport_nric_fin_number.regex' => 'Enter exactly 4 alphanumeric characters (A-Z and 0-9)',
            ];

            $this->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => $emailRule,
                'phone' => 'required|string|max:20',
                'dob' => 'required|date',
                'passport_nric_fin_number' => 'required|string|size:4|regex:/^[A-Za-z0-9]{4}$/',
            ], $messages);
            
            // Save Step 1 data directly to database
            if ($this->mode === 'edit') {
                $this->saveStep1Update();
            } else {
                $this->saveStep1Create();
            }
            
            // Only proceed to step 2 if no validation errors occurred
            if (!$this->getErrorBag()->any()) {
                // Clear any old form2 validation errors when navigating to step 2
                $this->resetErrorBag(['secondary_first_name', 'secondary_last_name', 'secondary_email', 'secondary_phone']);
                $this->currentStep = 2;
            }
        } elseif ($this->currentStep == 2) {
            // Basic frontend validation only - CustomerService handles all business logic validation
            $this->validate([
                'secondary_first_name' => 'required|string|max:255',
                'secondary_last_name' => 'required|string|max:255',
                'secondary_email' => 'required|email|max:255',
                'secondary_phone' => 'required|string|max:20',
            ]);
            
            // Save Step 2 data directly to database
            if ($this->mode === 'edit') {
                $this->saveStep2Update();
            } else {
                $this->saveStep2Create();
            }
            
            // Only move to step 3 if no validation errors occurred
            if (!$this->getErrorBag()->any()) {
                $this->currentStep = 3;
                
                // For new customers, ensure address form is completely clean
                if ($this->mode === 'add') {
                    $this->resetAddressForm();
                }
            }
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            // Clear validation errors when going back
            $this->resetErrorBag();
            $this->currentStep--;
        }
    }
    
    /**
     * Smart back navigation - goes to previous step or customer list
     */
    public function goBack()
    {
        if ($this->view) {
            // If in view mode, go back to customer list
            $this->showList();
        } elseif ($this->form && $this->currentStep > 1) {
            // If in form mode and not on step 1, go to previous step and clear errors
            $this->resetErrorBag();
            $this->currentStep--;
        } else {
            // If on step 1 or any other case, go back to customer list
            $this->showList();
        }
    }

    // ===========================================
    // REAL-TIME VALIDATION ERROR CLEARING
    // ===========================================
    
    // Clear validation errors when user updates fields
    public function updatedFirstName()
    {
        $this->resetErrorBag('first_name');
    }
    
    public function updatedLastName()
    {
        $this->resetErrorBag('last_name');
    }
    

    

    
    public function updatedDob()
    {
        $this->resetErrorBag('dob');
    }
    
    // Passport/NRIC/FIN number validation removed - handled by enhanced method below
    
    public function updatedSecondaryFirstName()
    {
        $this->resetErrorBag('secondary_first_name');
    }
    
    public function updatedSecondaryLastName()
    {
        $this->resetErrorBag('secondary_last_name');
    }
    
    public function updatedCountryCode($value)
    {
        $this->resetErrorBag('country_code');
        
        // Handle verification status based on original value comparison
        if ($this->mode === 'edit' && $this->editId) {
            if ($this->phone === $this->originalPhone && $value === $this->originalCountryCode) {
                // Reverted to original phone with original country code - restore original verification status
                $customer = $this->getCustomerSafely($this->editId);
                $this->phoneVerified = $customer ? !is_null($customer->phone_verified_at) : false;
            } else {
                // Changed from original values - require verification
                $this->phoneVerified = false;
                
                // Dispatch event for immediate UI feedback
                $this->dispatch('verification-reset-immediate', [
                    'field' => 'phone',
                    'message' => 'Country code changed. Phone verification required.'
                ]);
            }
        }
    }
    
    // Removed updatedSecondaryCountryCode() to prevent focus loss - using event-based approach instead
    
    public function updatedAddressPostalCode()
    {
        $this->resetErrorBag('address_postal_code');
        
        // Clean postal code for Singapore (remove non-digits) - JavaScript handles the autofill
        if ($this->address_country === 'SG' && $this->address_postal_code) {
            $this->address_postal_code = preg_replace('/\D/', '', $this->address_postal_code);
            
            // Auto-trigger search when postal code is 6 digits
            if (strlen($this->address_postal_code) === 6) {
                $this->searchAddressByPostalCode();
            }
        }
    }
    
    public function updatedAddressLine1()
    {
        $this->resetErrorBag('address_line1');
    }
    
    public function updatedAddressLine2()
    {
        $this->resetErrorBag('address_line2');
    }
    
    public function updatedAddressCountry()
    {
        $this->resetErrorBag('address_country');
        
        // Note: Auto-search is now handled by Alpine.js frontend for better UX with spinner
        // The searchAddressByPostalCode() method is still available for manual triggers
    }
    
    public function updatedAddressLabel()
    {
        $this->resetErrorBag('address_label');
    }
    
    public function updatedPassportNricFinNumber()
    {
        $this->resetErrorBag('passport_nric_fin_number');
        
        // Add real-time validation for passport/NRIC/FIN number
        if (!empty($this->passport_nric_fin_number)) {
            // Check if exactly 4 characters
            if (strlen($this->passport_nric_fin_number) !== 4) {
                $this->addError('passport_nric_fin_number', 'Enter exactly the last 4 characters of your Passport / NRIC / FIN Number.');
                return;
            }
            
            // Check if alphanumeric only
            if (!ctype_alnum($this->passport_nric_fin_number)) {
                $this->addError('passport_nric_fin_number', 'Enter exactly 4 alphanumeric characters (A-Z and 0-9)');
                return;
            }
        }
    }
    /**
     * Search address by postal code using Livewire
     */
    public function searchAddressByPostalCode()
    {
        try {
            // Validate postal code format for Singapore
            if ($this->address_country !== 'SG') {
                return;
            }
            
            if (!$this->address_postal_code || !preg_match('/^\d{6}$/', $this->address_postal_code)) {
                return;
            }
            
            // Use the OneMapService to search for address
            $oneMapService = app(\App\Services\OneMapService::class);
            $result = $oneMapService->searchByPostalCode($this->address_postal_code);
            
            if ($result['success'] && $result['data']) {
                $formattedData = $oneMapService->formatAddressData($result['data']);
                
                // Update address fields directly (more reliable than fill() for this case)
                if (isset($formattedData['address_line_1'])) {
                    $this->address_line1 = $formattedData['address_line_1'];
                }
                if (isset($formattedData['address_line_2'])) {
                    $this->address_line2 = $formattedData['address_line_2'];
                }
                
                // Clear validation errors
                $this->resetErrorBag(['address_line1', 'address_line2']);
                

                
                // Force component refresh to ensure UI updates
                
                // Dispatch event with address data for direct DOM manipulation (like customer side)
                $this->dispatch('fill-address-fields', [
                    'address_line_1' => $formattedData['address_line_1'],
                    'address_line_2' => $formattedData['address_line_2'],
                    'full_address' => $formattedData['full_address'] ?? '',
                    'block_number' => $formattedData['block_number'] ?? '',
                    'road_name' => $formattedData['road_name'] ?? '',
                    'building' => $formattedData['building'] ?? ''
                ]);
                
            } else {
                session()->flash('address_error', $result['error'] ?? 'Address not found for this postal code');
            }
            
        } catch (\Exception $e) {
            Log::error('Error searching address by postal code', [
                'postal_code' => $this->address_postal_code,
                'error' => $e->getMessage()
            ]);
            
            session()->flash('address_error', 'Error searching for address. Please enter manually.');
        }
    }
    
    /**
     * Manual search trigger for postal code
     */
    public function searchAddress()
    {
        $this->searchAddressByPostalCode();
    }
    
    /**
     * Clear address fields when no address is found
     */
    public function clearAddressFields()
    {
        $this->address_line1 = '';
        $this->address_line2 = '';
        $this->resetErrorBag(['address_line1', 'address_line2']);
    }
    
    /**
     * Update address fields from JavaScript autofill (kept for backward compatibility)
     */
    public function updateAddressFromAutofill($addressData)
    {
        try {
            if (isset($addressData['address_line_1'])) {
                $this->address_line1 = $addressData['address_line_1'];
            }
            if (isset($addressData['address_line_2'])) {
                $this->address_line2 = $addressData['address_line_2'];
            }
            
            // Clear any validation errors for address fields
            $this->resetErrorBag(['address_line1', 'address_line2']);
            
            // Dispatch event to update the form
            $this->dispatch('address-updated');
            
            // Flash a success message
            session()->flash('message', 'Address fields updated from postal code lookup');
            
        } catch (\Exception $e) {
            Log::error('Error updating address from autofill', [
                'error' => $e->getMessage(),
                'address_data' => $addressData
            ]);
            session()->flash('error', 'Error updating address fields: ' . $e->getMessage());
        }
    }



    /**
     * Save Step 1 data for new customer creation
     */
    private function saveStep1Create()
    {
        // For new customers, we need to create the customer first with Step 1 data
        $step1Data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'dob' => $this->dob,
            'passport_nric_fin_number' => $this->passport_nric_fin_number,
            'is_active' => $this->emailVerified && $this->phoneVerified,
        ];

        // Handle profile picture upload
        try {
            if ($this->profile_photo instanceof UploadedFile) {
                // We'll upload after customer creation, store temporarily
                $step1Data['profile_photo_temp'] = $this->profile_photo;
            }
        } catch (\Exception $e) {
            \Log::warning('Admin step1 image preparation warning', ['e' => $e->getMessage()]);
        }

        // Create customer with Step 1 data only
        $result = $this->customerService->createCustomer($step1Data, 'admin');

        if ($result['status'] === 'success') {
            // Store customer ID for Step 2
            $this->editId = $result['user_id'];

            // Upload profile photo if provided
            try {
                if ($this->profile_photo instanceof UploadedFile) {
                    $finalUrl = \App\Services\ImageService::uploadCustomerProfileImage($this->profile_photo, (int)$this->editId);
                    if ($finalUrl) {
                        \App\Models\User::where('id', $this->editId)->update(['image' => $finalUrl]);
                        $this->image = $finalUrl;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Admin step1 upload profile image failed', ['e' => $e->getMessage()]);
            }

            session()->flash('message', 'Customer profile has been successfully created.');
        } else {
            // Handle validation errors
            if (isset($result['errors'])) {
                foreach ($result['errors'] as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->addError($field, $message);
                    }
                }
            } else {
                session()->flash('error', $result['message']);
            }
        }
    }

    /**
     * Save Step 1 data for existing customer update
     */
    private function saveStep1Update()
    {
        if (!$this->editId) {
            session()->flash('error', 'No customer selected for update.');
            return;
        }

        // Get current customer data for comparison
        $customer = $this->getCustomerSafely($this->editId);
        $resetEmailVerification = $customer && $customer->email !== $this->email;
        $resetPhoneVerification = $customer && $customer->phone !== $this->phone;

        // Prepare Step 1 update data
        $step1Data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'dob' => $this->dob,
            'passport_nric_fin_number' => $this->passport_nric_fin_number,
            'is_active' => $this->emailVerified && $this->phoneVerified,
        ];

        // Handle verification status updates
        if ($resetEmailVerification) {
            $step1Data['email_verified_at'] = $this->emailVerified ? now() : null;
        }
        if ($resetPhoneVerification) {
            $step1Data['phone_verified_at'] = $this->phoneVerified ? now() : null;
        }

        // Handle profile picture upload
        try {
            if ($this->profile_photo instanceof UploadedFile) {
                // Delete previous image
                if (!empty($this->image)) {
                    \App\Services\ImageService::deleteCustomerProfileImage($this->image, (int)$this->editId);
                }
                // Upload new image
                $uploadedUrl = \App\Services\ImageService::uploadCustomerProfileImage($this->profile_photo, (int)$this->editId);
                if ($uploadedUrl) {
                    $step1Data['image'] = $uploadedUrl;
                    $this->image = $uploadedUrl . '?v=' . time();
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Admin step1 image upload warning', ['e' => $e->getMessage()]);
        }

        // Update customer with Step 1 data - using secure method that excludes email/phone
        // Email/phone should only be updated after successful OTP verification (consistent with user side)
        $result = $this->customerService->updateCustomerProfileBasicInfo($this->editId, $step1Data, 'admin');

        if ($result['status'] === 'success') {
            // Reload customer data to ensure component reflects actual database state
            // This prevents displaying unsaved email/phone changes in the form
            $this->reloadCustomerData();
            session()->flash('message', 'Customer profile updated successfully.');
        } else {
            // Handle validation errors
            if (isset($result['errors'])) {
                foreach ($result['errors'] as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->addError($field, $message);
                    }
                }
            } else {
                session()->flash('error', $result['message']);
            }
        }
    }

    /**
     * Save Step 2 data for new customer (update the customer created in Step 1)
     */
    private function saveStep2Create()
    {
        if (!$this->editId) {
            session()->flash('error', 'No customer found from Step 1.');
            return;
        }

        // Prepare Step 2 update data
        $step2Data = [
            'secondary_first_name' => $this->secondary_first_name,
            'secondary_last_name' => $this->secondary_last_name,
            'secondary_email' => $this->secondary_email,
            'secondary_phone' => $this->secondary_phone,
            'secondary_country_code' => $this->secondary_country_code,
        ];

        // Handle secondary verification status
        if ($this->secondaryEmailVerified) {
            $step2Data['secondary_email_verified_at'] = now();
        }
        if ($this->secondaryPhoneVerified) {
            $step2Data['secondary_phone_verified_at'] = now();
        }

        // Update customer with Step 2 data using dedicated secondary contact method
        $result = $this->customerService->updateSecondaryContact($this->editId, $step2Data, 'admin');

        if ($result['status'] === 'success') {
            session()->flash('message', 'Secondary contact saved successfully.');
        } else {
            // Handle validation errors
            if (isset($result['errors'])) {
                foreach ($result['errors'] as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->addError($field, $message);
                    }
                }
            } else {
                session()->flash('error', $result['message']);
            }
        }
    }

    /**
     * Save Step 2 data for existing customer update
     */
    private function saveStep2Update()
    {
        if (!$this->editId) {
            session()->flash('error', 'No customer selected for update.');
            return;
        }

        // Get current customer data for comparison
        $customer = $this->getCustomerSafely($this->editId);
        $resetSecondaryEmailVerification = $customer && $customer->secondary_email !== $this->secondary_email;
        $resetSecondaryPhoneVerification = $customer && $customer->secondary_phone !== $this->secondary_phone;

        // Prepare Step 2 update data
        $step2Data = [
            'secondary_first_name' => $this->secondary_first_name,
            'secondary_last_name' => $this->secondary_last_name,
            'secondary_email' => $this->secondary_email,
            'secondary_phone' => $this->secondary_phone,
            'secondary_country_code' => $this->secondary_country_code,
        ];

        // Handle verification status updates
        if ($resetSecondaryEmailVerification) {
            $step2Data['secondary_email_verified_at'] = $this->secondaryEmailVerified ? now() : null;
        }
        if ($resetSecondaryPhoneVerification) {
            $step2Data['secondary_phone_verified_at'] = $this->secondaryPhoneVerified ? now() : null;
        }

        // Update customer with Step 2 data using dedicated secondary contact method
        $result = $this->customerService->updateSecondaryContact($this->editId, $step2Data, 'admin');

        if ($result['status'] === 'success') {
            // Reload customer data to ensure component reflects actual database state
            // This prevents displaying unsaved secondary email/phone changes in the form
            $this->reloadCustomerData();
            session()->flash('message', 'Secondary contact updated successfully.');
        } else {
            // Handle validation errors
            if (isset($result['errors'])) {
                foreach ($result['errors'] as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->addError($field, $message);
                    }
                }
            } else {
                session()->flash('error', $result['message']);
            }
        }
    }

    // ===========================================
    // EDIT MODE METHODS FOR EMAIL AND PHONE
    // ===========================================

    public function enableEmailEdit()
    {
        $this->emailEditMode = true;
        $this->emailVerified = false;
    }

    public function enablePhoneEdit()
    {
        $this->phoneEditMode = true;
        $this->phoneVerified = false;
    }

    // ===========================================
    // OTP VERIFICATION METHODS  
    // ===========================================

    public function sendEmailOtp()
    {
        // Clear any existing errors
        $this->resetErrorBag('email');
        
        if (empty($this->email)) {
            $this->addError('email', 'Email is required to send OTP');
            return;
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('email', 'Please enter a valid email address');
            return;
        }

        try {
            // Use ProfileService for consistent validation across all platforms
            $result = $this->profileService->sendEmailOtpForProfileUpdate((int) ($this->editId ?: 0), $this->email);
            
            if (!$result['success']) {
                $this->addError('email', $result['message']);
                return;
            }
            
            // Reset OTP state on success
            $this->otpDigits = ['', '', '', '', '', ''];
            $this->otpMessage = '';
            $this->otpMessageType = '';
            
            $this->otpType = 'email';
            $this->showOtpModal = true;
            $this->otpMessage = 'OTP sent to ' . $this->email;
            $this->otpMessageType = 'success';
            
            // Dispatch event to initialize OTP modal
            $this->dispatch('otp-modal-opened');
            
            Log::info('Email OTP sent successfully in admin', ['email' => $this->email]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send email OTP in admin: ' . $e->getMessage());
            $this->addError('email', 'Failed to send OTP. Please try again.');
        }
    }

    public function sendMobileOtp()
    {
        // Clear any existing errors
        $this->resetErrorBag('phone');
        
        if (empty($this->phone)) {
            $this->addError('phone', 'Phone number is required to send OTP');
            return;
        }

        if (strlen($this->phone) < 8) {
            $this->addError('phone', 'Please enter a valid phone number');
            return;
        }

        try {
            // Use ProfileService for consistent validation across all platforms
            $result = $this->profileService->sendPhoneOtpForProfileUpdate((int) ($this->editId ?: 0), $this->phone, $this->country_code);
            
            if (!$result['success']) {
                $this->addError('phone', $result['message']);
                return;
            }
            
            // Reset OTP state on success
            $this->otpDigits = ['', '', '', '', '', ''];
            $this->otpMessage = '';
            $this->otpMessageType = '';
            
            $this->otpType = 'mobile';
            $this->showOtpModal = true;
            $this->otpMessage = 'OTP sent to ' . $this->country_code . ' ' . $this->phone;
            $this->otpMessageType = 'success';
            
            // Dispatch event to initialize OTP modal
            $this->dispatch('otp-modal-opened');
            
            Log::info('Mobile OTP sent successfully in admin', ['phone' => $this->country_code . $this->phone]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send mobile OTP in admin: ' . $e->getMessage());
            $this->addError('phone', 'Failed to send OTP. Please try again.');
        }
    }

    public function sendSecondaryEmailOtp()
    {
        $this->resetErrorBag('secondary_email');
        
        if (empty($this->secondary_email)) {
            $this->addError('secondary_email', 'Email is required to send OTP');
            return;
        }

        if (!filter_var($this->secondary_email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('secondary_email', 'Please enter a valid email address');
            return;
        }

        // Validate that secondary email is not the same as primary email
        if ($this->secondary_email === $this->email) {
            $this->addError('secondary_email', 'Secondary email cannot be the same as your primary email');
            return;
        }

        try {
            // Use the actual OtpService
            $this->otpService->sendOtp($this->secondary_email, 'email');
            
            // Reset OTP state
            $this->otpDigits = ['', '', '', '', '', ''];
            $this->otpMessage = '';
            $this->otpMessageType = '';

            $this->otpType = 'secondary_email';
            $this->showOtpModal = true;
            $this->otpMessage = 'OTP sent to ' . $this->secondary_email;
            $this->otpMessageType = 'success';
            
            // Dispatch event to initialize OTP modal
            $this->dispatch('otp-modal-opened');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $message = $errors['email'][0] ?? 'Please wait 60 seconds before requesting another OTP.';
            $this->addError('secondary_email', $message);
        } catch (\Exception $e) {
            Log::error('Failed to send secondary email OTP in admin: ' . $e->getMessage());
            $this->addError('secondary_email', 'Failed to send OTP. Please try again.');
        }
    }

    public function sendSecondaryMobileOtp()
    {
        $this->resetErrorBag('secondary_phone');
        
        if (empty($this->secondary_phone)) {
            $this->addError('secondary_phone', 'Phone number is required to send OTP');
            return;
        }

        if (strlen($this->secondary_phone) < 8) {
            $this->addError('secondary_phone', 'Please enter a valid phone number');
            return;
        }

        // Validate that secondary phone is not the same as primary phone
        if ($this->secondary_phone === $this->phone && $this->secondary_country_code === $this->country_code) {
            $this->addError('secondary_phone', 'Secondary phone cannot be the same as your primary phone');
            return;
        }

        try {
            $fullPhone = $this->secondary_country_code . $this->secondary_phone;
            
            // Use the actual OtpService
            $this->otpService->sendOtp($fullPhone, 'phone');
            
            // Reset OTP state
            $this->otpDigits = ['', '', '', '', '', ''];
            $this->otpMessage = '';
            $this->otpMessageType = '';

            $this->otpType = 'secondary_mobile';
            $this->showOtpModal = true;
            $this->otpMessage = 'OTP sent to ' . $this->secondary_country_code . ' ' . $this->secondary_phone;
            $this->otpMessageType = 'success';
            
            // Dispatch event to initialize OTP modal
            $this->dispatch('otp-modal-opened');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $message = $errors['phone'][0] ?? 'Please wait 60 seconds before requesting another OTP.';
            $this->addError('secondary_phone', $message);
        } catch (\Exception $e) {
            Log::error('Failed to send secondary mobile OTP in admin: ' . $e->getMessage());
            $this->addError('secondary_phone', 'Failed to send OTP. Please try again.');
        }
    }

    public function verifyOtp()
    {
        $otpCode = implode('', $this->otpDigits);
        
        if (strlen($otpCode) !== 6 || !ctype_digit($otpCode)) {
            $this->otpMessage = 'Please enter a valid 6-digit OTP.';
            $this->otpMessageType = 'error';
            // Clear all OTP digits and focus on first box
            $this->otpDigits = ['', '', '', '', '', ''];
            $this->dispatch('otp-error-clear-and-focus');
            return;
        }

        try {
            // Get the identifier based on OTP type
            $identifier = '';
            $type = '';
            
            switch ($this->otpType) {
                case 'email':
                    $identifier = $this->email;
                    $type = 'email';
                    break;
                case 'mobile':
                    $identifier = $this->country_code . $this->phone;
                    $type = 'phone';
                    break;
                case 'secondary_email':
                    $identifier = $this->secondary_email;
                    $type = 'email';
                    break;
                case 'secondary_mobile':
                    $identifier = $this->secondary_country_code . $this->secondary_phone;
                    $type = 'phone';
                    break;
            }

            // Use the actual OtpService to verify
            $result = $this->otpService->verifyOtp($identifier, $otpCode, $type);
            
            if ($result === 'success') {
                // Verify OTP and update the actual field values in database (like API and web user side)
                if ($this->mode === 'edit' && $this->editId) {
                    $updateResult = null;
                    
                    switch ($this->otpType) {
                        case 'email':
                            // Use same method as API: verify and update email
                            $updateResult = $this->customerService->updateVerifiedEmail($this->editId, $this->email);
                            if ($updateResult['status'] === 'success') {
                                $this->emailVerified = true;
                            }
                            break;
                            
                        case 'mobile':
                            // Use same method as API: verify and update phone
                            $updateResult = $this->customerService->updateVerifiedPhone($this->editId, $this->phone, $this->country_code);
                            if ($updateResult['status'] === 'success') {
                                $this->phoneVerified = true;
                            }
                            break;
                            
                        case 'secondary_email':
                            // For secondary contacts, just mark as verified (no dedicated update method exists)
                            $updateResult = $this->customerService->markFieldAsVerified($this->editId, 'secondary_email');
                            if ($updateResult['status'] === 'success') {
                                $this->secondaryEmailVerified = true;
                            }
                            break;
                            
                        case 'secondary_mobile':
                            // For secondary contacts, just mark as verified (no dedicated update method exists)
                            $updateResult = $this->customerService->markFieldAsVerified($this->editId, 'secondary_phone');
                            if ($updateResult['status'] === 'success') {
                                $this->secondaryPhoneVerified = true;
                            }
                            break;
                    }
                    
                    if ($updateResult && $updateResult['status'] === 'success') {
                        // Immediately set verification state based on OTP type
                        switch ($this->otpType) {
                            case 'email':
                                $this->emailVerified = true;
                                $this->emailEditMode = false;
                                break;
                            case 'mobile':
                                $this->phoneVerified = true;
                                $this->phoneEditMode = false;
                                break;
                            case 'secondary_email':
                                $this->secondaryEmailVerified = true;
                                $this->secondaryEmailEditMode = false;
                                break;
                            case 'secondary_mobile':
                                $this->secondaryPhoneVerified = true;
                                $this->secondaryPhoneEditMode = false;
                                break;
                        }
                        
                        // Reload customer data to ensure component reflects actual database state
                        $this->reloadCustomerData();
                        
                        // Simple component refresh after successful verification
                        $this->dispatch('otp-verified-refresh-page');
                    } else {
                        $errorMessage = $updateResult['message'] ?? 'Failed to update profile after verification';
                        $this->otpMessage = $errorMessage;
                        $this->otpMessageType = 'error';
                        return;
                    }
                } else {
                    // For add mode, just update component properties
                    switch ($this->otpType) {
                        case 'email':
                            $this->emailVerified = true;
                            break;
                        case 'mobile':
                            $this->phoneVerified = true;
                            break;
                        case 'secondary_email':
                            $this->secondaryEmailVerified = true;
                            break;
                        case 'secondary_mobile':
                            $this->secondaryPhoneVerified = true;
                            break;
                    }
                }
                
                $this->closeOtpModal();
                $successMessage = $this->mode === 'edit' ? 'OTP verified and profile updated successfully!' : 'OTP verified successfully!';
                session()->flash('message', $successMessage);
                
            } elseif ($result === 'expired') {
                $this->otpMessage = 'OTP has expired. Please request a new one.';
                $this->otpMessageType = 'error';
                // Clear all OTP digits and focus on first box
                $this->otpDigits = ['', '', '', '', '', ''];
                $this->dispatch('otp-error-clear-and-focus');
            } else {
                $this->otpMessage = 'Invalid OTP. Please try again.';
                $this->otpMessageType = 'error';
                // Clear all OTP digits and focus on first box
                $this->otpDigits = ['', '', '', '', '', ''];
                $this->dispatch('otp-error-clear-and-focus');
            }
            
        } catch (\Exception $e) {
            Log::error('OTP verification failed in admin: ' . $e->getMessage());
            $this->otpMessage = 'Verification failed. Please try again.';
            $this->otpMessageType = 'error';
            // Clear all OTP digits and focus on first box
            $this->otpDigits = ['', '', '', '', '', ''];
            $this->dispatch('otp-error-clear-and-focus');
        }
    }

    public function resendOtp()
    {
        // Reset OTP digits
        $this->otpDigits = ['', '', '', '', '', ''];
        
        try {
            // Resend based on type using actual OtpService
            switch ($this->otpType) {
                case 'email':
                    $this->otpService->sendOtp($this->email, 'email');
                    $this->otpMessage = 'OTP resent to ' . $this->email;
                    break;
                case 'mobile':
                    $fullPhone = $this->country_code . $this->phone;
                    $this->otpService->sendOtp($fullPhone, 'phone');
                    $this->otpMessage = 'OTP resent to ' . $this->country_code . ' ' . $this->phone;
                    break;
                case 'secondary_email':
                    $this->otpService->sendOtp($this->secondary_email, 'email');
                    $this->otpMessage = 'OTP resent to ' . $this->secondary_email;
                    break;
                case 'secondary_mobile':
                    $fullPhone = $this->secondary_country_code . $this->secondary_phone;
                    $this->otpService->sendOtp($fullPhone, 'phone');
                    $this->otpMessage = 'OTP resent to ' . $this->secondary_country_code . ' ' . $this->secondary_phone;
                    break;
            }
            
            $this->otpMessageType = 'success';
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle rate limiting
            $this->otpMessage = 'Please wait 60 seconds before requesting another OTP.';
            $this->otpMessageType = 'error';
        } catch (\Exception $e) {
            Log::error('Failed to resend OTP in admin: ' . $e->getMessage());
            $this->otpMessage = 'Failed to resend OTP. Please try again.';
            $this->otpMessageType = 'error';
        }
        
        // Dispatch event to reset OTP inputs
        $this->dispatch('otp-modal-opened');
    }

    public function closeOtpModal()
    {
        $this->showOtpModal = false;
        $this->otpDigits = ['', '', '', '', '', ''];
        $this->otpMessage = '';
        $this->otpType = '';
    }

    /**
     * Handle phone change events from Alpine.js phone input component
     */
    #[On('phone-changed')] 
    public function handlePhoneChanged($phone, $countryCode)
    {
        // Update the Livewire properties
        $this->phone = $phone;
        $this->country_code = $countryCode;
        
        // Handle verification status based on original value comparison
        if ($this->mode === 'edit' && $this->editId) {
            if ($phone === $this->originalPhone && $countryCode === $this->originalCountryCode) {
                // Reverted to original values - restore original verification status
                $customer = $this->getCustomerSafely($this->editId);
                $this->phoneVerified = $customer ? !is_null($customer->phone_verified_at) : false;
            } else {
                // Changed from original values - require verification
                $this->phoneVerified = false;
            }
        } elseif ($this->mode === 'add') {
            // For new customers, always require verification
            $this->phoneVerified = false;
        }
    }

    /**
     * Handle secondary phone change events from Alpine.js phone input component
     */
    #[On('secondary-phone-changed')] 
    public function handleSecondaryPhoneChanged($phone, $countryCode)
    {
        // Update the Livewire properties
        $this->secondary_phone = $phone;
        $this->secondary_country_code = $countryCode;
        
        // Handle verification status based on original value comparison
        if ($this->mode === 'edit' && $this->editId) {
            if ($phone === $this->originalSecondaryPhone && $countryCode === $this->originalSecondaryCountryCode) {
                // Reverted to original values - restore original verification status
                $customer = $this->getCustomerSafely($this->editId);
                $this->secondaryPhoneVerified = $customer ? !is_null($customer->secondary_phone_verified_at) : false;
            } else {
                // Changed from original values - require verification
                $this->secondaryPhoneVerified = false;
            }
        } elseif ($this->mode === 'add') {
            // For new customers, always require verification
            $this->secondaryPhoneVerified = false;
        }
    }

    /**
     * Refresh verification status from database after successful OTP verification
     */
    private function refreshVerificationStatus()
    {
        if ($this->mode === 'edit' && $this->editId) {
            $customer = $this->getCustomerSafely($this->editId);
            if ($customer) {
                $this->emailVerified = !is_null($customer->email_verified_at);
                $this->phoneVerified = !is_null($customer->phone_verified_at);
                $this->secondaryEmailVerified = !is_null($customer->secondary_email_verified_at);
                $this->secondaryPhoneVerified = !is_null($customer->secondary_phone_verified_at);
            }
        }
    }





    // ===========================================
    // ADDRESS MANAGEMENT METHODS
    // ===========================================



    // ===========================================
    // CUSTOMER MANAGEMENT METHODS  
    // ===========================================

    public function editCustomer($id)
    {
        $customer = User::with('addresses')->where('id', $id)->role('customer')->first();
        if ($customer) {
            $this->resetErrorBag(); // Clear all validation errors
            $this->editId = $id;
            $this->mode = 'edit';
            
            // Load customer data
            $this->first_name = $customer->first_name;
            $this->last_name = $customer->last_name;
            $this->email = $customer->email;
            $this->phone = $customer->phone;
            $this->country_code = $customer->country_code ?? '+65';
            $this->dob = $customer->dob?->format('Y-m-d');
            $this->passport_nric_fin_number = $customer->passport_nric_fin_number;
            $this->secondary_first_name = $customer->secondary_first_name;
            $this->secondary_last_name = $customer->secondary_last_name;
            $this->secondary_email = $customer->secondary_email;
            $this->secondary_phone = $customer->secondary_phone;
            $this->secondary_country_code = $customer->secondary_country_code ?? '+65';
            $this->is_active = $customer->is_active;
            
            // Existing profile image URL (works for full URL or storage path)
            $imgData = \App\Services\ImageService::getImageUrl($customer->image, null, $customer->first_name, $customer->last_name);
            $this->image = is_array($imgData) ? null : $imgData; // ensure a string URL for preview
            
            // Load verification status
            $this->emailVerified = !is_null($customer->email_verified_at);
            $this->phoneVerified = !is_null($customer->phone_verified_at);
            $this->secondaryEmailVerified = !is_null($customer->secondary_email_verified_at);
            $this->secondaryPhoneVerified = !is_null($customer->secondary_phone_verified_at);
            
            // Reset edit mode flags
            $this->emailEditMode = false;
            $this->phoneEditMode = false;
            
            // Store original values for comparison (to handle revert scenarios)
            $this->originalEmail = $customer->email;
            $this->originalPhone = $customer->phone;
            $this->originalCountryCode = $customer->country_code ?? '+65';
            $this->originalSecondaryEmail = $customer->secondary_email;
            $this->originalSecondaryPhone = $customer->secondary_phone;
            $this->originalSecondaryCountryCode = $customer->secondary_country_code ?? '+65';
            
            // Load addresses
            $this->savedAddresses = $customer->addresses->map(function($address) {
                $fullAddress = trim(($address->address_line1 ?? '') . ' ' . ($address->address_line2 ?? ''));
                if (empty($fullAddress)) {
                    $fullAddress = 'N/A';
                }
                
                $addressData = [
                    'id' => $address->id,
                    'address_type_id' => $address->address_type_id,
                    'address_type' => $address->addressType->name ?? 'N/A', // Fix field name
                    'label' => $address->label,
                    'country' => $address->country,
                    'postal_code' => $address->postal_code,
                    'address_line1' => $address->address_line1,
                    'address_line2' => $address->address_line2,
                    'full_address' => $fullAddress, // Add full address field
                    'is_billing_address' => $address->is_billing_address,
                    'is_shipping_address' => $address->is_shipping_address,
                    'address_type_name' => $address->addressType->name ?? 'N/A' // Keep for compatibility
                ];
                
                return $addressData;
            })->toArray();
            
            $this->form = true;
            $this->list = false;
            $this->currentStep = 1;
        }
    }

    /**
     * Reload customer data from database to sync component state
     * Used after save operations to ensure form reflects actual database state
     */
    private function reloadCustomerData()
    {
        if (!$this->editId) {
            return;
        }

        $customer = $this->getCustomerSafely($this->editId);
        if (!$customer) {
            return;
        }

        // Reload basic profile data
        $this->first_name = $customer->first_name;
        $this->last_name = $customer->last_name;
        $this->email = $customer->email;
        $this->phone = $customer->phone;
        $this->country_code = $customer->country_code ?? '+65';
        $this->dob = $customer->dob?->format('Y-m-d');
        $this->passport_nric_fin_number = $customer->passport_nric_fin_number;
        $this->is_active = $customer->is_active;

        // Reload secondary contact data
        $this->secondary_first_name = $customer->secondary_first_name;
        $this->secondary_last_name = $customer->secondary_last_name;
        $this->secondary_email = $customer->secondary_email;
        $this->secondary_phone = $customer->secondary_phone;
        $this->secondary_country_code = $customer->secondary_country_code ?? '+65';

        // Reload verification status
        $this->emailVerified = !is_null($customer->email_verified_at);
        $this->phoneVerified = !is_null($customer->phone_verified_at);
        $this->secondaryEmailVerified = !is_null($customer->secondary_email_verified_at);
        $this->secondaryPhoneVerified = !is_null($customer->secondary_phone_verified_at);

        // Update original values for future comparisons
        $this->originalEmail = $customer->email;
        $this->originalPhone = $customer->phone;
        $this->originalCountryCode = $customer->country_code ?? '+65';
        $this->originalSecondaryEmail = $customer->secondary_email;
        $this->originalSecondaryPhone = $customer->secondary_phone;
        $this->originalSecondaryCountryCode = $customer->secondary_country_code ?? '+65';
    }



    /**
     * Save or update address for customer
     */
    public function saveAddress()
    {
        // Validate using individual properties
        $this->validate([
            'address_type_id' => 'required|integer',
            'address_label' => 'required|string|max:255',
            'address_country' => 'required|string|max:2',
            'address_postal_code' => 'required|string|max:10',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
        ]);

        try {
            if ($this->editId) {
                // Prepare address data for CustomerService using individual properties
                $addressData = [
                    'address_type_id' => $this->address_type_id,
                    'label' => $this->address_label,
                    'country' => $this->address_country,
                    'postal_code' => $this->address_postal_code,
                    'address_line_1' => $this->address_line1, // API expects address_line_1
                    'address_line_2' => $this->address_line2, // API expects address_line_2
                    'is_billing_address' => $this->is_billing_address ?? false,
                    'is_shipping_address' => $this->is_shipping_address ?? false,
                ];

                // If editing existing address, include ID
                if ($this->address_id && is_numeric($this->address_id) && $this->address_id > 0) {
                    $addressData['id'] = $this->address_id;
                }

                // Use CustomerService to save address
                $result = $this->customerService->saveCustomerAddress($this->editId, $addressData);

                if ($result['status'] === 'success') {
                    // Refresh saved addresses list
                    $this->refreshSavedAddresses();
                    
                    // Reset individual properties
                    $this->address_id = null;
                    $this->address_type_id = '';
                    $this->address_label = '';
                    $this->address_country = '';
                    $this->address_postal_code = '';
                    $this->address_line1 = '';
                    $this->address_line2 = '';
                    $this->is_billing_address = false;
                    $this->is_shipping_address = false;
                    
                    // Also reset the newAddress array for compatibility
                    $this->newAddress = [
                        'id' => null,
                        'address_type_id' => '',
                        'label' => '',
                        'country' => '',
                        'postal_code' => '',
                        'address_line1' => '',
                        'address_line2' => '',
                        'is_billing_address' => false,
                        'is_shipping_address' => false,
                    ];
                    
                    // Force form re-render

                    session()->flash('message', $result['message']);
                } else {
                    session()->flash('error', $result['message']);
                }
            }
        } catch (Exception $e) {
            session()->flash('error', 'Failed to save address: ' . $e->getMessage());
        }
    }

    /**
     * Refresh saved addresses from database
     */
    private function refreshSavedAddresses()
    {

        
        if ($this->editId) {
            $customer = User::with('addresses.addressType')->find($this->editId);
            if ($customer) {
                $this->savedAddresses = $customer->addresses->map(function($address) {
                    $fullAddress = trim(($address->address_line1 ?? '') . ' ' . ($address->address_line2 ?? ''));
                    if (empty($fullAddress)) {
                        $fullAddress = 'N/A';
                    }
                    
                    return [
                        'id' => $address->id,
                        'address_type_id' => $address->address_type_id,
                        'address_type' => $address->addressType->name ?? 'N/A', // Fix field name
                        'label' => $address->label,
                        'country' => $address->country,
                        'postal_code' => $address->postal_code,
                        'address_line1' => $address->address_line1,
                        'address_line2' => $address->address_line2,
                        'full_address' => $fullAddress, // Add full address field
                        'is_billing_address' => $address->is_billing_address,
                        'is_shipping_address' => $address->is_shipping_address,
                        'address_type_name' => $address->addressType->name ?? 'N/A' // Keep for compatibility
                    ];
                })->toArray();
                
            }
        }
    }

    /**
     * Edit existing address - following customer side logic
     */
    public function editAddress($index)
    {
        if (isset($this->savedAddresses[$index])) {
            $address = $this->savedAddresses[$index];
            
            // Clear any existing validation errors
            $this->resetErrorBag();
            
            // Use individual properties for better Livewire binding
            $this->address_id = $address['id'] ?? null;
            $this->address_type_id = (string)($address['address_type_id'] ?? '');
            $this->address_label = $address['label'] ?? '';
            $this->address_country = $address['country'] ?? 'SG';
            $this->address_postal_code = $address['postal_code'] ?? '';
            $this->address_line1 = $address['address_line1'] ?? '';
            $this->address_line2 = $address['address_line2'] ?? '';
            $this->is_billing_address = (bool)($address['is_billing_address'] ?? false);
            $this->is_shipping_address = (bool)($address['is_shipping_address'] ?? false);
            

            
            // Also update the newAddress array for compatibility
            $this->newAddress = [
                'id' => $this->address_id,
                'address_type_id' => $this->address_type_id,
                'label' => $this->address_label,
                'country' => $this->address_country,
                'postal_code' => $this->address_postal_code,
                'address_line1' => $this->address_line1,
                'address_line2' => $this->address_line2,
                'is_billing_address' => $this->is_billing_address,
                'is_shipping_address' => $this->is_shipping_address,
            ];
            
            // Force form re-render
            
            // Force Livewire to refresh the component
            $this->dispatch('addressLoaded', [
                'address_id' => $this->address_id,
                'address_type_id' => $this->address_type_id,
                'address_label' => $this->address_label,
                'address_country' => $this->address_country,
                'address_postal_code' => $this->address_postal_code,
                'address_line1' => $this->address_line1,
                'address_line2' => $this->address_line2,
                'is_billing_address' => $this->is_billing_address,
                'is_shipping_address' => $this->is_shipping_address,
            ]);
            
            // Force component refresh
            $this->dispatch('$refresh');
            

            
            
        } else {
            session()->flash('error', 'Address not found at index: ' . $index);
        }
    }
    
    public function updatedAddressTypeId()
    {
        $this->resetErrorBag('address_type_id');

        // When address type changes, hide and reset incompatible flags to keep UI consistent
        // Get the address type name from the selected address_type_id
        $selectedTypeName = '';
        if (!empty($this->address_type_id)) {
            // Make sure we're working with the ID as an integer
            $addressTypeId = (int)$this->address_type_id;
            $addressType = AddressType::find($addressTypeId);
            if ($addressType) {
                $selectedTypeName = $addressType->name;
            }
        }
        
        // Handle null or empty address_type_id - default to pickup_dropoff
        if (empty($selectedTypeName)) {
            $selectedTypeName = 'pickup_dropoff';
        }
        
        // Clean the type name
        $selectedTypeName = strtolower(trim($selectedTypeName));
        
        // According to the specification:
        // Billing Address: Shows only shipping checkbox (hides billing)
        // Shipping Address: Shows only billing checkbox (hides shipping)
        // Pickup & Dropoff: Shows both checkboxes
        if ($selectedTypeName === 'billing') {
            // Hide billing checkbox → reset its value
            $this->is_billing_address = false;
        } elseif ($selectedTypeName === 'shipping') {
            // Hide shipping checkbox → reset its value
            $this->is_shipping_address = false;
        }
        // pickup_dropoff shows both; keep as-is
        
        // Force a refresh of the component
        $this->dispatch('$refresh');
    }
    
    /**
     * Clear Step 3 (address) form fields
     */
    public function clearAddressForm()
    {
        // Clear individual properties
        $this->address_id = null;
        $this->address_type_id = '';
        $this->address_label = '';
        $this->address_country = 'SG'; // Default to Singapore
        $this->address_postal_code = '';
        $this->address_line1 = '';
        $this->address_line2 = '';
        $this->is_billing_address = false;
        $this->is_shipping_address = false;
        
        // Also clear the newAddress array for compatibility
        $this->newAddress = [
            'id' => null,
            'address_type_id' => '',
            'label' => '',
            'country' => 'SG',
            'postal_code' => '',
            'address_line1' => '',
            'address_line2' => '',
            'is_billing_address' => false,
            'is_shipping_address' => false,
        ];
        
        // Force form re-render
        $this->formKey = uniqid();
        
        // Clear validation errors for step 3
        $this->resetErrorBag([
            'address_type_id', 'address_label', 'address_country', 
            'address_postal_code', 'address_line1', 'address_line2'
        ]);
        

    }
    


    /**
     * Delete address
     */
    public function deleteAddress($index)
    {
        if (isset($this->savedAddresses[$index]) && isset($this->savedAddresses[$index]['id'])) {
            try {
                $addressId = $this->savedAddresses[$index]['id'];
                
                $result = $this->customerService->deleteCustomerAddress($this->editId, $addressId);
                
                if ($result['status'] === 'success') {
                    $this->refreshSavedAddresses();
                    session()->flash('message', $result['message']);
                    Log::info('Admin address deleted successfully', ['address_id' => $addressId]);
                } else {
                    session()->flash('error', $result['message']);
                    Log::warning('Admin address deletion failed', ['result' => $result]);
                }
            } catch (Exception $e) {
                Log::error('Admin address deletion error', [
                    'error' => $e->getMessage(),
                    'index' => $index
                ]);
                session()->flash('error', 'Failed to delete address: ' . $e->getMessage());
            }
        } else {
            Log::error('Admin deleteAddress failed - address not found', [
                'index' => $index,
                'available_indices' => array_keys($this->savedAddresses)
            ]);
        }
    }

    public function toggleStatus($id)
    {
        $customer = User::where('id', $id)->role('customer')->first();
        if ($customer) {
            $customer->update(['is_active' => !$customer->is_active]);
            session()->flash('message', 'Customer status updated successfully!');
        } else {
            session()->flash('error', 'Customer not found or has been deleted!');
        }
    }

    public function viewCustomer($id)
    {
        try {
            $customer = User::with(['addresses.addressType'])->where('id', $id)->role('customer')->first();
            
            if (!$customer) {
                session()->flash('error', 'Customer not found!');
                return;
            }

            // Set view mode and load customer data
            $this->list = false;
            $this->form = false;
            $this->view = true;
            $this->editId = $id;

            // Load basic customer data for viewing
            $this->first_name = $customer->first_name ?? '';
            $this->last_name = $customer->last_name ?? '';
            $this->email = $customer->email ?? '';
            $this->phone = $customer->phone ?? '';
            $this->country_code = $customer->country_code ?? '+65';
            $this->dob = $customer->dob ? $customer->dob->format('Y-m-d') : '';
            $this->passport_nric_fin_number = $customer->passport_nric_fin_number ?? '';
            $this->is_active = $customer->is_active;
            $this->referal_code = $customer->referal_code;

            
            // Load secondary contact data
            $this->secondary_first_name = $customer->secondary_first_name ?? '';
            $this->secondary_last_name = $customer->secondary_last_name ?? '';
            $this->secondary_email = $customer->secondary_email ?? '';
            $this->secondary_phone = $customer->secondary_phone ?? '';
            $this->secondary_country_code = $customer->secondary_country_code ?? '+65';

            // Load verification status
            $this->emailVerified = !is_null($customer->email_verified_at);
            $this->phoneVerified = !is_null($customer->phone_verified_at);
            $this->secondaryEmailVerified = !is_null($customer->secondary_email_verified_at);
            $this->secondaryPhoneVerified = !is_null($customer->secondary_phone_verified_at);

            // Load addresses
            $this->addresses = $customer->addresses->toArray();

        } catch (Exception $e) {
            session()->flash('error', 'Failed to load customer details: ' . $e->getMessage());
        }
    }

    // ===========================================
    // CUSTOMER RESTORE METHOD
    // ===========================================

    public function restoreCustomer($customerId)
    {
        try {
            $customer = User::withTrashed()->find($customerId);
            
            if (!$customer) {
                session()->flash('error', 'Customer not found.');
                return;
            }
            
            if (!$customer->trashed()) {
                session()->flash('error', 'Customer account is not deleted.');
                return;
            }
            
            $customer->restore();
            
            session()->flash('message', 'Customer account has been restored successfully.');
            Log::info('Customer account restored', [
                'customer_id' => $customerId,
                'customer_email' => $customer->email,
                'restored_by' => auth()->id()
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to restore customer account', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Failed to restore customer account: ' . $e->getMessage());
        }
    }

    // ===========================================
    // UTILITY METHODS
    // ===========================================

    /**
     * Clear all validation errors
     */
    public function clearAllErrors()
    {
        $this->resetErrorBag();
    }



    // ===========================================
    // RENDER METHOD
    // ===========================================

    public function render()
    {
        $customers = collect();
        
        if ($this->list) {
            $query = User::withCount('pet','referredUsers')->role('customer')
                // Handle deleted customers filter
                ->when(!empty($this->filterDeleted), function ($q) {
                    if (in_array('deleted', $this->filterDeleted) && in_array('active', $this->filterDeleted)) {
                        // Show both active and deleted customers
                        $q->withTrashed();
                    } elseif (in_array('deleted', $this->filterDeleted) && !in_array('active', $this->filterDeleted)) {
                        // Show only deleted customers
                        $q->onlyTrashed();
                    }
                    // If only 'active' is selected or neither is selected, default behavior (only active)
                })
                ->when($this->search, function ($q) {
                    $q->where(function ($query) {
                        $query->where('first_name', 'like', '%' . $this->search . '%')
                            ->orWhere('last_name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%')
                            ->orWhere('phone', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->filterStatus, function ($q) {
                    if ($this->filterStatus === 'active') {
                        $q->where('is_active', true);
                    } elseif ($this->filterStatus === 'inactive') {
                        $q->where('is_active', false);
                    }
                    // If filterStatus is empty, no filter is applied (show all)
                })
                ->when(!empty($this->filterVerification), function ($q) {
                    if (in_array('mobile_verified', $this->filterVerification)) {
                        $q->where('phone_verified_at', '!=', null);
                    }
                    if (in_array('email_verified', $this->filterVerification)) {
                        $q->where('email_verified_at', '!=', null);
                    }
                })
                ->when(!empty($this->filterSpecies), function ($q) {
                    // Filter customers who have pets of selected species
                    $q->whereHas('pet', function ($petQuery) {
                        $petQuery->whereIn('species_id', $this->filterSpecies);
                    });
                });
            
            $customers = $query->orderBy('created_at', 'desc')->paginate($this->perPage);
        }
        
        // Always load address types and countries for form usage
        if ($this->addressTypes->isEmpty()) {
            $this->loadAddressTypes();
        }
        
        if (empty($this->countries)) {
            $this->loadCountries();
        }
        
        return view('livewire.backend.customer', [
            'customers' => $customers,
            'addressTypes' => $this->addressTypes,
            'countries' => $this->countries,
            'species' => $this->species,
            'formKey' => $this->formKey,
        ])->layout('layouts.backend.index');
    }
}