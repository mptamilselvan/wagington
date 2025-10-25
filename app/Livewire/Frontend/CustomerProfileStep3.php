<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use App\Models\AddressType;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerProfileStep3 extends Component
{
    protected $customerService;
    
    // Form properties for wire:model bindings
    public $address_id;
    public $address_type;
    public $label;
    public $country;
    public $postal_code;
    public $address_line1;
    public $address_line2;
    public $is_billing_address = false;
    public $is_shipping_address = false;
    
    // Validation rules for address fields
    protected function rules()
    {
        return [
            'address_type' => 'required|string',
            'label' => 'required|string|max:255',
            'country' => 'required|string|max:2',
            'postal_code' => 'required|string|max:10',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
        ];
    }
    
    // Custom error messages
    protected $messages = [
        'address_type.required' => 'Address type is required.',
        'label.required' => 'Label is required.',
        'country.required' => 'Country is required.',
        'postal_code.required' => 'Postal code is required.',
        'address_line1.required' => 'Address line 1 is required.',
    ];
    
    public function boot(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }
    
    public function mount()
    {
        // Initialize with old values to preserve data on validation errors
        $this->address_id = old('address_id', '');
        $this->address_type = old('address_type', 'pickup_dropoff'); // Ensure default value
        $this->label = old('label', '');
        $this->country = old('country', 'SG');
        $this->postal_code = old('postal_code', '');
        $this->address_line1 = old('address_line1', '');
        $this->address_line2 = old('address_line2', '');
        $this->is_billing_address = old('is_billing_address', false);
        $this->is_shipping_address = old('is_shipping_address', false);
        
        // Force the address_type to have a default value if it's null
        if (empty($this->address_type)) {
            $this->address_type = 'pickup_dropoff';
        }
    }
    
    // Computed properties for addresses and address types
    public function getSavedAddressesProperty()
    {
        return Auth::user()->addresses()->with('addressType')->get();
    }
    
    public function getAddressTypesProperty()
    {
        return AddressType::active()->ordered()->get();
    }
    
    public function updatedAddressType()
    {
        // Simple logging to track address type changes
        \Log::info('Address type changed', ['address_type' => $this->address_type]);
        
        // No need for complex event dispatching - PHP conditionals handle checkbox visibility
    }
    
    public function clearForm()
    {
        $this->address_id = '';
        $this->address_type = 'pickup_dropoff';
        $this->label = '';
        $this->country = 'SG';
        $this->postal_code = '';
        $this->address_line1 = '';
        $this->address_line2 = '';
        $this->is_billing_address = false;
        $this->is_shipping_address = false;
    }
    
    public function editAddress($addressId)
    {
        $address = Auth::user()->addresses()->find($addressId);
        if (!$address) {
            return;
        }
        
        // Update ALL Livewire properties explicitly as per project specification
        $this->address_id = $address->id;
        $this->address_type = $address->addressType->name ?? 'pickup_dropoff';
        $this->label = $address->label ?? '';
        $this->country = $address->country ?? 'SG';
        $this->postal_code = $address->postal_code ?? '';
        $this->address_line1 = $address->address_line1 ?? '';
        $this->address_line2 = $address->address_line2 ?? '';
        $this->is_billing_address = (bool) $address->is_billing_address;
        $this->is_shipping_address = (bool) $address->is_shipping_address;
        
        // Force component re-render to ensure dropdowns update
        $this->render();
        
        // Also dispatch Alpine.js event for postal code and other fields
        $this->dispatch('fill-address-form', [
            'id' => $address->id,
            'address_type' => $address->addressType->name ?? 'pickup_dropoff',
            'label' => $address->label ?? '',
            'country' => $address->country ?? 'SG',
            'postal_code' => $address->postal_code ?? '',
            'address_line_1' => $address->address_line1 ?? '',
            'address_line_2' => $address->address_line2 ?? '',
            'is_billing' => $address->is_billing_address,
            'is_shipping' => $address->is_shipping_address
        ]);
    }
    
    public function saveAddress()
    {
        // Validate form data before processing
        $this->validate();
        
        try {
            // Get the address type ID from the name
            $addressType = AddressType::where('name', $this->address_type)->first();
            if (!$addressType) {
                session()->flash('error', 'Invalid address type selected.');
                return;
            }
            
            // Prepare address data using the same format as API
            $addressData = [
                'address_type_id' => $addressType->id,
                'label' => $this->label,
                'country' => $this->country,
                'postal_code' => $this->postal_code,
                'address_line_1' => $this->address_line1, // API expects address_line_1
                'address_line_2' => $this->address_line2, // API expects address_line_2
                'is_billing_address' => (bool) $this->is_billing_address,
                'is_shipping_address' => (bool) $this->is_shipping_address,
            ];
            
            // If editing existing address, include ID
            if ($this->address_id && is_numeric($this->address_id) && $this->address_id > 0) {
                $addressData['id'] = $this->address_id;
            }
            
            // Use centralized CustomerService method (same as API, Admin, and Web)
            $result = $this->customerService->saveCustomerAddress(Auth::id(), $addressData);
            
            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
                $this->clearForm();
            } else {
                session()->flash('error', $result['message']);
            }
            
        } catch (\Exception $e) {
            Log::error('Address save error: ' . $e->getMessage());
            session()->flash('error', 'Failed to save address.');
        }
    }
    
    public function deleteAddress($addressId)
    {
        try {
            // Use centralized CustomerService method (same as API, Admin, and Web)
            $result = $this->customerService->deleteCustomerAddress(Auth::id(), $addressId);
            
            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Error deleting address: ' . $e->getMessage());
            session()->flash('error', 'Failed to delete address.');
        }
    }
    
    public function render()
    {
        return view('livewire.frontend.customer-profile-step3', [
            'savedAddresses' => $this->savedAddresses,
            'addressTypes' => $this->addressTypes
        ]);
    }
}