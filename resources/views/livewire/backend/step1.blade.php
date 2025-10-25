<div class="w-full" x-data="verificationDebouncer()">
    <!-- Profile Photo Section - Centered -->
    <div class="relative flex justify-center mb-8">
        <div class="relative">
            <div class="w-32 h-32 overflow-hidden bg-gray-200 rounded-full">
                @if($profile_photo)
                    <img src="{{ $profile_photo->temporaryUrl() }}" alt="Profile" class="object-cover w-full h-full">
                @elseif($image)
                    <img src="{{ $image }}" alt="Profile" class="object-cover w-full h-full">
                @else
                   <div class="text-center">
                    <svg class="w-6 h-6 mx-auto mb-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-sm text-gray-500">Add picture</span>
                </div>
                @endif
            </div>
            <!-- Camera Icon - Exactly like Figma -->
            <label class="absolute bottom-0 right-0 flex items-center justify-center w-10 h-10 bg-white border-2 border-gray-100 rounded-full shadow-lg cursor-pointer">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <input type="file" class="hidden" accept="image/*" wire:model="profile_photo">
            </label>
        </div>
        <!-- Active Status Label - Top Right Corner of Profile Section -->
        <div class="absolute top-0 right-0">
            <span class="px-4 py-1.5 {{ $this->isCustomerActive ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200' }} text-sm font-medium rounded-full border" >
                {{ $this->isCustomerActive ? 'Active' : 'Inactive' }}
            </span>
        </div>
    </div>
    
    <!-- Profile Photo Error Message - Positioned outside relative container -->
    @error('profile_photo') 
        <div class="flex justify-center mt-2">
            <p class="text-sm text-red-600">{{ $message }}</p>
        </div>
    @enderror

    <!-- Form Fields - Two Column Layout -->
    <div class="space-y-6">
        <!-- First Row: First Name / Last Name -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @component('components.textbox-component', [
                'wireModel' => 'first_name',
                'id' => 'first_name',
                'label' => 'First Name',
                'star' => true,
                'error' => $errors->first('first_name'),
                'placeholder' => 'Enter your first name'
            ])
            @endcomponent
            
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

    <!-- Second Row: Email / Mobile -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block mb-2 text-sm font-normal text-gray-700">
                        Email Address<span class="text-black-500"> *</span>
                    </label>
                    @if(!$emailVerified && ($mode !== 'edit' || $emailEditMode || !$emailVerified))
                        <button wire:click="sendEmailOtp" 
                                wire:loading.attr="disabled" 
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="text-sm font-normal text-blue-500 hover:underline">
                            <span wire:loading.remove wire:target="sendEmailOtp">Send OTP for verification</span>
                            <span wire:loading wire:target="sendEmailOtp">Sending OTP...</span>
                        </button>
                    @endif
                </div>
                <div class="relative">
                    <input type="email" wire:model.live="email" placeholder="Enter your email address"
                        class="form-input w-full {{ $mode === 'edit' && $emailVerified && !$emailEditMode ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                        {{ $mode === 'edit' && $emailVerified && !$emailEditMode ? 'readonly' : '' }}
                        >
                    <!-- Verified Icon and Edit Link -->
                    @if($emailVerified && $mode === 'edit' && !$emailEditMode)
                        <div class="absolute flex items-center gap-2 transform -translate-y-1/2 right-3 top-1/2">
                            <div class="flex items-center justify-center w-6 h-6 bg-green-500 rounded-full">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <button wire:click="enableEmailEdit" 
                                    type="button"
                                    class="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">
                                Edit
                            </button>
                        </div>
                    @endif
                </div>
                @error('email') <p class="mt-1 text-sm text-red-600" >{{ $message }}</p> @enderror
            </div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block mb-2 text-sm font-normal text-gray-700">
                        Mobile Number<span class="text-black-500"> *</span>
                    </label>
                    @if(!$phoneVerified && ($mode !== 'edit' || $phoneEditMode || !$phoneVerified))
                        <button wire:click="sendMobileOtp" 
                                wire:loading.attr="disabled" 
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="text-sm font-normal text-blue-500 hover:underline">
                            <span wire:loading.remove wire:target="sendMobileOtp">Send OTP for verification</span>
                            <span wire:loading wire:target="sendMobileOtp">Sending OTP...</span>
                        </button>
                    @endif
                </div>
                <div class="relative">
                    <x-forms.phone-input
                        phoneFieldName="phone"
                        countryCodeFieldName="country_code"
                        phoneWireModel="phone"
                        countryCodeWireModel="country_code"
                        phoneValue="{{ $phone }}"
                        countryCodeValue="{{ $country_code }}"
                        placeholder="Enter your mobile number"
                        :class="$phoneVerified ? 'pr-12' : ''"
                        label=""
                        :showValidationIcons="false"
                        inputClass="form-input"
                        :readonly="$mode === 'edit' && $phoneVerified && !$phoneEditMode"
                    />
                    <!-- Verified Icon and Edit Link for Mobile -->
                    @if($phoneVerified && $mode === 'edit' && !$phoneEditMode)
                        <div class="absolute z-10 flex items-center gap-2 transform -translate-y-1/2 right-3 top-1/2">
                            <div class="flex items-center justify-center w-6 h-6 bg-green-500 rounded-full">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <button wire:click="enablePhoneEdit" 
                                    type="button"
                                    class="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">
                                Edit
                            </button>
                        </div>
                    @endif
                </div>
                @error('phone') <p class="mt-1 text-sm text-red-600" >{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Third Row: Date of Birth / Passport -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @component('components.date-component', [
                'wireModel' => 'dob',
                'id' => 'dob',
                'label' => 'Date of birth',
                'star' => true,
                'error' => $errors->first('dob'),
                'max' => date('Y-m-d')
            ])
            @endcomponent
            
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

    <!-- Navigation Buttons -->
    <div class="flex justify-end gap-4 pt-6 mt-8">
        <button wire:click="clearStep1Form" 
            class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
            Clear
        </button>
        <button wire:click="nextStep" 
            class="button-primary-small bg-[#1B85F3]">
            Save
        </button>
    </div>
</div>