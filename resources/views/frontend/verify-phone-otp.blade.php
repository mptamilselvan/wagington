@extends('layouts.frontend.index')

@section('auth_card_content')

{{-- Use the x-auth-card component for consistent styling --}}
<x-auth-card>
    <div>
        <h2 class="text-xl font-bold mb-2 text-center">Verify mobile number</h2>
        <p class="text-gray-600 mb-4 text-sm text-center">
            Please enter the 6 digit validation code sent to your 
            registered mobile number {{ session('register_country_code') ?? '' }} {{ session('register_phone') ?? '' }}
        </p>

        {{-- Success Messages --}}
        @if (session('success'))
            <div class="mb-4 p-4 bg-green-50 border-2 border-green-200 rounded-xl">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>  
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-800 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            </div>  
        @endif

        {{-- Error Messages --}}
        @if ($errors->has('otp'))
            <div class="mb-4 p-4 bg-red-50 border-2 border-red-200 rounded-xl">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800 font-medium">{{ $errors->first('otp') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('customer.register.verifyOtp') }}" class="space-y-6" 
              x-data="{}" 
              @submit="
                const otpInput = $refs.otpForm.querySelector('input[name=otp]');
                if (otpInput.value.length !== 6) {
                    $event.preventDefault();
                    alert('Please enter all 6 digits of the OTP');
                    return false;
                }
              ">
            @csrf

            {{-- Use the modern OTP input component --}}
            <div x-ref="otpForm">
                <x-frontend.form-otp-input 
                    name="otp" 
                    label="Enter OTP"
                    :required="true" 
                    helpText="Please enter the 6-digit code sent to your phone" />
            </div>

            <button type="submit"
                    class="bg-blue-600 text-white w-full py-3 mt-6 rounded-lg hover:bg-blue-700 
                           transition-all duration-200 font-medium shadow-md hover:shadow-lg">
                Verify OTP
            </button>
        </form>

        <div class="text-center text-sm text-gray-600 mt-6 flex justify-center items-center">
            <span>Didn't receive the OTP?&nbsp;</span>
            <form method="POST" action="{{ route('customer.register.resendPhoneOtp') }}" class="inline-block">
                @csrf
                <input type="hidden" name="phone" value="{{ session('register_phone') }}">
                <button type="submit" class="text-blue-600 hover:underline focus:outline-none font-medium">
                    Resend OTP
                </button>
            </form>
        </div>
    </div>
</x-auth-card>

@endsection