@props([
    'paymentMethods' => [],
    'selectedPaymentMethodId' => null,
    'sectionOpen' => false,
    'disabled' => false
])

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden {{ $disabled ? 'opacity-50 pointer-events-none' : '' }}">
    {{-- Section Header --}}
    <button 
        type="button" 
        class="w-full flex items-center justify-between px-6 py-4 bg-gray-50 hover:bg-gray-100 transition-colors duration-200"
        wire:click="togglePaymentSection"
        @if($disabled) disabled @endif
    >
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 {{ $selectedPaymentMethodId ? 'bg-blue-600' : 'bg-gray-400' }} text-white rounded-full flex items-center justify-center text-sm font-semibold">
                2
            </div>
            <h3 class="font-semibold text-gray-900">Payment Method</h3>
        </div>
        <div class="flex items-center space-x-2">
            @if($selectedPaymentMethodId && !$sectionOpen)
                <span class="text-sm text-green-600 font-medium">✓ Complete</span>
            @endif
            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200 {{ $sectionOpen ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
    </button>

    {{-- Section Content --}}
    @if($sectionOpen && !$disabled)
        <div class="p-6">
            @if(count($paymentMethods) > 0)
                <div class="space-y-3">
                    @foreach($paymentMethods as $pm)
                        <label 
                            wire:key="payment-method-{{ $pm['id'] }}"
                            class="flex items-center justify-between p-4 border rounded-lg hover:border-gray-300 cursor-pointer transition-all duration-200 {{ $selectedPaymentMethodId == $pm['id'] ? 'border-gray-300 bg-gray-50' : 'border-gray-200' }}"
                        >
                            <div class="flex items-center space-x-3">
                                <input 
                                    type="radio" 
                                    class="text-blue-600" 
                                    name="payment_method" 
                                    value="{{ $pm['id'] }}" 
                                    wire:model="selectedPaymentMethodId"
                                >
                                <div class="flex items-center space-x-3">
                                    {{-- Card Brand Icon --}}
                                    <div class="w-10 h-6 bg-gradient-to-r from-gray-700 to-gray-900 rounded flex items-center justify-center">
                                        <span class="text-white text-xs font-bold">{{ strtoupper(substr($pm['brand'], 0, 4)) }}</span>
                                    </div>
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900">{{ strtoupper($pm['brand']) }} •••• {{ $pm['last4'] }}</div>
                                        <div class="text-gray-500">Expires {{ $pm['exp_month'] }}/{{ $pm['exp_year'] }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($selectedPaymentMethodId == $pm['id'])
                                    <div class="flex items-center text-green-600 text-xs font-medium">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Selected
                                    </div>
                                @endif
                                @if(($pm['is_default'] ?? false))
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700 font-medium">Primary</span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Security Notice --}}
                <div class="mt-6 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-start space-x-2">
                        <svg class="w-5 h-5 text-green-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <div class="text-sm text-gray-700">
                            <p class="font-medium">Secure Payment</p>
                            <p class="text-gray-600">Your payment information is encrypted and secure. Your card will be charged after confirming your order.</p>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-200 mt-6">
                    <a href="{{ route('customer.payment-methods.add') }}" 
                       class="text-blue-600 hover:text-blue-700 text-sm font-medium hover:underline">
                        + Add payment method
                    </a>
                </div>
            @else
                {{-- No payment methods found --}}
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">No cards on file</h4>
                    <p class="text-gray-600 mb-4">Add a payment method to proceed with your order.</p>
                    <a href="{{ route('customer.payment-methods.add') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add payment method
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>