{{-- OTP Verification Modal - Matching Figma Design Exactly --}}
@if($showOtpModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
         wire:click.self="closeOtpModal"
    >
        <div class="relative w-full max-w-md p-8 mx-4 bg-white rounded-2xl">
            <!-- Header with Back Arrow -->
            <div class="flex items-center mb-6">
                <button wire:click="closeOtpModal" class="mr-4 text-gray-600 hover:text-gray-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                
                <h2 class="text-xl font-medium text-gray-800">
                    @if($otpType === 'mobile' || $otpType === 'secondary_mobile')
                        Verify mobile number
                    @else
                        Verify email address
                    @endif
                </h2>
            </div>

            <!-- Description -->
            <p class="mb-8 text-sm text-gray-600">
                Please enter the 6 digit validation code sent to your registered 
                @if($otpType === 'mobile')
                    mobile number <span class="font-semibold"> {{ $country_code }} {{ $phone }} </span>
                @elseif($otpType === 'email')
                    email address <span class="font-semibold"> {{ $email }} </span>
                @elseif($otpType === 'secondary_mobile')
                    mobile number <span class="font-semibold"> {{ $secondary_country_code }} {{ $secondary_phone }} </span>
                @elseif($otpType === 'secondary_email')
                    email address <span class="font-semibold">  {{ $secondary_email }} </span>
                @endif
            </p>

            <!-- OTP Input Fields -->
            <div class="flex justify-center mb-8 space-x-3">
                @for($i = 0; $i < 6; $i++)
                    <input type="text" maxlength="1" 
                        wire:model="otpDigits.{{ $i }}"
                        class="w-12 h-12 text-lg font-medium text-center border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                        id="otp-{{ $i }}"
                    >
                @endfor
            </div>

            <!-- Error Messages -->
            @if($otpMessage && $otpMessageType === 'error')
                <div class="mb-6 text-center">
                    <span class="text-sm text-red-500">
                        {{ $otpMessage }}
                    </span>
                </div>
            @endif

            <!-- Confirm Button -->
            <button wire:click="verifyOtp" 
                class="w-full px-4 py-3 font-medium text-white transition-colors duration-200 bg-blue-500 rounded-lg hover:bg-blue-600 focus:outline-none">
                Confirm
            </button>

            <!-- Resend Link -->
            <div class="text-center mt-4">
                <span class="text-sm text-gray-500">Didn't receive OTP? </span>
                <button wire:click="resendOtp" class="text-sm text-blue-500 hover:underline">
                    <span wire:loading.remove wire:target="resendOtp">Resend</span>
                    <span wire:loading wire:target="resendOtp">Resending...</span>
                </button>
            </div>
        </div>
    </div>

    @script
    <script>
        // Function to clear all OTP inputs and focus on first box
        function clearOtpAndFocus() {
            document.querySelectorAll('[id^="otp-"]').forEach((input) => {
                input.value = '';
            });
            const firstInput = document.getElementById('otp-0');
            if (firstInput) {
                firstInput.focus();
            }
        }

        // Listen for OTP error event to clear and focus
        $wire.on('otp-error-clear-and-focus', () => {
            setTimeout(() => {
                clearOtpAndFocus();
            }, 100);
        });

        // OTP Input functionality
        $wire.on('otp-modal-opened', () => {
            setTimeout(() => {
                const firstInput = document.getElementById('otp-0');
                if (firstInput) {
                    firstInput.focus();
                }
                
                // Setup OTP input handlers
                document.querySelectorAll('[id^="otp-"]').forEach((input, index) => {
                    input.addEventListener('input', function(e) {
                        // Only allow numbers
                        this.value = this.value.replace(/[^0-9]/g, '');
                        
                        if (this.value.length === 1) {
                            const nextInput = document.getElementById(`otp-${index + 1}`);
                            if (nextInput) {
                                nextInput.focus();
                            }
                        }
                        
                        // Update Livewire model
                        $wire.set(`otpDigits.${index}`, this.value);
                    });

                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Backspace' && this.value === '') {
                            const prevInput = document.getElementById(`otp-${index - 1}`);
                            if (prevInput) {
                                prevInput.focus();
                            }
                        }
                    });

                    input.addEventListener('paste', function(e) {
                        e.preventDefault();
                        const pastedData = e.clipboardData.getData('text');
                        const digits = pastedData.replace(/[^0-9]/g, '').split('').slice(0, 6);
                        
                        digits.forEach((digit, i) => {
                            const otpInput = document.getElementById(`otp-${i}`);
                            if (otpInput) {
                                otpInput.value = digit;
                                $wire.set(`otpDigits.${i}`, digit);
                            }
                        });
                        
                        // Focus on next empty input or last input
                        const nextEmptyIndex = digits.length < 6 ? digits.length : 5;
                        const nextInput = document.getElementById(`otp-${nextEmptyIndex}`);
                        if (nextInput) {
                            nextInput.focus();
                        }
                    });
                });
            }, 100);
        });
    </script>
    @endscript
@endif