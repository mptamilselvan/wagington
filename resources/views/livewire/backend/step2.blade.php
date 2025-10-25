<div class="w-full" x-data="verificationDebouncer()">


    <!-- Form Fields - Two Column Layout -->
    <div class="space-y-6">
        <!-- First Row: First Name / Last Name -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @component('components.textbox-component', [
                'wireModel' => 'secondary_first_name',
                'id' => 'secondary_first_name',
                'label' => 'First Name',
                'star' => true,
                'error' => $errors->first('secondary_first_name'),
                'placeholder' => 'Enter your first name'
            ])
            @endcomponent
            
            @component('components.textbox-component', [
                'wireModel' => 'secondary_last_name',
                'id' => 'secondary_last_name',
                'label' => 'Last Name',
                'star' => true,
                'error' => $errors->first('secondary_last_name'),
                'placeholder' => 'Enter your last name'
            ])
            @endcomponent
        </div>

        <!-- Second Row: Email / Mobile -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div>
                <div class="flex items-center justify-between pt-2 mb-2">
                    <label class="block mb-2 text-sm font-normal text-gray-700" >
                        Email Address<span class="text-black-500"> *</span>
                    </label>
                    {{-- @if(!$secondaryEmailVerified)
                        <button wire:click="sendSecondaryEmailOtp" 
                                wire:loading.attr="disabled" 
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="text-sm font-normal text-blue-500 hover:underline"
                                >
                            <span wire:loading.remove wire:target="sendSecondaryEmailOtp">Send OTP for verification</span>
                            <span wire:loading wire:target="sendSecondaryEmailOtp">Sending OTP...</span>
                        </button>
                    @endif --}}
                </div>
                <div class="relative">
                    <input type="email" 
                           wire:model.live="secondary_email" 
                           placeholder="Enter your email address"
                           required
                           class="w-full form-input"
                           >
                    
                    @if($secondaryEmailVerified)
                        <div class="absolute transform -translate-y-1/2 right-3 top-1/2">
                            <div class="flex items-center justify-center w-6 h-6 bg-green-500 rounded-full">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    @endif
                </div>
                
                @error('secondary_email') 
                    <p class="mt-1 text-sm text-red-600" >{{ $message }}</p>
                @enderror
            </div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block mb-2 text-sm font-normal text-gray-700">
                        Mobile Number<span class="text-black-500"> *</span>
                    </label>
                    {{-- @if(!$secondaryPhoneVerified)
                        <button wire:click="sendSecondaryMobileOtp" 
                                wire:loading.attr="disabled" 
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="text-sm font-normal text-blue-500 hover:underline">
                            <span wire:loading.remove wire:target="sendSecondaryMobileOtp">Send OTP for verification</span>
                            <span wire:loading wire:target="sendSecondaryMobileOtp">Sending OTP...</span>
                        </button>
                    @endif --}}
                </div>
                <div class="relative">
                    <x-forms.phone-input
                        phoneFieldName="secondary_phone"
                        countryCodeFieldName="secondary_country_code"
                        phoneWireModel="secondary_phone"
                        countryCodeWireModel="secondary_country_code"
                        phoneValue="{{ $secondary_phone }}"
                        countryCodeValue="{{ $secondary_country_code }}"
                        placeholder="Enter your mobile number"
                        :class="$secondaryPhoneVerified ? 'pr-12' : ''"
                        label=""
                        :showValidationIcons="false"
                        inputClass="form-input"
                    />
                    <!-- Verified Icon for Secondary Mobile -->
                    @if($secondaryPhoneVerified)
                        <div class="absolute z-10 transform -translate-y-1/2 right-3 top-1/2">
                            <div class="flex items-center justify-center w-6 h-6 bg-green-500 rounded-full">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    @endif
                </div>
                @error('secondary_phone') <p class="mt-1 text-sm text-red-600" >{{ $message }}</p> @enderror
            </div>
        </div>
    </div>



    <!-- Navigation Buttons -->
    <div class="flex justify-end gap-4 pt-6 mt-8">
        <button wire:click="clearStep2Form" 
            class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] ">
            Clear
        </button>
        <button wire:click="nextStep" 
            class="button-primary-small bg-[#1B85F3]">
            Save
        </button>
    </div>
</div>