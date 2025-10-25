<main class="py-10 lg:pl-72 bg-gray-50">
    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-8">
        <div class="h-20 md:flex md:items-center md:justify-between">
            <span class="text-xl font-medium text-gray-900 sm:truncate sm:tracking-tight">
                {{ $title }}</span>
        </div>
    </div>

    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">
        <div class="w-1/5 p-4 bg-white">
            @component('components.subMenu', [
                'active' => 'company-setting',
                'subMenuType' => 'generalSetting',
            ])
            @endcomponent
        </div>
        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'company_name',
                            'id' => 'company_name',
                            'label' => 'company name',
                            'star' => true,
                            'placeholder' => "Enter company name",
                            'error' => $errors->first('company_name'),
                            ])
                        @endcomponent
                    </div>
                    
                    <div class="mt-3">
                        @component('components.dropdown-component', [
                            'wireModel' => 'country_id',
                            'id' => 'country_id',
                            'label' => 'Country',
                            'optionValue' => 'Select country',
                            'options' => $countries,
                            'star' => true,
                            'error' => $errors->first('country_id'),
                            ])
                        @endcomponent
                    </div>
                    <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'address_line1',
                            'id' => 'address_line1',
                            'label' => 'Address line 1',
                            'star' => true,
                            'placeholder' => "Enter Address",
                            'error' => $errors->first('address_line1'),
                            ])
                        @endcomponent
                    </div>
                    <div>
                        <x-forms.phone-input
                            label="Mobile Number"
                            phoneFieldName="contact_number"
                            countryCodeFieldName="country_code"
                            phoneWireModel="contact_number"
                            countryCodeWireModel="country_code"
                            phoneValue="{{ $contact_number }}"
                            countryCodeValue="{{ $country_code }}"
                            placeholder="Enter mobile number"
                            :error="$errors->first('contact_number')"
                            required="true"
                        />
                        {{-- @component('components.phone-component', [
                            'wireModel' => 'contact_number',
                            'id' => 'contact_number',
                            'label' => 'contact number',
                            'star' => true,
                            'placeholder' => "Contact number",
                            'error' => $errors->first('contact_number'),
                            ])
                        @endcomponent --}}
                        
                    </div>
                </div>
                <div>
                    <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'uen_no',
                            'id' => 'uen_no',
                            'label' => 'Registration No./ UEN No.',
                            'star' => true,
                            'error' => $errors->first('uen_no'),
                            'placeholder' => "Registration No./ UEN No.",
                            ])
                        @endcomponent
                    </div>
                    <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'postal_code',
                            'id' => 'postal_code',
                            'label' => 'Postal Code',
                            'star' => true,
                            'placeholder' => "Postal Code",
                            'error' => $errors->first('postal_code'),
                            'wireOnBlur' => 'changePostalCode()'
                            ])
                        @endcomponent
                    </div>
                    <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'address_line2',
                            'id' => 'address_line2',
                            'label' => 'Address line 2',
                            'placeholder' => "Enter Address",
                            'error' => $errors->first('address_line2'),
                            ])
                        @endcomponent
                    </div>
                    <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'support_email',
                            'id' => 'support_email',
                            'label' => 'support email',
                            'star' => true,
                            'placeholder' => "Enter support email address",
                            'error' => $errors->first('support_email'),
                            ])
                        @endcomponent
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 ">
                @component('components.button-component', [
                    'label' => 'Save',
                    'id' => 'save',
                    'type' => 'buttonSmall',
                    'wireClickFn' => 'save()'
                    ])
                @endcomponent
            </div>
        </div>
    </div>
</main>