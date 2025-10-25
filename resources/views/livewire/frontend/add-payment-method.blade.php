@extends('layouts.frontend.index')

@section('content')
<div>
<x-frontend.mobile-responsive-styles />
<div class="relative min-h-screen bg-white">
    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        <!-- Combined Profile Container -->
        <div class="p-6 border border-gray-200 rounded-lg shadow-sm bg-gray-50 sm:p-8" x-data="stripeCardForm">
            {{-- Header --}}
            <div class="flex items-start mb-3">
                <a href="{{ route('customer.payment-methods') }}"
                    class="flex items-center mt-1 mr-3 text-gray-600 transition-colors hover:text-gray-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 sm:text-xl">Add card details </h1>
                    <p class="mt-2 text-base text-gray-500"> Add your card information</p>
                </div>
            </div>
            
            

            {{-- Flash Messages --}}
            @if (session()->has('error'))
                <div class="p-4 mb-6 border border-red-200 bg-red-50 rounded-xl">
                    <p class="text-red-800" style="font-family: 'Rubik', sans-serif;">{{ session('error') }}</p>
                </div>
            @endif

            @if($clientSecret)
                <form @submit.prevent="handleSubmit">
                    {{-- Card Form --}}
                    <div class="mb-8 space-y-6">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <div>
                                <div>
                                    <label class="block mb-2 text-sm font-normal text-gray-700">Name on card<span class="">*</span></label>
                                    <input type="text" 
                                           name="cardholderName" 
                                           id="cardholderName"
                                           placeholder="Enter name on card" 
                                           x-model="cardholderName"
                                           @blur="validateCardholderName()"
                                           autocomplete="given-name"
                                           class="w-full form-input"
                                           :class="cardholderNameError ? 'border-red-500 focus:border-red-500' : ''">
                                    <div x-show="cardholderNameError" x-cloak class="mt-1">
                                        <p class="text-sm text-red-600" x-text="cardholderNameError"></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <div>
                                    <label class="block mb-2 text-sm font-normal text-gray-700">Card Number<span class="">*</span></label>
                                    <div id="card-number-element" class="w-full form-input"></div>
                                    <div x-show="cardNumberError" x-cloak class="mt-1">
                                        <p class="text-sm text-red-600" x-text="cardNumberError"></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <div>
                                    <label class="block mb-2 text-sm font-normal text-gray-700">Expiry<span class="">*</span></label>
                                    <div id="card-expiry-element" class="w-full form-input"></div>
                                    <div x-show="cardExpiryError" x-cloak class="mt-1">
                                        <p class="text-sm text-red-600" x-text="cardExpiryError"></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <div>
                                    <label class="block mb-2 text-sm font-normal text-gray-700">CVV<span class="">*</span></label>
                                    <div id="card-cvc-element" class="w-full form-input"></div>
                                    <div x-show="cardCvcError" x-cloak class="mt-1">
                                        <p class="text-sm text-red-600" x-text="cardCvcError"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Set as Primary --}}
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                       <div class="flex items-center justify-end justify-between px-4 py-2 py-6 bg-blue-50 rounded-xl">
                            <span class="text-sm text-gray-700" style="font-family: 'Rubik', sans-serif;">Set as primary card</span>
                            <button type="button" @click="isPrimary = !isPrimary"
                                class="relative inline-flex items-center h-6 transition-colors rounded-full w-11 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                :class="isPrimary ? 'bg-[#1B85F3]' : 'bg-gray-200'">
                                <span class="inline-block w-4 h-4 transition-transform transform bg-white rounded-full"
                                    :class="isPrimary ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                        </div>
                        </div>


                    </div>

                    {{-- Bottom Section --}}
                   <div class="pt-4 pb-4 pl-8 pr-8 bg-white border-t border-gray-100 rounded-2xl">
                        <p class="mb-4 text-gray-500" style="font-family: 'Rubik', sans-serif;">You can set the card as primary to make
                            future payments easier.</p>
                    
                        {{-- Form Buttons --}}
                        <div class="flex justify-end mt-6 space-x-3">
                            <button type="submit" :disabled="isProcessing" class="button-primary-small bg-[#1B85F3] font-rubik-button">
                                <span x-show="!isProcessing">Save</span>
                                <span x-show="isProcessing" class="flex items-center">
                                    <svg class="w-5 h-5 mr-3 -ml-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            @else
                <div class="py-16 text-center">
                    <div class="w-12 h-12 mx-auto mb-4 border-b-2 border-blue-600 rounded-full animate-spin"></div>
                    <p class="text-gray-500" style="font-family: 'Rubik', sans-serif;">Loading payment form...</p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Stripe Elements Script --}}
