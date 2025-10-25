@props([
    'name' => 'phone',
    'label' => 'Mobile Number',
    'placeholder' => 'Enter mobile number',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'value' => '',
    'countryCode' => '+65',
    'class' => '',
    'id' => null,
    'wireModel' => null,
    'error' => null,
    'rightIcon' => false
])

<div x-data="{ 
    countryCode: '{{ $countryCode }}',
    countryCodes: {
        '+65': { flag: '🇸🇬', name: 'Singapore' },
        '+60': { flag: '🇲🇾', name: 'Malaysia' },
        '+1': { flag: '🇺🇸', name: 'United States' },
        '+44': { flag: '🇬🇧', name: 'United Kingdom' },
        '+91': { flag: '🇮🇳', name: 'India' },
        '+880': { flag: '🇧🇩', name: 'Bangladesh' },
        '+86': { flag: '🇨🇳', name: 'China' },
        '+81': { flag: '🇯🇵', name: 'Japan' },
        '+82': { flag: '🇰🇷', name: 'South Korea' },
        '+61': { flag: '🇦🇺', name: 'Australia' },
        '+49': { flag: '🇩🇪', name: 'Germany' }
    }
}">
    @if($label)
        <label for="{{ $id ?? $name }}" class="block text-gray-700 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="flex space-x-2">
        {{-- Country Code Dropdown --}}
        <div class="relative">
            <select x-model="countryCode" 
                    name="{{ $name }}_country_code"
                    class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-3 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-rubik-input">
                <template x-for="(data, code) in countryCodes" :key="code">
                    <option :value="code" x-text="data.flag + ' ' + code"></option>
                </template>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                </svg>
            </div>
        </div>
        
        {{-- Phone Number Input --}}
        <div class="flex-1 relative">
            <input 
                type="tel"
                name="{{ $name }}"
                id="{{ $id ?? $name }}"
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if($value) value="{{ $value }}" @endif
                @if($wireModel) wire:model.defer="{{ $wireModel }}" @endif
                {{ $required ? 'required' : '' }}
                {{ $disabled ? 'disabled' : '' }}
                {{ $readonly ? 'readonly' : '' }}
                {!! $attributes->merge([
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 font-rubik-input ' . $class
                ]) !!}
            >
            
            {{-- Right Icon (if verified) --}}
            @if($rightIcon)
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            @endif
        </div>
    </div>
    
    @if($error)
        <div class="mt-2 text-sm text-red-600 font-rubik-error">
            {{ $error }}
        </div>
    @endif
    
    @error($name)
        <div class="mt-2 text-sm text-red-600 font-rubik-error">
            {{ $message }}
        </div>
    @enderror
</div>