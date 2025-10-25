@props([
    'addresses' => [],
    'selectedShippingAddressId' => null,
    'selectedBillingAddressId' => null,
    'billingAddressSameAsShipping' => true,
    'sectionOpen' => true,
    'requiresShipping' => true,
    'title' => null
])

@php
    // Dynamic title based on shipping requirement
    if ($title === null) {
        $title = $requiresShipping ? 'Delivery & Billing Address' : 'Billing Address';
    }
@endphp

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
            @if($selectedBillingAddressId && (!$requiresShipping || $selectedShippingAddressId) && !$sectionOpen)
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
                {{-- Instructions --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start space-x-2">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-blue-900 mb-1">Select Your Address{{ $requiresShipping ? 'es' : '' }}</h4>
                            <p class="text-sm text-blue-800">
                                @if($requiresShipping)
                                    Choose one address for shipping and one for billing from the list below. You can use the same address for both.
                                @else
                                    Choose one address for billing from the list below. No shipping address is required for your items.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Addresses List --}}
                <div class="space-y-3">
                    <h4 class="font-medium text-gray-900 flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Your Addresses
                    </h4>
                    
                    {{-- Responsive grid layout for better space utilization --}}
                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        @foreach($addresses as $addr)
                            @php
                                $isSelectedShipping = $selectedShippingAddressId == $addr['id'];
                                $isSelectedBilling = $selectedBillingAddressId == $addr['id'];
                                $isSelected = $isSelectedShipping || $isSelectedBilling;
                            @endphp
                            
                            <div class="border rounded-lg p-3 transition-all duration-200 {{ $isSelected ? 'border-gray-300 bg-gray-50' : 'border-gray-200 hover:border-gray-300' }}">
                                {{-- Address Details --}}
                                <div class="mb-3">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-gray-900 text-sm truncate">{{ $addr['label'] ?? 'Address' }}</div>
                                            <div class="text-gray-600 text-xs">
                                                <div class="truncate">{{ $addr['address_line1'] ?? $addr['address_line_1'] }}</div>
                                                @if(!empty($addr['address_line2']) || !empty($addr['address_line_2']))
                                                    <div class="truncate">{{ $addr['address_line2'] ?? $addr['address_line_2'] }}</div>
                                                @endif
                                                <div class="truncate">{{ $addr['country'] ?? '' }} {{ $addr['postal_code'] ?? '' }}</div>
                                            </div>
                                        </div>
                                        
                                        {{-- Compact selection indicators --}}
                                        <div class="flex flex-col items-end space-y-1 ml-2">
                                            @if($requiresShipping && $isSelectedShipping)
                                                <div class="flex items-center text-green-600 text-xs font-medium">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4.5m8-4.5v10l-8 4.5m0-10L4 7m8 4.5v10M4 7v10l8 4.5"></path>
                                                    </svg>
                                                    Ship
                                                </div>
                                            @endif
                                            @if($isSelectedBilling)
                                                <div class="flex items-center text-blue-600 text-xs font-medium">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                    </svg>
                                                    Bill
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Compact Selection Options --}}
                                <div class="flex {{ $requiresShipping ? 'justify-between' : 'justify-center' }} gap-2 pt-2 border-t border-gray-200">
                                    {{-- Shipping Selection - only show if shipping is required --}}
                                    @if($requiresShipping)
                                        <label class="flex items-center space-x-1 cursor-pointer group flex-1">
                                            <input 
                                                type="radio" 
                                                class="text-blue-600 border-gray-300 focus:ring-blue-500" 
                                                name="shipping_address" 
                                                value="{{ $addr['id'] }}" 
                                                wire:model="selectedShippingAddressId"
                                            >
                                            <span class="text-xs font-medium {{ $isSelectedShipping ? 'text-blue-700' : 'text-gray-700 group-hover:text-blue-600' }}">
                                                Shipping
                                            </span>
                                        </label>
                                    @endif
                                    
                                    {{-- Billing Selection --}}
                                    <label class="flex items-center space-x-1 cursor-pointer group flex-1">
                                        <input 
                                            type="radio" 
                                            class="text-blue-600 border-gray-300 focus:ring-blue-500" 
                                            name="billing_address" 
                                            value="{{ $addr['id'] }}" 
                                            wire:model="selectedBillingAddressId"
                                        >
                                        <span class="text-xs font-medium {{ $isSelectedBilling ? 'text-blue-700' : 'text-gray-700 group-hover:text-blue-600' }}">
                                            Billing
                                        </span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
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
                        @if(!$selectedBillingAddressId || ($requiresShipping && !$selectedShippingAddressId)) disabled @endif
                    >
                        Continue to Payment
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