<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('stripeCardForm', () => ({
        stripe: null,
        elements: null,
        cardNumber: null,
        cardExpiry: null,
        cardCvc: null,
        cardholderName: '',
        isPrimary: true,
        isProcessing: false,
        cardholderNameError: null,
        cardNumberError: null,
        cardExpiryError: null,
        cardCvcError: null,
        hasUserInteracted: false,

        init() {
            this.stripe = Stripe('{{ $publishableKey }}');
            this.elements = this.stripe.elements();
            
            // Create card elements
            const style = {
                base: {
                    fontSize: '16px',
                    color: '#374151',
                    fontFamily: 'Rubik, sans-serif',
                    '::placeholder': {
                        color: '#9CA3AF',
                    },
                },
                invalid: {
                    color: '#EF4444',
                },
            };

            this.cardNumber = this.elements.create('cardNumber', { style });
            this.cardExpiry = this.elements.create('cardExpiry', { style });
            this.cardCvc = this.elements.create('cardCvc', { style });

            // Mount elements
            this.$nextTick(() => {
                this.cardNumber.mount('#card-number-element');
                this.cardExpiry.mount('#card-expiry-element');
                this.cardCvc.mount('#card-cvc-element');
            });

            // Handle real-time validation errors for individual fields
            this.cardNumber.on('change', ({error, complete, empty}) => {
                if (!empty) this.hasUserInteracted = true;
                if (this.hasUserInteracted && error) {
                    // Clear other field errors and set number error
                    this.cardExpiryError = null;
                    this.cardCvcError = null;
                    this.cardNumberError = error.message;
                } else if (!error) {
                    this.cardNumberError = null;
                }
            });

            this.cardExpiry.on('change', ({error, complete, empty}) => {
                if (!empty) this.hasUserInteracted = true;
                if (this.hasUserInteracted && error) {
                    // Clear other field errors and set expiry error
                    this.cardNumberError = null;
                    this.cardCvcError = null;
                    this.cardExpiryError = error.message;
                } else if (!error) {
                    this.cardExpiryError = null;
                }
            });

            this.cardCvc.on('change', ({error, complete, empty}) => {
                if (!empty) this.hasUserInteracted = true;
                if (this.hasUserInteracted && error) {
                    // Clear other field errors and set CVC error
                    this.cardNumberError = null;
                    this.cardExpiryError = null;
                    this.cardCvcError = error.message;
                } else if (!error) {
                    this.cardCvcError = null;
                }
            });
        },

        validateCardholderName() {
            if (!this.cardholderName || this.cardholderName.trim() === '') {
                this.cardholderNameError = 'Name on card is required';
                return false;
            } else {
                this.cardholderNameError = null;
                return true;
            }
        },

        async handleSubmit() {
            if (this.isProcessing) return;
            
            // Clear ALL previous errors first
            this.cardholderNameError = null;
            this.cardNumberError = null;
            this.cardExpiryError = null;
            this.cardCvcError = null;
            
            // Validate cardholder name
            if (!this.validateCardholderName()) {
                return;
            }
            
            this.isProcessing = true;

            try {
                const { setupIntent, error } = await this.stripe.confirmCardSetup(
                    '{{ $clientSecret }}',
                    {
                        payment_method: {
                            card: this.cardNumber,
                            billing_details: {
                                name: this.cardholderName,
                            },
                        }
                    }
                );

                if (error) {
                    console.error('Stripe error:', error);
                    
                    // First clear all errors again
                    this.cardholderNameError = null;
                    this.cardNumberError = null;
                    this.cardExpiryError = null;
                    this.cardCvcError = null;
                    
                    // Then set the appropriate error based on message content
                    if (error.message && (error.message.toLowerCase().includes('expiry') || error.message.toLowerCase().includes('date'))) {
                        this.cardExpiryError = error.message;
                    } else if (error.message && (error.message.toLowerCase().includes('cvc') || error.message.toLowerCase().includes('cvv') || error.message.toLowerCase().includes('security code') || error.message.toLowerCase().includes('security'))) {
                        this.cardCvcError = error.message;
                    } else if (error.message && (error.message.toLowerCase().includes('number') || error.message.toLowerCase().includes('card'))) {
                        this.cardNumberError = error.message;
                    } else {
                        // Default fallback
                        this.cardNumberError = error.message || 'Please check your card details';
                    }
                } else {
                    
                    // Send to unified web endpoint to attach and optionally set default
                    try {
                        const response = await fetch('/customer/payment-methods/create', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                token: setupIntent.payment_method,
                                default: this.isPrimary
                            })
                        });

                        if (!response.ok) {
                            const err = await response.json().catch(() => ({}));
                            console.warn('Failed to create payment method via unified endpoint', err);
                            this.cardNumberError = err?.message || 'Failed to save payment method.';
                            this.isProcessing = false;
                            return;
                        }
                    } catch (createError) {
                        console.warn('Error creating payment method via unified endpoint:', createError);
                        this.cardNumberError = 'Failed to save payment method.';
                        this.isProcessing = false;
                        return;
                    }

                    // Redirect to payment methods page with success message
                    window.location.href = '/customer/payment-methods?success=1';
                }
            } catch (err) {
                console.error('Unexpected error:', err);
                this.cardNumberError = 'An unexpected error occurred. Please try again.';
            }

            this.isProcessing = false;
        }
    }));
});
</script>
@endsection
</div>