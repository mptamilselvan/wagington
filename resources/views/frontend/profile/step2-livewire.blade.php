@extends('layouts.frontend.index')

@section('content')
<x-frontend.mobile-responsive-styles />
<div class="relative min-h-screen bg-white">
    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        <!-- Combined Profile Container -->
        <div class="p-6 border border-gray-200 rounded-lg shadow-sm bg-gray-50 sm:p-8">
            <!-- Step Navigation -->
            <x-frontend.profile.step-navigation :currentStep="2" />

            <!-- Form Content -->
            <div>
                <form method="POST" action="{{ route('customer.profile.step.save', 2) }}" class="space-y-6" novalidate>
                    @csrf



                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- First Name -->
                        @component('components.textbox-component', [
                            'wireModel' => 'secondary_first_name',
                            'id' => 'secondary_first_name',
                            'label' => 'First Name',
                            'star' => true,
                            'error' => $errors->first('secondary_first_name'),
                            'placeholder' => 'Enter first name',
                            'value' => old('secondary_first_name', auth()->user()->secondary_first_name)
                        ])
                        @endcomponent
                        
                        <!-- Last Name -->
                        @component('components.textbox-component', [
                            'wireModel' => 'secondary_last_name',
                            'id' => 'secondary_last_name',
                            'label' => 'Last Name',
                            'star' => true,
                            'error' => $errors->first('secondary_last_name'),
                            'placeholder' => 'Enter last name',
                            'value' => old('secondary_last_name', auth()->user()->secondary_last_name)
                        ])
                        @endcomponent
                    </div>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Email Address -->
                        @component('components.textbox-component', [
                            'wireModel' => 'secondary_email',
                            'id' => 'secondary_email',
                            'label' => 'Email Address',
                            'type' => 'email',
                            'star' => true,
                            'error' => $errors->first('secondary_email'),
                            'placeholder' => 'Enter email address',
                            'value' => old('secondary_email', auth()->user()->secondary_email)
                        ])
                        @endcomponent
                        
                        <!-- Mobile Number with Country Code -->
                        @component('components.phone-input-component', [
                            'phoneWireModel' => 'secondary_phone',
                            'countryWireModel' => 'secondary_country_code',
                            'phoneId' => 'secondary_phone',
                            'label' => 'Mobile Number',
                            'star' => true,
                            'placeholder' => 'Enter mobile number',
                            'phoneValue' => old('secondary_phone', auth()->user()->secondary_phone),
                            'countryValue' => old('secondary_country_code', auth()->user()->secondary_country_code ?? '+65'),
                            'error' => $errors->first('secondary_phone')
                        ])
                        @endcomponent
                    </div>

                    <!-- Form Buttons -->
                    <x-frontend.form-buttons type="save-cancel" submit="Save" {{-- submitText="Save" --}} {{--
                        :showCancel="true" cancelText="Cancel" cancelAction="history.back()" --}} layout="right" />
                </form>
            </div>
        </div>
    </div>
</div>
@endsection