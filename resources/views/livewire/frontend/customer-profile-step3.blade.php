<div>
    <!-- Form Content -->
    <div>
        <div class="space-y-6" autocomplete="off" novalidate
              x-data="customerAddressForm()" 
              x-init="initType()" 
              @fill-address-form.window="fillForm($event.detail)"
              wire:ignore.self>
            @csrf
            <!-- Hidden field for address ID (used when editing existing address) -->
            <input type="hidden" id="address_id" name="address_id" wire:model="address_id">
            
            <!-- Address Type and Label -->
            <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
                <!-- Address Type -->
                <div>
                    @php
                        $addressTypeOptions = [];
                        foreach($addressTypes ?? [] as $addressType) {
                            $addressTypeOptions[] = [
                                'value' => $addressType->name,
                                'option' => $addressType->display_name
                            ];
                        }
                    @endphp
                    @component('components.dropdown-component', [
                        'wireModel' => 'address_type',
                        'id' => 'address_type',
                        'label' => 'Address Type',
                        'star' => true,
                        'error' => $errors->first('address_type'),
                        'placeholder_text' => 'Select address type',
                        'options' => $addressTypeOptions,
                        'value' => $address_type ?: 'pickup_dropoff',
                        'wireChange' => ''
                    ])
                    @endcomponent
                </div>
                
                <!-- Label -->
                <div>
                    @component('components.textbox-component', [
                        'wireModel' => 'label',
                        'id' => 'label',
                        'label' => 'Label',
                        'star' => true,
                        'error' => $errors->first('label'),
                        'placeholder' => 'e.g., Home, Office, etc.',
                        'value' => old('label', '')
                    ])
                    @endcomponent
                </div>
            </div>
            
            <!-- Country and Postal Code -->
            <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
                <!-- Country -->
                <div>
                    @php
                        $countryOptions = [
                            ['value' => 'SG', 'option' => 'Singapore'],
                            ['value' => 'MY', 'option' => 'Malaysia'],
                            ['value' => 'TH', 'option' => 'Thailand'],
                            ['value' => 'ID', 'option' => 'Indonesia'],
                            ['value' => 'PH', 'option' => 'Philippines'],
                            ['value' => 'VN', 'option' => 'Vietnam'],
                            ['value' => 'OTHER', 'option' => 'Other']
                        ];
                    @endphp
                    @component('components.dropdown-component', [
                        'wireModel' => 'country',
                        'id' => 'country',
                        'label' => 'Country',
                        'star' => true,
                        'error' => $errors->first('country'),
                        'placeholder_text' => 'Select country',
                        'options' => $countryOptions,
                        'value' => old('country', 'SG'),
                        'onchangeFn' => 'onCountryChange()'
                    ])
                    @endcomponent
                </div>
                
                <!-- Postal Code -->
                <div>
                    <label for="postal_code" class="block mb-2 text-sm text-gray-700">
                        Postal Code<span class="text-black-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="text"
                            id="postal_code"
                            name="postal_code"
                            wire:model.live="postal_code"
                            @input="handlePostalCodeInput()"
                            @keydown.enter.prevent="searchAddress()"
                            :placeholder="postalCodePlaceholder"
                            required
                            autocomplete="off"
                            :maxlength="country === 'SG' ? 6 : null"
                            :class="[
                                'form-input w-full pr-24',
                                autoFilled ? 'bg-green-50 border-green-300' : '',
                                '{{ $errors->first('postal_code') ? 'border-red-500' : '' }}'
                            ]"
                        >
                        <!-- Search Button -->
                        <button 
                            type="button"
                            @click.prevent="searchAddress()"
                            x-show="showSearchButton"
                            x-transition
                            class="absolute px-2 py-1 text-xs text-white transition-colors duration-200 transform -translate-y-1/2 bg-blue-600 rounded right-12 top-1/2 hover:bg-blue-700"
                            title="Search Address"
                        >
                            🔍
                        </button>
                        <!-- Loading Indicator -->
                        <div x-show="loading" 
                             x-transition
                             class="absolute transform -translate-y-1/2 right-3 top-1/2">
                            <svg class="w-5 h-5 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    @if($errors->first('postal_code'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->first('postal_code') }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500">
                        <span x-text="helpText"></span>
                        <span x-show="showFormatHint" x-text="formatHint" class="text-blue-600"></span>
                    </p>
                    
                    <!-- Feedback Message -->
                    <div x-show="feedback.show" 
                         x-transition
                         :class="[
                             'mt-3 p-3 rounded-lg text-sm flex items-start space-x-2',
                             feedback.type === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : '',
                             feedback.type === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : '',
                             feedback.type === 'warning' ? 'bg-yellow-50 text-yellow-700 border border-yellow-200' : '',
                             feedback.type === 'info' ? 'bg-blue-50 text-blue-700 border border-blue-200' : ''
                         ]">
                        <span class="flex-shrink-0 font-bold" x-text="feedback.icon"></span>
                        <span class="flex-1" x-text="feedback.message"></span>
                    </div>
                </div>
            </div>
            
            <!-- Address Lines -->
            <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
                <!-- Address Line 1 -->
                <div>
                    @component('components.textbox-component', [
                        'wireModel' => 'address_line1',
                        'id' => 'address_line1',
                        'label' => 'Address Line 1',
                        'star' => true,
                        'error' => $errors->first('address_line1'),
                        'placeholder' => 'Address line 1',
                        'value' => old('address_line1', '')
                    ])
                    @endcomponent
                </div>
                
                <!-- Address Line 2 -->
                <div>
                    @component('components.textbox-component', [
                        'wireModel' => 'address_line2',
                        'id' => 'address_line2',
                        'label' => 'Address Line 2',
                        'star' => false,
                        'error' => $errors->first('address_line2'),
                        'placeholder' => 'Address line 2 (optional)',
                        'value' => old('address_line2', '')
                    ])
                    @endcomponent
                </div>
            </div>

            <!-- Checkboxes -->
            @php
                // Handle null or empty address_type
                $rawAddressType = $address_type;
                if (empty($rawAddressType)) {
                    $rawAddressType = 'pickup_dropoff';
                }
                $selectedAddressType = strtolower(trim($rawAddressType));
                $showBilling = !in_array($selectedAddressType, ['billing']);
                $showShipping = !in_array($selectedAddressType, ['shipping']);
            @endphp
            <div class="space-y-3">
                
                @if($showBilling)
                <!-- Billing checkbox -->
                <div class="flex items-center">
                    <input 
                        type="checkbox"
                        id="is_billing_address"
                        name="is_billing_address"
                        value="1"
                        wire:model="is_billing_address"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    >
                    <label for="is_billing_address" class="block ml-2 text-sm text-gray-700">
                        Save this as my billing address.
                    </label>
                </div>
                @endif
                
                @if($showShipping)
                <!-- Shipping checkbox -->
                <div class="flex items-center">
                    <input 
                        type="checkbox"
                        id="is_shipping_address"
                        name="is_shipping_address"
                        value="1"
                        wire:model="is_shipping_address"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    >
                    <label for="is_shipping_address" class="block ml-2 text-sm text-gray-700">
                        Save this as my shipping address.
                    </label>
                </div>
                @endif
            </div>
            
    

             <!-- Form Buttons -->
                <div class="flex flex-col items-center justify-end pt-6 space-y-3 sm:flex-row sm:space-y-0 sm:space-x-4">
                    <button type="button" 
                            wire:click="clearForm"
                            class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] text-gray-500">
                        Cancel
                    </button>
                    <button type="button" 
                            wire:click="saveAddress"
                            class="button-primary-small bg-[#1B85F3]">
                        Save
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Saved Addresses with Livewire -->
        @if(count($savedAddresses ?? []) > 0)
        <div class="p-6 mt-8 bg-white border border-gray-200 rounded-lg shadow-sm saved-addresses-section sm:p-8">
            <h3 class="mb-4 text-base font-semibold text-gray-800">Saved Address</h3>
            
            <div class="w-full">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-sm font-normal text-left text-gray-600">Address Type</th>
                            <th class="px-4 py-3 text-sm font-normal text-left text-gray-600">Label</th>
                            <th class="px-4 py-3 text-sm font-normal text-left text-gray-600">Address</th>
                            <th class="px-4 py-3 text-sm font-medium text-right text-gray-700"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($savedAddresses as $address)
                        <tr class="border-b border-gray-100">
                            <td class="px-4 py-4 text-sm text-gray-900">
                                @php
                                    $typeDisplay = $address->addressType->display_name ?? ucfirst($address->label ?? 'Home');
                                    $typeDisplay = preg_replace('/\s*(address|location)$/i', '', $typeDisplay);
                                    $flags = [];
                                    if (!empty($address->is_billing_address)) { $flags[] = 'Billing'; }
                                    if (!empty($address->is_shipping_address)) { $flags[] = 'Shipping'; }
                                @endphp
                                @if(count($flags))
                                    {{ $typeDisplay }}@if(count($flags)), {{ implode(', ', $flags) }}@endif
                                @else
                                    {{ $typeDisplay }}
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900">{{ $address->label ?? 'Home' }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900">{{ $address->address_line1 ?? '' }}{{ $address->address_line2 ? ', ' . $address->address_line2 : '' }}</td>
                            <td class="px-4 py-4 text-right">
                                <div class="relative inline-block">
                                    @php
                                        $menuItems = [
                                            [
                                                'label' => 'Edit',
                                                'action' => 'editAddress(' . $address->id . ')'
                                            ],
                                            [
                                                'label' => 'Delete', 
                                                'action' => 'deleteAddress(' . $address->id . ')',
                                                'confirm' => 'Are you sure you want to delete this address?'
                                            ]
                                        ];
                                    @endphp
                                    
                                    <div class="relative" x-data="{ open: false, addressToDelete: null, shouldKeepOpen: false }" @click.outside="open = shouldKeepOpen">
                                        <button @click="open = !open" type="button" class="flex items-center text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                            </svg>
                                        </button>

                                        <div x-show="open" 
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="transform opacity-0 scale-95"
                                             x-transition:enter-end="transform opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="transform opacity-100 scale-100"
                                             x-transition:leave-end="transform opacity-0 scale-95"
                                             class="absolute right-0 z-10 mt-2 text-left origin-top-right bg-white rounded-md shadow-lg w-36 ring-1 ring-black ring-opacity-5 focus:outline-none"
                                             role="menu" 
                                             aria-orientation="vertical" 
                                             aria-labelledby="menu-button"
                                             x-cloak
                                        >
                                            <div class="py-1" role="none">
                                                <button type="button" 
                                                        wire:click="editAddress({{ $address->id }})" 
                                                        @click="open = false; addressToDelete = null; shouldKeepOpen = false"
                                                        class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                                        role="menuitem">Edit</button>
                                                <button type="button" 
                                                        @click="addressToDelete = {{ $address->id }}; shouldKeepOpen = true; open = false"
                                                        class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                                        role="menuitem">Delete</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Custom Delete Confirmation Modal -->
                                        <div x-cloak
                                             x-show="addressToDelete === {{ $address->id }}" 
                                             class="fixed inset-0 z-50 overflow-y-auto" 
                                             aria-labelledby="modal-title" 
                                             role="dialog" 
                                             aria-modal="true"
                                             x-transition:enter="ease-out duration-300"
                                             x-transition:enter-start="opacity-0"
                                             x-transition:enter-end="opacity-100"
                                             x-transition:leave="ease-in duration-200"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0">
                                            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                                                <div class="fixed inset-0 transition-opacity bg-black bg-opacity-50" aria-hidden="true"></div>
                                                <div class="relative bg-white rounded-lg shadow-lg" style="width: 450px; max-width: 90vw;">
                                                    <!-- Header -->
                                                    <div class="flex items-center justify-between px-4 py-3">
                                                        <h4 class="text-base font-medium text-gray-900">
                                                            Delete Record
                                                        </h4>
                                                        <button type="button" 
                                                                @click="addressToDelete = null; shouldKeepOpen = false"
                                                                class="text-gray-400 hover:text-gray-600">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Content -->
                                                    <div class="px-4 py-4">
                                                        <p class="text-sm text-left text-gray-600">
                                                            Are you sure you want to delete the record?
                                                        </p>
                                                    </div>
                                                    
                                                    <!-- Buttons -->
                                                    <div class="flex justify-end gap-3 px-4 py-3">
                                                        <button type="button" 
                                                                @click="addressToDelete = null"
                                                                class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] text-gray-500">
                                                            Cancel
                                                        </button>
                                                        <button type="button" 
                                                                wire:click="deleteAddress({{ $address->id }})"
                                                                @click="addressToDelete = null; shouldKeepOpen = false"
                                                                class="button-primary-small bg-[#1B85F3]">
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
// Alpine.js Address Form Component
function customerAddressForm() {
    return {
        // Address lookup state
        apiConfig: {
            baseUrl: '{{ url("/api") }}',
            searchEndpoint: '/address/search'
        },
        country: @json($country ?? 'SG'),
        postalCode: @json($postal_code ?? ''),
        loading: false,
        autoFilled: false,
        searchTimeout: null,
        isSearching: false,
        showSearchButton: false,
        showFormatHint: false,
        helpText: 'Address will be auto-filled for Singapore postal codes',
        formatHint: '• Format: 6 digits (e.g., 123456)',
        postalCodePlaceholder: 'Enter 6-digit postal code',
        feedback: {
            show: false,
            type: 'info',
            message: '',
            icon: 'ℹ'
        },
        
        initType() {
            // Initialize address lookup
            this.onCountryChange();
            // Sync with Livewire postal code
            this.syncPostalCodeWithLivewire();
            
            // Force sync dropdown with Livewire on page load
            this.$nextTick(() => {
                const dropdown = document.getElementById('address_type');
                if (dropdown && dropdown.value) {
                    // Forcing sync: dropdown value = dropdown.value, livewire value = this.$wire.address_type
                    this.$wire.set('address_type', dropdown.value);
                }
                
                // Add change listener to keep them in sync
                if (dropdown) {
                    dropdown.addEventListener('change', (e) => {
                        // Dropdown changed to: e.target.value
                        this.$wire.set('address_type', e.target.value);
                    });
                }
            });
        },
        
        // Sync Alpine postal code with Livewire property
        syncPostalCodeWithLivewire() {
            const postalCodeField = document.getElementById('postal_code');
            // Syncing postal code, field value: postalCodeField?.value
            // Current Alpine postalCode: this.postalCode
            if (postalCodeField && postalCodeField.value) {
                this.postalCode = postalCodeField.value;
            }
            // If postal code exists, update UI accordingly
            if (this.postalCode && this.postalCode.length === 6) {
                this.updateCountryUI();
            }
        },
        
        onTypeChange() {
            // This function can be removed since we're using PHP conditionals
        },
        
        // Address lookup functions
        onCountryChange() {
            this.clearAddressFields();
            this.clearFeedback();
            this.autoFilled = false;
            this.updateCountryUI();
        },
        
        updateCountryUI() {
            if (this.country === 'SG') {
                this.helpText = 'Address will be auto-filled for Singapore postal codes';
                this.formatHint = '• Format: 6 digits (e.g., 123456)';
                this.postalCodePlaceholder = 'Enter 6-digit postal code';
                this.showFormatHint = true;
            } else {
                this.helpText = 'Enter postal code for the selected country';
                this.formatHint = '';
                this.postalCodePlaceholder = 'Enter postal code';
                this.showFormatHint = false;
            }
            this.showSearchButton = this.country === 'SG';
        },
        
        handlePostalCodeInput() {
            // Get current value from Livewire field
            const postalCodeField = document.getElementById('postal_code');
            if (postalCodeField) {
                this.postalCode = postalCodeField.value || '';
            }
            
            this.clearAddressFields();
            this.clearFeedback();
            this.autoFilled = false;
            
            if (this.country === 'SG') {
                const cleaned = this.postalCode.replace(/\D/g, '');
                if (cleaned.length <= 6) {
                    this.postalCode = cleaned;
                    // Update Livewire field
                    if (postalCodeField && postalCodeField.value !== cleaned) {
                        postalCodeField.value = cleaned;
                        postalCodeField.dispatchEvent(new Event('input'));
                    }
                }
                
                if (cleaned.length === 6) {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.searchAddress();
                    }, 500);
                }
            }
        },
        
        async searchAddress() {
            if (this.country !== 'SG' || this.postalCode.length !== 6 || this.loading) {
                return;
            }
            
            this.loading = true;
            this.isSearching = true;
            this.clearFeedback();
            
            try {
                const response = await fetch(`${this.apiConfig.baseUrl}${this.apiConfig.searchEndpoint}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        postal_code: this.postalCode,
                        country: this.country
                    })
                });
                
                const data = await response.json();
                
                if (response.ok && data.status === 'success' && data.data) {
                    this.fillAddressFields(data.data);
                    this.showFeedback('success', `Address found and filled automatically for postal code ${this.postalCode}`);
                    this.autoFilled = true;
                } else if (data.status === 'not_found') {
                    this.showFeedback('warning', `⚠ No address found for postal code ${this.postalCode}. Please enter manually.`);
                } else {
                    this.showFeedback('error', data.message || '✗ Failed to search address. Please enter manually.');
                }
            } catch (error) {
                console.error('Address search error:', error);
                this.showFeedback('error', '✗ Failed to search address. Please enter manually.');
            } finally {
                this.loading = false;
                this.isSearching = false;
            }
        },
        
        fillAddressFields(data) {
            const addressLine1 = document.getElementById('address_line1');
            const addressLine2 = document.getElementById('address_line2');

            const line1 = data.address_line_1 ?? data.address_line1 ?? '';
            const line2 = data.address_line_2 ?? data.address_line2 ?? '';

            if (addressLine1) {
                addressLine1.value = line1;
                addressLine1.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (addressLine2) {
                addressLine2.value = line2;
                addressLine2.dispatchEvent(new Event('input', { bubbles: true }));
            }

            this.$wire.set('address_line1', line1);
            this.$wire.set('address_line2', line2);
        },
        
        clearAddressFields() {
            const addressLine1 = document.getElementById('address_line1');
            const addressLine2 = document.getElementById('address_line2');

            if (addressLine1) {
                addressLine1.value = '';
                addressLine1.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (addressLine2) {
                addressLine2.value = '';
                addressLine2.dispatchEvent(new Event('input', { bubbles: true }));
            }

            this.$wire.set('address_line1', '');
            this.$wire.set('address_line2', '');
        },
        
        showFeedback(type, message) {
            const icons = {
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ'
            };
            
            this.feedback = {
                show: true,
                type: type,
                message: message,
                icon: icons[type] || 'ℹ'
            };
            
            if (type === 'success') {
                setTimeout(() => {
                    this.clearFeedback();
                }, 5000);
            }
        },
        
        clearFeedback() {
            this.feedback.show = false;
        },
        
        // Function to fill form when editing address
        fillForm(addressData) {
            
            // Handle different possible property names
            const postalCode = addressData.postal_code || addressData.postalCode || addressData.postal || '';
            
            // Update Alpine.js state only (Livewire handles the field via wire:model)
            this.country = addressData.country || 'SG';
            this.postalCode = postalCode;
            this.updateCountryUI();
            
            // Set hidden address_id
            const addressIdField = document.getElementById('address_id');
            if (addressIdField) {
                addressIdField.value = addressData.id;            }
            
            setTimeout(() => {
                document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
            }, 100);
        }
    }
}
</script>