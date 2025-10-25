<div class="w-full">
    <form wire:submit.prevent="saveAddress" class="space-y-6" wire:key="address-form-{{ $formKey }}">
        <!-- First Row: Address Type / Label -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @php
                $addressTypeOptions = [];
                foreach($addressTypes as $type) {
                    $addressTypeOptions[] = [
                        'value' => $type->id,
                        'option' => $type->display_name
                    ];
                }
            @endphp
            @component('components.dropdown-component', [
                'wireModel' => 'address_type_id',
                'id' => 'address_type_id',
                'label' => 'Address Type',
                'star' => true,
                'options' => $addressTypeOptions,
                'error' => $errors->first('address_type_id'),
                'placeholder_text' => 'Select address type'
            ])
            @endcomponent

            @component('components.textbox-component', [
                'wireModel' => 'address_label',
                'id' => 'address_label',
                'label' => 'Label',
                'star' => true,
                'error' => $errors->first('address_label'),
                'placeholder' => 'e.g., Home, Office, etc.'
            ])
            @endcomponent
        </div>

        <!-- Second Row: Country / Postal Code -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2" 
             x-data="{
                loading: false,
                feedback: { show: false, type: '', message: '' },
                feedbackTimeout: null,
                showFeedback(type, message) {
                    this.feedback.show = true;
                    this.feedback.type = type;
                    this.feedback.message = message;
                    
                    // Clear any existing timeout
                    if (this.feedbackTimeout) {
                        clearTimeout(this.feedbackTimeout);
                    }
                    
                    // Set new timeout
                    this.feedbackTimeout = setTimeout(() => { 
                        this.feedback.show = false; 
                    }, 120000);
                },
                async searchAddress() {
                    if (!$wire.address_postal_code || $wire.address_postal_code.length !== 6 || $wire.address_country !== 'SG') {
                        return;
                    }
                    
                    this.loading = true;
                    
                    try {
                        const response = await fetch('/api/address/search', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                postal_code: $wire.address_postal_code,
                                country: 'SG'
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success && data.data) {
                            // Show feedback FIRST before updating Livewire
                            this.showFeedback('success', 'Address found and auto-filled!');
                            
                            // Wait a bit then update the field to avoid Alpine.js reset
                            setTimeout(() => {
                                $wire.set('address_line1', data.data.address_line1 || '');
                            }, 100);
                        } else {
                            $wire.address_line1 = '';
                            $wire.address_line2 = '';
                            <!-- this.showFeedback('error', 'Address not found for this postal code'); -->
                        }
                    } catch (error) {
                        this.showFeedback('error', 'Failed to search address. Please try again.');
                    } finally {
                        this.loading = false;
                    }
                }
             }">
            @php
                $countryOptions = [];
                foreach($countries as $code => $name) {
                    $countryOptions[] = [
                        'value' => $code,
                        'option' => $name
                    ];
                }
            @endphp
            @component('components.dropdown-component', [
                'wireModel' => 'address_country',
                'id' => 'address_country',
                'label' => 'Country',
                'star' => true,
                'options' => $countryOptions,
                'error' => $errors->first('address_country'),
                'placeholder_text' => 'Select country'
            ])
            @endcomponent

            <div>
                <label for="address_postal_code" class="block mb-2 text-sm font-normal text-gray-700" >Postal Code <span class="text-black-500">*</span></label>
                <div class="relative">
                    <input type="text" 
                           id="address_postal_code"
                           wire:model.live="address_postal_code" 
                           placeholder="Enter your postal code"
                           maxlength="6"
                           autocomplete="off"
                           @input="if ($event.target.value.length === 6 && $wire.address_country === 'SG') { setTimeout(() => searchAddress(), 100); }"
                           @keydown.enter.prevent="searchAddress()"
                           class="w-full form-input"
                           >
                    
                    <!-- Loading Spinner -->
                    <div x-show="loading" 
                         x-transition
                         class="absolute transform -translate-y-1/2 right-3 top-1/2">
                        <svg class="w-5 h-5 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('address_postal_code') <p class="mt-1 text-sm text-red-600" >{{ $message }}</p> @enderror
                

                <!-- Flash Message -->
                <div x-show="feedback.show" x-cloak 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform translate-y-2"
                     :class="[
                         'mt-3 p-3 rounded-lg text-sm flex items-start space-x-2',
                         feedback.type === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : '',
                         feedback.type === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : '',
                         feedback.type === 'warning' ? 'bg-yellow-50 text-yellow-700 border border-yellow-200' : '',
                         feedback.type === 'info' ? 'bg-blue-50 text-blue-700 border border-blue-200' : ''
                     ]"
                     >
                    <span class="flex-shrink-0 font-bold" x-text="feedback.icon"></span>
                    <span class="flex-1" x-text="feedback.message"></span>
                </div>
                
                <p class="mt-1 text-xs text-gray-500" >
                    @if($address_country === 'SG')
                        Address will be auto-filled for Singapore postal codes
                    @else
                        Enter postal code for your selected country
                    @endif
                </p>
            </div>
        </div>

        <!-- Third Row: Address Line 1 / Address Line 2 -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @component('components.textbox-component', [
                'wireModel' => 'address_line1',
                'id' => 'address_line1',
                'label' => 'Address Line 1',
                'star' => true,
                'error' => $errors->first('address_line1'),
                'placeholder' => 'Street address, building name'
            ])
            @endcomponent

            @component('components.textbox-component', [
                'wireModel' => 'address_line2',
                'id' => 'address_line2',
                'label' => 'Address Line 2',
                'star' => false,
                'error' => $errors->first('address_line2'),
                'placeholder' => 'Unit number, floor, etc.'
            ])
            @endcomponent
        </div>

        <!-- Fourth Row: Checkboxes -->
        @php
            // Get the address type name from the selected address_type_id
            $selectedTypeName = '';
            
            // Make sure we have addressTypes available
            $availableAddressTypes = $addressTypes ?? collect();
            
            if (!empty($address_type_id) && $availableAddressTypes->count() > 0) {
                // Find the address type by ID
                foreach($availableAddressTypes as $type) {
                    if ($type->id == $address_type_id) {
                        $selectedTypeName = $type->name;
                        break;
                    }
                }
            }
            
            // Handle null or empty address_type_id - default to pickup_dropoff
            if (empty($selectedTypeName)) {
                $selectedTypeName = 'pickup_dropoff';
            }
            
            // Clean the type name
            $selectedTypeName = strtolower(trim($selectedTypeName));
        @endphp
        <div class="space-y-4">
            @if(!in_array($selectedTypeName, ['billing']))
            <div class="flex items-center">
                <input type="checkbox" 
                       wire:model.live="is_billing_address" 
                       id="is_billing_address"
                       class="w-5 h-5 text-blue-600 transition-all duration-200 border-2 border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500/20 focus:ring-offset-2">
                <label for="is_billing_address" class="block pt-0.5 ml-3 text-gray-500">
                    Save this as my billing address
                </label>
            </div>
            @endif

            @if(!in_array($selectedTypeName, ['shipping']))
            <div class="flex items-center">
                <input type="checkbox" 
                       wire:model.live="is_shipping_address" 
                       id="is_shipping_address"
                       class="w-5 h-5 text-blue-600 transition-all duration-200 border-2 border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500/20 focus:ring-offset-2">
                <label for="is_shipping_address" class="block pt-0.5 ml-3 text-gray-500">
                    Save this as my shipping address
                </label>
            </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-4 pt-6 mt-8">
            <button wire:click="clearAddressForm" 
                class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] "
                >
                Clear
            </button>
            <button type="submit" 
                class="button-primary-small bg-[#1B85F3]"
                >
                Save
            </button>
        </div>
    </form>

    <!-- Saved Addresses Section -->
    @if($savedAddresses && count($savedAddresses) > 0)
        <div class="p-6 mt-8 bg-white border border-gray-200 rounded-lg shadow-sm saved-addresses-section sm:p-8">
            <h3 class="mb-4 text-base font-semibold text-gray-900">Saved Addresses</h3>
            
            <div class="w-full">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-sm font-medium text-left text-gray-700">Address Type</th>
                            <th class="px-4 py-3 text-sm font-medium text-left text-gray-700">Label</th>
                            <th class="px-4 py-3 text-sm font-medium text-left text-gray-700">Address</th>
                            <th class="px-4 py-3 text-sm font-medium text-right text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($savedAddresses as $index => $address)
                        <tr class="border-b border-gray-100">
                            <td class="px-4 py-4 text-sm text-gray-900">
                                @php
                                    $types = [];
                                    if (!empty($address['address_type'])) $types[] = $address['address_type'];
                                    if (!empty($address['is_billing_address'])) $types[] = 'Billing';
                                    if (!empty($address['is_shipping_address'])) $types[] = 'Shipping';
                                    $types = array_unique($types);
                                @endphp
                                {{ implode(', ', $types) ?: 'N/A' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900">{{ $address['label'] ?? 'N/A' }}</td>
                            <td class="px-4 py-4 text-sm text-gray-900">{{ $address['full_address'] ?? 'N/A' }}</td>
                            <td class="px-4 py-4 text-right">
                                <div class="relative inline-block">
                                    <div class="relative" x-data="{ open: false, addressToDelete: null }" @click.outside="open = false">
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
                                                        wire:click="editAddress({{ $index }})" 
                                                        @click="open = false"
                                                        class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                                        role="menuitem">Edit</button>
                                                <button type="button" 
                                                        @click="addressToDelete = {{ $index }}; open = false"
                                                        class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                                        role="menuitem">Delete</button>
                                            </div>
                                        </div>
                                        
                                        <!-- Custom Delete Confirmation Modal -->
                                        <div x-show="addressToDelete === {{ $index }}" 
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
                                                                @click="addressToDelete = null"
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
                                                                class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
                                                            Cancel
                                                        </button>
                                                        <button type="button" 
                                                                wire:click="deleteAddress({{ $index }})"
                                                                @click="addressToDelete = null"
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

<script>

        
        // Listen for address loaded event and force update form fields
        document.addEventListener('livewire:init', () => {
            Livewire.on('addressLoaded', (data) => {
                
                // Force update form fields with the loaded data
                setTimeout(() => {
                    const addressData = data[0]; // Livewire passes data as array
                    
                    // Update all form fields
                    const fields = {
                        'address_type_id': addressData.address_type_id,
                        'address_label': addressData.address_label,
                        'is_billing_address': addressData.is_billing_address,
                        'is_shipping_address': addressData.is_shipping_address,
                        'address_country': addressData.address_country,
                        'address_postal_code': addressData.address_postal_code,
                        'address_line1': addressData.address_line1,
                        'address_line2': addressData.address_line2
                    };
                    
                    Object.keys(fields).forEach(fieldName => {
                        const input = document.querySelector(`[wire\\:model\\.live="${fieldName}"]`);
                        if (input) {
                            input.value = fields[fieldName] || '';
                            // Trigger input event to notify Livewire
                            input.dispatchEvent(new Event('input', { bubbles: true }));

                        }
                    });
                    
                    // Update checkboxes
                    const billingCheckbox = document.querySelector('[wire\\:model\\.live="is_billing_address"]');
                    if (billingCheckbox) {
                        billingCheckbox.checked = addressData.is_billing_address;
                        billingCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    
                    const shippingCheckbox = document.querySelector('[wire\\:model\\.live="is_shipping_address"]');
                    if (shippingCheckbox) {
                        shippingCheckbox.checked = addressData.is_shipping_address;
                        shippingCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    

                }, 100); // Small delay to ensure DOM is ready
            });
        });
        

        // Simple address autofill for admin (using direct DOM manipulation like customer side)
        // Always reinitialize to handle Livewire updates
        
        try {
            window.adminAddressAutofill = {
                init() {
                    this.bindEvents();
                },
                

                
                bindEvents() {
                    // Find postal code input by ID (same as customer side)
                    const postalCodeInput = document.getElementById('address_postal_code');
                    const countrySelect = document.querySelector('select[wire\\:model\\.live="address_country"]');
                    

                    
                    if (postalCodeInput) {
                        postalCodeInput.addEventListener('input', (e) => {
                            this.handlePostalCodeInput(e.target.value);
                        });
                    }
                    
                    if (countrySelect) {
                        countrySelect.addEventListener('change', (e) => {
                            this.handleCountryChange(e.target.value);
                        });
                    }
                },
                
                handleCountryChange(country) {
                    // Clear address fields when country changes
                    if (country !== 'SG') {
                        this.clearAddressFields();
                    }
                },
                
                handlePostalCodeInput(postalCode) {
                    const countrySelect = document.querySelector('select[wire\\:model\\.live="address_country"]');
                    const country = countrySelect ? countrySelect.value : '';
                    
                    if (country === 'SG') {
                        // Clean postal code (digits only)
                        const cleanedCode = postalCode.replace(/\D/g, '');
                        
                        // Update input if cleaned
                        const postalCodeInput = document.querySelector('input[wire\\:model\\.live="address_postal_code"]');
                        if (postalCodeInput && postalCodeInput.value !== cleanedCode) {
                            postalCodeInput.value = cleanedCode;
                            // Trigger Livewire update
                            postalCodeInput.dispatchEvent(new Event('input'));
                        }
                        
                        // Auto-search is handled by Livewire backend (updatedAddressPostalCode method)
                        // JavaScript auto-search is disabled to avoid conflicts
                    }
                },
                
                async searchAddress(postalCode) {
                    try {
                        const url = '{{ url("/api/address/search") }}';
                        const payload = {
                            postal_code: postalCode,
                            country: 'SG'
                        };
                        
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(payload)
                        });
                        
                        const result = await response.json();
                        
                        if (result.success && result.data) {
                            this.fillAddressFields(result.data);
                        }
                    } catch (error) {
                        // Handle API error silently
                    }
                },
                
                fillAddressFields(data) {
                    // Use same approach as customer side - find by ID
                    const addressLine1Input = document.getElementById('address_line1');
                    const addressLine2Input = document.getElementById('address_line2');
                    
                    // Extract values from API response
                    const line1Value = data.address_line_1 || '';
                    const line2Value = data.address_line_2 || '';
                    
                    // Fill address line 1
                    if (addressLine1Input && line1Value) {
                        addressLine1Input.value = line1Value;
                        addressLine1Input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    
                    // Fill address line 2
                    if (addressLine2Input && line2Value) {
                        addressLine2Input.value = line2Value;
                        addressLine2Input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                },
                
                clearAddressFields() {
                    const addressLine1Input = document.querySelector('input[wire\\:model\\.live="address_line1"]');
                    const addressLine2Input = document.querySelector('input[wire\\:model\\.live="address_line2"]');
                    
                    if (addressLine1Input) {
                        addressLine1Input.value = '';
                        addressLine1Input.dispatchEvent(new Event('input'));
                    }
                    
                    if (addressLine2Input) {
                        addressLine2Input.value = '';
                        addressLine2Input.dispatchEvent(new Event('input'));
                    }
                }
            };
            

            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    window.adminAddressAutofill.init();
                });
            } else {
                window.adminAddressAutofill.init();
            }
        } catch (error) {
            // Silent error handling for production
        }
        
        // Listen for fill-address-fields event from Livewire (like customer side)
        document.addEventListener('livewire:init', () => {
            Livewire.on('fill-address-fields', (data) => {
                // Get form fields immediately - no delay needed since Livewire ensures DOM is ready
                const addressLine1 = document.getElementById('address_line1');
                const addressLine2 = document.getElementById('address_line2');
                
                if (addressLine1 && addressLine2) {
                    // Fix: Livewire dispatch sends data as array, so we need data[0]
                    // But let's handle both cases for safety
                    const addressData = Array.isArray(data) ? data[0] : data;
                    
                    // Use the same strategy as customer side
                    fillAddressFields(addressData, addressLine1, addressLine2);
                }
            });
        });
        
        // Address filling function (similar to customer side)
        function fillAddressFields(data, addressLine1, addressLine2) {
            // Strategy 1: Use provided address_line_1 and address_line_2 if available
            if (data && data.address_line_1) {
                addressLine1.value = data.address_line_1;
                
                // If address_line_2 is provided and not empty, use it
                if (data.address_line_2 && data.address_line_2.trim()) {
                    addressLine2.value = data.address_line_2;
                } else {
                    addressLine2.value = '';
                }
            } else {
                // Strategy 3: Fallback to building components
                buildAddressFromComponents(data, addressLine1, addressLine2);
            }
            
            // Visual feedback (green border)
            addressLine1.style.border = '2px solid green';
            addressLine2.style.border = '2px solid green';
            
            setTimeout(() => {
                addressLine1.style.border = '';
                addressLine2.style.border = '';
            }, 1000);
            
            // Trigger Livewire to sync the values back to the component
            addressLine1.dispatchEvent(new Event('input', { bubbles: true }));
            addressLine2.dispatchEvent(new Event('input', { bubbles: true }));
            
            // Also trigger change events for good measure
            addressLine1.dispatchEvent(new Event('change', { bubbles: true }));
            addressLine2.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Force focus and blur to ensure Livewire detects the change
            addressLine1.focus();
            addressLine1.blur();
            addressLine2.focus();
            addressLine2.blur();
        }
        
        // Smart address splitting (similar to customer side)
        function smartAddressSplit(data, addressLine1, addressLine2) {
            const fullAddress = data.full_address || '';
            
            if (fullAddress) {
                // Simple split logic - can be enhanced
                const parts = fullAddress.split(' ');
                if (parts.length > 4) {
                    const midPoint = Math.ceil(parts.length / 2);
                    addressLine1.value = parts.slice(0, midPoint).join(' ');
                    addressLine2.value = parts.slice(midPoint).join(' ');
                } else {
                    addressLine1.value = fullAddress;
                    addressLine2.value = '';
                }
            }
        }
        
        // Build address from components (similar to customer side)
        function buildAddressFromComponents(data, addressLine1, addressLine2) {
            let line1Parts = [];
            
            if (data.block_number) line1Parts.push(data.block_number);
            if (data.road_name) line1Parts.push(data.road_name);
            
            addressLine1.value = line1Parts.join(' ');
            addressLine2.value = data.building || '';
        }
        
        // Re-initialize after Livewire updates
        document.addEventListener('livewire:navigated', () => {
            setTimeout(() => window.adminAddressAutofill && window.adminAddressAutofill.init(), 100);
        });
        document.addEventListener('livewire:load', () => {
            setTimeout(() => window.adminAddressAutofill && window.adminAddressAutofill.init(), 100);
        });
        
        // Also listen for Livewire component updates
        document.addEventListener('livewire:update', () => {
            setTimeout(() => window.adminAddressAutofill && window.adminAddressAutofill.init(), 100);
        });
</script>