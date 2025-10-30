@props([
    'addresses' => [],
    'selectedShippingAddressId' => null,
    'selectedBillingAddressId' => null,
    'billingAddressSameAsShipping' => true,
    'sectionOpen' => true,
    'title' => 'Delivery & Billing Address'
])

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    {{-- Section Header --}}
    <button 
        type="button" 
        class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition-colors duration-200"
        wire:click="toggleAddressSection"
    >
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">
                1
            </div>
            <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
        </div>
        <div class="flex items-center space-x-2">
            @if($selectedShippingAddressId && ($billingAddressSameAsShipping || $selectedBillingAddressId) && !$sectionOpen)
                <span class="text-sm text-green-600 font-medium">✓ Complete</span>
            @endif
            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200 {{ $sectionOpen ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
    </button>

    {{-- Section Content --}}
    @if($sectionOpen)
        <div class="p-6 space-y-6">
            @if(count($addresses) > 0)
                {{-- Shipping Address Selection --}}
                <div>
                    <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4.5m8-4.5v10l-8 4.5m0-10L4 7m8 4.5v10M4 7v10l8 4.5"></path>
                        </svg>
                        Shipping Address
                    </h4>
                    <div class="grid gap-3">
                        @foreach($addresses as $addr)
                            <label class="flex items-start space-x-3 p-4 border-2 rounded-lg hover:border-gray-300 cursor-pointer transition-all duration-200 {{ $selectedShippingAddressId == $addr['id'] ? 'border-blue-600 bg-blue-50' : 'border-gray-200' }}">
                                <input 
                                    type="radio" 
                                    class="mt-1 text-blue-600" 
                                    name="shipping_address" 
                                    value="{{ $addr['id'] }}" 
                                    wire:model="selectedShippingAddressId"
                                >
                                <div class="flex-1 text-sm">
                                    <div class="font-medium text-gray-900">{{ $addr['label'] ?? 'Address' }}</div>
                                    <div class="text-gray-600 mt-1">{{ $addr['address_line1'] ?? $addr['address_line_1'] }}</div>
                                    @if(!empty($addr['address_line2']) || !empty($addr['address_line_2']))
                                        <div class="text-gray-600">{{ $addr['address_line2'] ?? $addr['address_line_2'] }}</div>
                                    @endif
                                    <div class="text-gray-600">{{ $addr['country'] }} {{ $addr['postal_code'] }}</div>
                                    @if($addr['is_shipping_address'] ?? false)
                                        <span class="inline-block mt-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Default Shipping</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Billing Address Selection --}}
                <div>
                    <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        Billing Address
                    </h4>
                    
                    {{-- Same as shipping checkbox --}}
                    <div class="mb-4">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                class="text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                                wire:model="billingAddressSameAsShipping"
                            >
                            <span class="text-sm text-gray-700">Billing address is the same as shipping address</span>
                        </label>
                    </div>

                    {{-- Billing Address Selection (hidden if same as shipping) --}}
                    @if(!$billingAddressSameAsShipping)
                        <div class="grid gap-3">
                            @foreach($addresses as $addr)
                                <label class="flex items-start space-x-3 p-4 border-2 rounded-lg hover:border-gray-300 cursor-pointer transition-all duration-200 {{ $selectedBillingAddressId == $addr['id'] ? 'border-blue-600 bg-blue-50' : 'border-gray-200' }}">
                                    <input 
                                        type="radio" 
                                        class="mt-1 text-blue-600" 
                                        name="billing_address" 
                                        value="{{ $addr['id'] }}" 
                                        wire:model="selectedBillingAddressId"
                                    >
                                    <div class="flex-1 text-sm">
                                        <div class="font-medium text-gray-900">{{ $addr['label'] ?? 'Address' }}</div>
                                        <div class="text-gray-600 mt-1">{{ $addr['address_line1'] ?? $addr['address_line_1'] }}</div>
                                        @if(!empty($addr['address_line2']) || !empty($addr['address_line_2']))
                                            <div class="text-gray-600">{{ $addr['address_line2'] ?? $addr['address_line_2'] }}</div>
                                        @endif
                                        <div class="text-gray-600">{{ $addr['country'] }} {{ $addr['postal_code'] }}</div>
                                        @if($addr['is_billing_address'] ?? false)
                                            <span class="inline-block mt-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Default Billing</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex items-center space-x-2 text-sm text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Same as shipping address</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <a href="{{ route('customer.profile.step', 3) }}" 
                       class="text-blue-600 hover:text-blue-700 text-sm font-medium hover:underline">
                        + Add another address
                    </a>
                    <button 
                        type="button" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed" 
                        wire:click="togglePaymentSection"
                        x-bind:disabled="!@js($selectedShippingAddressId) || (!@js($billingAddressSameAsShipping) && !@js($selectedBillingAddressId))"
                    >
                        Payment Card Details
                    </button>
                </div>
            @else
                {{-- No addresses found --}}
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">No addresses found</h4>
                    <p class="text-gray-600 mb-4">Please add a delivery address to continue.</p>
                    <a href="{{ route('customer.profile.step', 3) }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add address
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>