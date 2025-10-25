@props([
    'label' => 'Mobile Number',
    'placeholder' => 'Enter mobile number',
    'required' => false,
    'error' => null,
    'phoneFieldName' => 'phone',
    'countryCodeFieldName' => 'country_code',
    'phoneWireModel' => null,
    'countryCodeWireModel' => null,
    'phoneValue' => '',
    'countryCodeValue' => '+65',
    'verified' => false,
    'readonly' => false,
    'showEditButton' => false,
    'editButtonText' => 'Edit',
    'editButtonAction' => null,
    // Optional stylistic overrides
    'selectClass' => '',
    'inputClass' => '',
])

@php
    $countryCodes = [
        '+65' => ['flag' => '🇸🇬', 'name' => 'Singapore'],
        '+91' => ['flag' => '🇮🇳', 'name' => 'India'],
        '+880' => ['flag' => '🇧🇩', 'name' => 'Bangladesh'],
        '+60' => ['flag' => '🇲🇾', 'name' => 'Malaysia'],
        '+1' => ['flag' => '🇺🇸', 'name' => 'United States'],
        '+44' => ['flag' => '🇬🇧', 'name' => 'United Kingdom'],
    ];
    
    $inputId = $phoneFieldName . '_' . uniqid();
@endphp

<div class="mb-6">
    @if($label)
 <label for="{{ $inputId }}" class="block mb-2 text-sm font-normal text-gray-700" style="font-family: 'Rubik', sans-serif;">
            {{ $label }}
            @if($required)
                <span class="text-black-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="relative" x-data="{ localValue: '{{ $phoneValue }}' }">
        <div class="flex rounded-xl border-[1px] border-gray-105 overflow-hidden shadow-sm focus-within:border-primary-blue focus-within:ring-0 transition-all duration-200" style="height: 42px;">
            {{-- Country Code Select --}}
            <div class="relative" style="min-width: 90px; border-right: 1px solid #e5e7eb;">
                <select 
                    name="{{ $countryCodeFieldName }}"
                    id="{{ $inputId }}_country"
                    @if($countryCodeWireModel) 
                        x-on:change="$wire.set('{{ $countryCodeWireModel }}', $event.target.value)"
                    @endif
                    class="h-full w-28 px-3 py-2 bg-white focus:outline-none focus:ring-0 appearance-none text-gray-900 border-0 {{ $readonly ? 'bg-gray-50 pointer-events-none' : '' }} {{ $selectClass }}"
                    {{ $readonly ? 'disabled' : '' }}
                >
                    @foreach($countryCodes as $code => $data)
                        <option value="{{ $code }}" {{ $code === $countryCodeValue ? 'selected' : '' }}>
                            {{ $data['flag'] }} {{ $code }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            {{-- Hidden field to ensure country code is always submitted when readonly --}}
            @if($readonly)
                <input type="hidden" name="{{ $countryCodeFieldName }}" value="{{ $countryCodeValue }}">
            @endif
            
            {{-- Phone Number Input with Verification and Edit Button --}}
            <input 
                type="tel"
                name="{{ $phoneFieldName }}"
                id="{{ $inputId }}"
                x-model="localValue"
                @if($phoneWireModel) 
                    x-on:blur="$wire.set('{{ $phoneWireModel }}', localValue)"
                    x-on:change="$wire.set('{{ $phoneWireModel }}', localValue)"
                @endif
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                {{ $required ? 'required' : '' }}
                {{ $readonly ? 'readonly' : '' }}
                class="flex-1 px-3 py-2 bg-white focus:outline-none focus:ring-0 text-gray-900 placeholder-gray-400 border-0 {{ $readonly ? 'bg-gray-50' : '' }} {{ $inputClass }}"
            >
            
            {{-- Verification Checkmark and Edit Button --}}
            <div class="absolute inset-y-0 flex items-center space-x-2 right-3">
                @if($verified)
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                @endif
                
                @if($showEditButton)
                    <button 
                        type="button" 
                        @if($editButtonAction) onclick="{{ $editButtonAction }}" @endif
                        class="text-sm font-medium text-blue-600 hover:text-blue-700 focus:outline-none"
                    >
                        {{ $editButtonText }}
                    </button>
                @endif
            </div>
        </div>
    </div>
    
    {{-- Error Messages --}}
    @if($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>