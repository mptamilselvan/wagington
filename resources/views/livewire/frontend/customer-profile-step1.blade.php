<div class="space-y-6">
    <!-- Profile Picture Upload - Centered at Top -->
    <div class="flex justify-center mb-8">
        <div class="relative">
             <label class="flex items-center justify-center w-32 h-32 bg-gray-300 border-4 border-white rounded-full shadow-lg hover:opacity-90">
                @if($profile_picture)
                    <img src="{{ $profile_picture->temporaryUrl() }}" alt="Profile" class="object-cover w-32 h-32 rounded-full">
                @elseif(auth()->user()->image)
                    <img src="{{ auth()->user()->image }}" alt="Profile" class="object-cover w-32 h-32 rounded-full">
                @else
                  <div class="text-center">
                    <svg class="w-6 h-6 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-sm text-gray-500">Add picture</span>
                </div>
                @endif
            </label>
            <!-- Camera Icon -->
            <label class="absolute bottom-0 right-0 flex items-center justify-center w-10 h-10 bg-white border-2 border-gray-100 rounded-full shadow-lg cursor-pointer">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <input type="file" class="hidden" accept="image/*" wire:model="profile_picture">
            </label>
        </div>
    </div>
    
    @error('profile_picture') 
        <div class="flex justify-center mt-2">
            <p class="text-sm text-red-600">{{ $message }}</p>
        </div>
    @enderror
    
    <!-- Section 1: Name Fields -->
    <div class="grid grid-cols-1 gap-6 mt-8 lg:grid-cols-2">
        <!-- First Name -->
        <div>
            @component('components.textbox-component', [
                'wireModel' => 'first_name',
                'id' => 'first_name',
                'label' => 'First Name',
                'star' => true,
                'error' => $errors->first('first_name'),
                'placeholder' => 'Enter your first name'
            ])
            @endcomponent
        </div>
        
        <!-- Last Name -->
        <div>
            @component('components.textbox-component', [
                'wireModel' => 'last_name',
                'id' => 'last_name',
                'label' => 'Last Name',
                'star' => true,
                'error' => $errors->first('last_name'),
                'placeholder' => 'Enter your last name'
            ])
            @endcomponent
        </div>
    </div>
    
    <!-- Section 2: Contact & Personal Info -->
    <div class="space-y-3">
        <!-- Email and Mobile Row -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Email Address with OTP Feature -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="email" class="block mb-2 text-sm font-normal text-gray-700">
                        Email Address <span class="text-black-500">*</span>
                    </label>
                    @if(!$emailEditMode)
                        <button type="button" 
                            wire:click="toggleEmailEdit"
                            class="text-sm font-medium text-blue-600 hover:text-blue-700">
                            Edit
                        </button>
                    @else
                        <button type="button" 
                            wire:click="sendEmailOtp"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="text-sm font-normal text-blue-500 hover:underline">
                            <span wire:loading.remove wire:target="sendEmailOtp">Send OTP for verification</span>
                            <span wire:loading wire:target="sendEmailOtp">Sending OTP...</span>
                        </button>
                    @endif
                </div>
                <div class="relative">
                    <input type="email" 
                        id="email" 
                        wire:model.live="email"
                        @if(!$emailEditMode) readonly @endif
                        class="w-full h-10 px-3 border border-gray-300 rounded-xl focus:ring-1 focus:ring-blue-100 focus:border-blue-300 @if(!$emailEditMode) bg-gray-50 @endif"
                        placeholder="Enter your email address"
                    >
                    @if(!$emailEditMode && $email)
                        <div class="absolute transform -translate-y-1/2 left-64 top-1/2">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" fill="#22C55E" />
                                <path d="M16 9L10.5 14.5L8 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    @endif
                </div>
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            
            <!-- Mobile Number with OTP Feature -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="phone" class="block mb-2 text-sm font-normal text-gray-700">
                        Mobile Number <span class="text-black-500">*</span>
                    </label>
                    @if(!$phoneEditMode)
                        <button type="button" 
                            wire:click="togglePhoneEdit"
                            class="text-sm font-medium text-blue-600 hover:text-blue-700">
                            Edit
                        </button>
                    @else
                        <button type="button" 
                            wire:click="sendPhoneOtp"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="text-sm font-normal text-blue-500 hover:underline">
                            <span wire:loading.remove wire:target="sendPhoneOtp">Send OTP for verification</span>
                            <span wire:loading wire:target="sendPhoneOtp">Sending OTP...</span>
                        </button>
                    @endif
                </div>
                
                <!-- Phone Number Input -->
                <div class="relative">
                    <div class="flex border border-gray-300 rounded-xl h-10 focus-within:ring-1 focus-within:ring-blue-100 focus-within:border-blue-300 @if(!$phoneEditMode) bg-gray-50 @endif overflow-hidden">
                        <!-- Country Code Dropdown -->
                        <select wire:model="country_code" 
                            {{ !$phoneEditMode ? 'disabled' : '' }}
                            wire:key="country-code-select-{{ $phoneEditMode ? 'edit' : 'view' }}" 
                            class="border-0 bg-transparent h-full pl-2 pr-6 text-sm font-medium focus:outline-none focus:ring-0 w-28 flex-shrink-0 @if($phoneEditMode) cursor-pointer @else cursor-default @endif"
                            style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 20 20&quot; fill=&quot;currentColor&quot;><path fill-rule=&quot;evenodd&quot; d=&quot;M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z&quot; clip-rule=&quot;evenodd&quot;/></svg>'); background-position: right 4px center; background-repeat: no-repeat; background-size: 12px 12px;">
                            <option value="+65">🇸🇬 +65</option>
                            <option value="+91">🇮🇳 +91</option>
                            <option value="+880">🇧🇩 +880</option>
                            <option value="+60">🇲🇾 +60</option>
                            <option value="+1">🇺🇸 +1</option>
                            <option value="+44">🇬🇧 +44</option>
                        </select>
                        
                        <!-- Separator Line -->
                        <div class="self-stretch w-px my-2 bg-gray-300"></div>
                        
                        <!-- Phone Number Input -->
                        <input type="tel" 
                            id="phone"
                            wire:model.live="phone"
                            @if(!$phoneEditMode) readonly @endif
                            class="flex-1 h-full min-w-0 px-2 text-sm bg-transparent border-0 focus:outline-none focus:ring-0"
                            placeholder="Enter mobile number"
                        >
                        @if(!$phoneEditMode && $phone)
                            <div class="absolute transform -translate-y-1/2 left-56 top-1/2">
                                 <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" fill="#22C55E" />
                                <path d="M16 9L10.5 14.5L8 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            </div>
                        @endif
                    </div>
                </div>
                @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        
        <!-- Date of Birth and Passport Row -->
        <div class="grid grid-cols-1 gap-6 pt-5 mt-6 lg:grid-cols-2">
            <!-- Date of Birth -->
            <div>
                @component('components.date-component', [
                    'wireModel' => 'dob',
                    'id' => 'dob',
                    'label' => 'Date of birth',
                    'star' => true,
                    'error' => $errors->first('dob'),
                    'max' => date('Y-m-d')
                ])
                @endcomponent
            </div>
            
            <!-- Passport/NRIC/FIN Number -->
            <div>
                @component('components.textbox-component', [
                    'wireModel' => 'passport_nric_fin_number',
                    'id' => 'passport_nric_fin_number',
                    'label' => 'Passport / NRIC / FIN Number (last 4 digits)',
                    'star' => true,
                    'error' => $errors->first('passport_nric_fin_number'),
                    'placeholder' => 'Enter last 4 digits only',
                    'maxlength' => 4
                ])
                @endcomponent
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const passportInput = document.getElementById('passport_nric_fin_number');
                    if (passportInput) {
                        passportInput.addEventListener('input', function(e) {
                            // Remove any non-alphanumeric characters and limit to 4 characters
                            let value = e.target.value.replace(/[^A-Za-z0-9]/g, '').substring(0, 4);
                            e.target.value = value.toUpperCase();
                            // Update Livewire model
                            @this.set('passport_nric_fin_number', value.toUpperCase());
                        });
                    }
                });
                </script>
            </div>
        </div>
    </div>
    
    <!-- Form Buttons -->
    <div class="flex flex-col items-center justify-end pt-6 space-y-3 sm:flex-row sm:space-y-0 sm:space-x-4">
        <button type="button" 
            onclick="history.back()"
            class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] text-gray-500">
            Cancel
        </button>
        <button type="button"
            wire:click="saveProfile"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50 cursor-not-allowed"
            class="button-primary-small bg-[#1B85F3]">
            <span wire:loading.remove wire:target="saveProfile">Save</span>
            <span wire:loading wire:target="saveProfile">Saving...</span>
        </button>
    </div>

    <!-- OTP Verification Modal -->
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
                        @if($otpType === 'email')
                            Verify email address
                        @elseif($otpType === 'sms')
                            Verify mobile number
                        @endif
                    </h2>
                </div>

                <!-- Description -->
                <p class="mb-8 text-sm text-gray-600">
                    @if($otpType === 'email')
                        Please enter the 6 digit validation code sent to your registered email address <span class="font-semibold">{{ $pendingEmail }} </span>
                    @elseif($otpType === 'sms')
                        Please enter the 6 digit validation code sent to your registered mobile number <span class="font-semibold"> {{ $pendingCountryCode }}{{ $pendingPhone }} </span>
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
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="w-full px-4 py-3 font-medium text-white transition-colors duration-200 bg-blue-500 rounded-lg hover:bg-blue-600 focus:outline-none">
                    <span wire:loading.remove wire:target="verifyOtp">Confirm</span>
                    <span wire:loading wire:target="verifyOtp">Verifying...</span>
                </button>

                <!-- Resend Link -->
                <div class="mt-4 text-center">
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
</div>