{{-- Auth Slider Panel --}}
<div>
    {{-- Overlay --}}
    @if($isOpen)
    <div class="fixed inset-0 z-40 bg-black bg-opacity-50 cursor-pointer" wire:click="closeModal"
        style="pointer-events: auto !important;">
    </div>
    @endif

    {{-- Slider Panel --}}
    @if($isOpen)
    <div class="fixed right-0 top-0 h-full w-full sm:w-[480px] md:w-[480px] lg:w-[480px] bg-white shadow-xl z-50 flex flex-col rounded-l-3xl transform transition-transform duration-300 ease-in-out"
        style="transform: translateX(0);" onclick="event.stopPropagation()">

        {{-- Header with Logo and Close Button --}}
        <div class="relative flex-shrink-0 overflow-hidden h-2/6 rounded-tl-3xl">
            {{-- Close Button --}}
            <button wire:click="closeModal"
                class="absolute z-50 p-2 text-gray-700 transition-all bg-white rounded-full cursor-pointer top-4 right-4 hover:text-gray-900 bg-opacity-80 hover:bg-opacity-100"
                type="button" style="pointer-events: auto !important;" onclick="event.stopPropagation();">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>

            {{-- Logo Image - Full Width Stretch --}}
            <div
                class="flex items-center justify-start w-full h-full overflow-hidden bg-gradient-to-b from-blue-50 to-white">
                <img src="{{ asset('images/waginton-image-1.png') }}" alt="Wagington"
                    class="object-cover object-left w-full h-full">
            </div>
        </div>

        {{-- Panel Body --}}
        <div class="flex-1 px-4 pb-6 overflow-y-auto sm:px-10">
            @if($currentStep === 'register')
            {{-- Registration Step --}}
            <div>
                <h2 class="mt-6 mb-2 text-2xl font-bold tracking-tight text-gray-900">Create account</h2>
                <p class="mt-2 mb-8 text-sm leading-6 text-gray-500">Welcome! Please enter your mobile number to get
                    started.</p>

                {{-- General Error Display --}}
                @if($errors->has('general'))
                <div class="p-3 mb-4 border border-red-200 rounded-lg bg-red-50">
                    <p class="text-sm text-red-600">{{ $errors->first('general') }}</p>
                </div>
                @endif

                <form wire:submit="sendRegistrationOtp" class="">
                    {{-- Mobile Number with Country Code --}}
                    @component('components.phone-input-component', [
                        'phoneWireModel' => 'mobileNumber',
                        'countryWireModel' => 'countryCode',
                        'phoneId' => 'register_mobile',
                        'label' => 'Mobile Number',
                        'star' => true,
                        'placeholder' => 'Enter mobile number',
                        'phoneValue' => $mobileNumber,
                        'countryValue' => $countryCode,
                        'error' => $errors->first('mobileNumber'),
                        'debounce' => false
                    ])
                    @endcomponent


                    {{-- Referral Code --}}
                    @component('components.textbox-component', [
                        'wireModel' => 'referralCode',
                        'id' => 'referralCode',
                        'label' => 'Referral Code',
                        'placeholder' => 'Enter referral code',
                        'type' => 'text',
                        'error' => $errors->first('referal_code'),
                        'star' => false
                    ])
                    @endcomponent

                    {{-- Terms Checkbox --}}
                    <x-frontend.form-enhanced-checkbox
                        label="<span class='text-sm text-gray-500'>Accept <a href='#' class='text-blue-500 hover:underline '>Terms and Conditions</a></span>"
                        name="acceptTerms" wireModel="acceptTerms" />

                    {{-- Submit Button --}}
                    <button type="submit"
                        class="w-full py-3 mt-6 text-sm font-semibold text-white transition-colors bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled" wire:target="sendRegistrationOtp"
                        @if(!$acceptTerms) disabled @endif>
                        <span wire:loading.remove wire:target="sendRegistrationOtp">Sign up</span>
                        <span wire:loading wire:target="sendRegistrationOtp" class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending OTP...
                        </span>
                    </button>
                </form>

                {{-- Login Link --}}
                <p class="mt-4 text-sm text-center text-gray-500">
                    Already have an account?
                    <button wire:click="switchToLogin" class="text-sm font-normal text-blue-500 hover:underline">Log in</button>
                </p>
            </div>

            @elseif($currentStep === 'verify-mobile')
            {{-- Mobile Verification Step --}}
            <div class="pt-8">
                {{-- Back Button --}}
                <div class="flex items-center mb-4">
                    <button wire:click="goBack" class="flex items-center text-gray-600 hover:text-gray-800">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M15 19l-7-7 7-7">
                            </path>
                        </svg>
                    </button>

                    <h2 class="text-2xl font-bold text-gray-900">Verify mobile number</h2>
                </div>
                <p class="mt-2 mb-8 text-sm leading-6 text-gray-500">
                    Please enter the 6 digit validation code sent to your registered mobile number<span class="font-semibold"> {{ $countryCode }} {{
                    $mobileNumber }} </span>
                </p>

                {{-- OTP Input Boxes --}}
                <div class="flex justify-center mb-6 space-x-3">
                    @for($i = 0; $i < 6; $i++) <input type="text" wire:model="otp.{{ $i }}" maxlength="1"
                        class="w-12 h-12 text-center border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 text-lg font-semibold bg-white shadow-sm @error('otp') border-red-500 @enderror"
                        x-on:input="if($event.target.value.length === 1 && $event.target.nextElementSibling) $event.target.nextElementSibling.focus()"
                        x-on:keydown.backspace="if($event.target.value === '' && $event.target.previousElementSibling) $event.target.previousElementSibling.focus()"
                        x-on:paste="
                                           $event.preventDefault();
                                           const paste = ($event.clipboardData || window.clipboardData).getData('text');
                                           const digits = paste.replace(/\D/g, '').slice(0, 6);
                                           const inputs = $event.target.parentElement.querySelectorAll('input');
                                           for(let i = 0; i < inputs.length && i < digits.length; i++) {
                                               inputs[i].value = digits[i];
                                               inputs[i].dispatchEvent(new Event('input'));
                                           }
                                           if(digits.length === 6) inputs[5].focus();
                                       " pattern="[0-9]*" inputmode="numeric">
                        @endfor
                </div>

                {{-- Status Message --}}
                @if($statusMessage)
                <div class="mb-4 text-center">
                    <span class="text-sm {{ $statusType === 'error' ? 'text-red-500' : 'text-green-500' }}">
                        {{ $statusMessage }}
                    </span>
                </div>
                @endif

                @error('otp') <span class="block mb-4 text-sm text-center text-red-500">{{ $message }}</span> @enderror

                {{-- Confirm Button --}}
                <button type="button" wire:click="verifyMobileOtp"
                    class="w-full py-3 mb-4 text-sm font-semibold text-white transition-colors bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50"
                    wire:loading.attr="disabled" wire:target="verifyMobileOtp">
                    <span wire:loading.remove wire:target="verifyMobileOtp">Confirm</span>
                    <span wire:loading wire:target="verifyMobileOtp">Verifying...</span>
                </button>

                {{-- Resend Link --}}
                <p class="text-sm font-normal text-center text-gray-500">
                    Didn't receive OTP?
                    <button wire:click="resendOtp" class="text-sm font-normal text-blue-500 hover:underline">Resend</button>
                </p>
            </div>

            @elseif($currentStep === 'register-success')
            {{-- Registration Success Step --}}
            <div class="text-center">
                {{-- Success Icon --}}
                <div class="flex items-center justify-center w-16 h-16 mx-auto mt-8 mb-8 bg-green-100 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>

                <h2 class="mb-2 text-2xl font-bold text-gray-900">Verification success!</h2>
                <p class="mb-8 text-sm text-gray-500">
                    Congratulations! Your mobile number has been successfully verified.
                </p>

                <button wire:click="continueToEmail"
                    class="w-full py-3 font-semibold text-white transition-colors bg-blue-500 rounded-lg hover:bg-blue-600">
                    Continue
                </button>
            </div>

            @elseif($currentStep === 'enter-email')
            {{-- Email Entry Step --}}
            <div class="pt-8">
                {{-- Back Button --}}
                <div class="flex items-center mb-4">
                    <button wire:click="goBack" class="flex items-center text-gray-600 hover:text-gray-800">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M15 19l-7-7 7-7">
                            </path>
                        </svg>

                    </button>

                    <h2 class="text-2xl font-bold text-gray-900">Enter email address</h2>
                </div>
                <p class="mb-8 text-sm text-gray-500">
                    Please enter your email address below to continue using the application.
                </p>

                <form wire:submit="sendEmailOtp" class="space-y-6">
                    {{-- Email Input --}}
                    <x-frontend.form-text-input label="Email Address" name="email" type="email"
                        placeholder="Enter email address" wireModel="email" />

                    {{-- Submit Button --}}
                    <div class="pt-1">
                        <x-frontend.form-buttons submitText="Submit" layout="full" />
                    </div>
                </form>
            </div>

            @elseif($currentStep === 'verify-email')
            {{-- Email OTP Verification Step --}}
            <div class="pt-8">
                {{-- Back Button with Title --}}
                <div class="flex items-center mb-4">
                    <button wire:click="goBack" class="flex items-center mr-3 text-gray-600 hover:text-gray-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M15 19l-7-7 7-7">
                            </path>
                        </svg>
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900">Verify Email Address</h2>
                </div>
                <p class="mt-2 mb-8 text-sm leading-6 text-gray-500">
                    Please enter the 6 digit validation code sent to your registered email <span class="font-semibold">{{ $email }}</span>
                </p>

                <form wire:submit="verifyEmailOtp" class="space-y-4">
                    {{-- OTP Input --}}
                    <x-frontend.form-otp-input label="" wireModel="otp" helpText="" />

                    {{-- Status Message --}}
                    @if($statusMessage)
                    <div class="text-center">
                        <span class="text-sm {{ $statusType === 'error' ? 'text-red-500' : 'text-green-500' }}">
                            {{ $statusMessage }}
                        </span>
                    </div>
                    @endif

                    {{-- Confirm Button --}}
                    <x-frontend.form-buttons submitText="Confirm" layout="full" />
                </form>

                {{-- Resend Link --}}
                <p class="mt-4 text-sm font-normal text-center text-gray-500">
                    Didn't receive OTP?
                    <button wire:click="resendOtp" class="text-sm font-normal text-blue-500 hover:underline">Resend</button>
                </p>
            </div>

            @elseif($currentStep === 'email-success')
            {{-- Email Success Step --}}
            <div class="text-center">
                {{-- Success Icon --}}
                <div class="flex items-center justify-center w-16 h-16 mx-auto mt-8 mb-8 bg-green-100 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>

                <h2 class="mb-2 text-2xl font-bold text-gray-900">Registration Complete!</h2>
                <p class="mb-8 text-sm text-gray-500">
                    Congratulations! Your account has been successfully created.
                </p>

                <button wire:click="completeRegistration"
                    class="w-full py-3 font-semibold text-white transition-colors bg-blue-500 rounded-lg hover:bg-blue-600">
                    Continue to Dashboard
                </button>
            </div>

            @elseif($currentStep === 'success')
            {{-- Success Step --}}
            <div class="text-center">
                {{-- Success Icon --}}
                <div class="flex items-center justify-center w-16 h-16 mx-auto mt-8 mb-8 bg-green-100 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>

                <h2 class="mb-2 text-2xl font-bold text-gray-900">Verification success!</h2>
                <p class="mb-8 text-sm text-gray-500">
                    Congratulations! Your mobile number has been successfully verified.
                </p>

                <button wire:click="closeModal"
                    class="w-full py-3 font-semibold text-white transition-colors bg-blue-500 rounded-lg hover:bg-blue-600">
                    Continue
                </button>
            </div>  

            @elseif($currentStep === 'login')
            {{-- Login Step --}}
            <div>
                <h2 class="mt-6 mb-2 text-2xl font-bold tracking-tight text-gray-900">Login</h2>
                <p class="mt-2 mb-6 text-sm text-gray-500">Welcome! You can login using your email or mobile number.</p>

                {{-- Email/Mobile Toggle --}}
                <div class="flex justify-center mb-8">
                    <div class="flex w-full overflow-hidden border-0 rounded-lg">
                        <button type="button" wire:click="$set('type', 'email')"
                            class="flex-1 text-center py-2.5 px-3 transition rounded-lg mr-3 text-sm font-medium {{ $type === 'email' ? 'bg-blue-200 text-blue-600' : 'bg-gray-200 text-gray-600' }}">
                            Email
                        </button>
                        <button type="button" wire:click="$set('type', 'mobile')"
                            class="flex-1 text-center py-1.5 px-2 transition font-medium text-sm rounded-lg {{ $type === 'mobile' ? 'bg-blue-200 text-blue-600' : 'bg-gray-200 text-gray-600' }}">
                            Mobile
                        </button>
                    </div>
                </div>

                <form wire:submit="sendLoginOtp" class="space-y-3">
                    @if($type === 'email')
                    {{-- Email Input --}}
                    <x-frontend.form-text-input label="Email Address" name="identifier" type="email"
                        placeholder="Enter your email address" wireModel="identifier" containerClass="mb-7" />
                    @else
                    {{-- Mobile Number Input with Country Code --}}
                    @component('components.phone-input-component', [
                        'phoneWireModel' => 'mobileNumber',
                        'countryWireModel' => 'countryCode',
                        'phoneId' => 'login_mobile',
                        'label' => 'Mobile Number',
                        'star' => false,
                        'placeholder' => 'Enter mobile number',
                        'phoneValue' => $mobileNumber,
                        'countryValue' => $countryCode,
                        'error' => $errors->first('mobileNumber'),
                        'debounce' => false
                    ])
                    @endcomponent
                    @endif

                    {{-- Submit Button --}}
                    <button type="submit"
                        class="w-full px-10 py-3 mt-6 text-sm font-semibold text-white transition-colors bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled" wire:target="sendLoginOtp">
                        <span wire:loading.remove wire:target="sendLoginOtp">Login</span>
                        <span wire:loading wire:target="sendLoginOtp" class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending OTP...
                        </span>
                    </button>
                </form>

                {{-- Register Link --}}
                <p class="mt-4 text-sm text-center text-gray-500">
                    Don't have an account?
                    <button wire:click="switchToRegister" class="text-sm font-normal text-blue-500 hover:underline">Sign up</button>
                </p>
            </div>

            @elseif($currentStep === 'verify-login')
            {{-- Login OTP Verification --}}
            <div class="pt-8">
                {{-- Back Button with Title --}}
                <div class="flex items-center mb-4">
                    <button wire:click="goBack" class="flex items-center mr-3 text-gray-600 hover:text-gray-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                            </path>
                        </svg>
                    </button>
                    <h2 class="text-2xl font-bold text-gray-900">
                        @if($type === 'mobile')
                        Verify mobile number
                        @else
                        Verify Email Address
                        @endif
                    </h2>
                </div>
                <p class="mt-2 mb-8 text-sm leading-6 text-gray-500">
                    @if($type === 'mobile')
                    Please enter the 6 digit validation code sent to your registered mobile number <span class="font-semibold">{{ $countryCode }} {{
                    $mobileNumber }} </span>
                    @else
                    Please enter the 6 digit validation code sent to your registered email <span class="font-semibold">{{ $email }}</span>
                    @endif
                </p>

                {{-- OTP Input Boxes --}}
                <div class="flex justify-center mb-6 space-x-3">
                    @for($i = 0; $i < 6; $i++) <input type="text" wire:model="otp.{{ $i }}" maxlength="1"
                        class="w-12 h-12 text-center border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 text-lg font-semibold bg-white shadow-sm @error('otp') border-red-500 @enderror"
                        x-on:input="if($event.target.value.length === 1 && $event.target.nextElementSibling) $event.target.nextElementSibling.focus()"
                        x-on:keydown.backspace="if($event.target.value === '' && $event.target.previousElementSibling) $event.target.previousElementSibling.focus()"
                        x-on:paste="
                                           $event.preventDefault();
                                           const paste = ($event.clipboardData || window.clipboardData).getData('text');
                                           const digits = paste.replace(/\D/g, '').slice(0, 6);
                                           const inputs = $event.target.parentElement.querySelectorAll('input');
                                           for(let i = 0; i < inputs.length && i < digits.length; i++) {
                                               inputs[i].value = digits[i];
                                               inputs[i].dispatchEvent(new Event('input'));
                                           }
                                           if(digits.length === 6) inputs[5].focus();
                                       " pattern="[0-9]*" inputmode="numeric">
                        @endfor
                </div>

                {{-- Status Message --}}
                @if($statusMessage)
                <div class="mb-4 text-center">
                    <span class="text-sm {{ $statusType === 'error' ? 'text-red-500' : 'text-green-500' }}">
                        {{ $statusMessage }}
                    </span>
                </div>
                @endif

                @error('otp') <span class="block mb-4 text-sm text-center text-red-500">{{ $message }}</span> @enderror

                {{-- Confirm Button --}}
                <button type="button" wire:click="verifyLoginOtp"
                    class="w-full py-3 mb-4 text-sm font-semibold text-white transition-colors bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50"
                    wire:loading.attr="disabled" wire:target="verifyLoginOtp">
                    <span wire:loading.remove wire:target="verifyLoginOtp">Confirm</span>
                    <span wire:loading wire:target="verifyLoginOtp">Verifying...</span>
                </button>

                {{-- Resend Link --}}
                <p class="mt-4 text-sm font-normal text-center text-gray-500">
                    Didn't receive OTP?
                    <button wire:click="resendOtp" class="text-sm font-normal text-blue-500 hover:underline">Resend</button>
                </p>
            </div>

            @elseif($currentStep === 'failed')
            {{-- Verification Failed --}}
            <div class="text-center">
                {{-- Error Icon --}}
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>

                <h2 class="mb-2 text-2xl font-bold text-gray-900">Verification failed</h2>
                <p class="mb-8 text-sm text-gray-500">
                    Please enter correct mobile number or try again later.
                </p>

                <button wire:click="goBack"
                    class="w-full py-3 font-semibold text-white transition-colors bg-blue-500 rounded-lg hover:bg-blue-600">
                    Try Again
                </button>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

@if($isOpen)
<script>
    document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        @this.call('closeModal');
    }
});

// Function to clear all OTP inputs and focus on first box
function clearOtpAndFocus() {
    const otpInputs = document.querySelectorAll('input[wire\\:model^="otp."]');
    otpInputs.forEach((input, index) => {
        input.value = '';
        // Also trigger Livewire model update
        input.dispatchEvent(new Event('input'));
    });
    // Focus on first input
    if (otpInputs.length > 0) {
        otpInputs[0].focus();
    }
}

// Listen for OTP error event to clear and focus
document.addEventListener('livewire:initialized', () => {
    Livewire.on('otp-error-clear-and-focus', () => {
        setTimeout(() => {
            clearOtpAndFocus();
        }, 100);
    });
});
</script>
@endif