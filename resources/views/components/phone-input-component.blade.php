@php
    $inputId = $phoneId ?? $phoneWireModel . '_' . uniqid();
@endphp

<div class="mb-6">
    @if(isset($label) && $label)
        <label for="{{ $inputId }}" class="block mb-2 text-sm font-normal text-gray-700">
            {{ $label }}
            @if (isset($star) && $star == true)
                <span class="">*</span>
            @endif
        </label>
    @endif
    
    <div class="relative" x-data="{ localPhoneValue: '{{ $phoneValue ?? '' }}' }">
        {{-- Single unified box with country code and phone input --}}
        <div class="flex rounded-xl border-[1px] border-gray-105 overflow-hidden focus-within:border-primary-blue transition-all duration-200 bg-white focus-within:ring-0" style="height: 42px;">
            {{-- Country Code Select --}}
            <div class="relative" style="min-width: 110px;">
                <select 
                    name="{{ $countryWireModel }}"
                    id="{{ $inputId }}_country"
                    wire:model.live="{{ $countryWireModel }}"
                    class="h-full w-full px-3 py-2 bg-white focus:outline-none focus:ring-0 appearance-none text-gray-input border-0 rubik text-[14px]"
                    style="cursor: pointer; background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 20 20&quot; fill=&quot;%23374151&quot;><path fill-rule=&quot;evenodd&quot; d=&quot;M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z&quot; clip-rule=&quot;evenodd&quot;/></svg>'); background-position: right 8px center; background-repeat: no-repeat; background-size: 16px 16px; padding-right: 28px;">
                    @php
                        $countries = [
                            '+65' => ['flag' => '🇸🇬', 'code' => '+65'],
                            '+91' => ['flag' => '🇮🇳', 'code' => '+91'],
                            '+880' => ['flag' => '🇧🇩', 'code' => '+880'],
                            '+60' => ['flag' => '🇲🇾', 'code' => '+60'],
                            '+1' => ['flag' => '🇺🇸', 'code' => '+1'],
                            '+44' => ['flag' => '🇬🇧', 'code' => '+44']
                        ];
                    @endphp
                    @foreach($countries as $code => $data)
                        <option value="{{ $code }}" {{ (isset($countryValue) && $countryValue == $code) ? 'selected' : '' }}>
                            {{ $data['flag'] }} {{ $code }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            {{-- Vertical Line Separator --}}
            <div class="self-stretch w-px my-2 bg-gray-200"></div>
            
            {{-- Phone Number Input --}}
            <input 
                type="tel"
                name="{{ $phoneWireModel }}"
                id="{{ $inputId }}"
                x-model="localPhoneValue"
                @if(isset($debounce) && $debounce)
                    x-on:blur="$wire.set('{{ $phoneWireModel }}', localPhoneValue)"
                    x-on:change="$wire.set('{{ $phoneWireModel }}', localPhoneValue)"
                @else
                    wire:model.defer="{{ $phoneWireModel }}"
                @endif
                @if(isset($placeholder)) placeholder="{{ $placeholder }}" @endif
                autocomplete="tel"
                class="flex-1 px-3 py-2 bg-white focus:outline-none focus:ring-0 text-gray-input placeholder:text-[#808B9A] border-0 rubik text-[14px] placeholder:text-[14px]">
        </div>
    </div>
    
    @if (isset($placeholder_text) && $placeholder_text != '')
        <span class="text-sm text-gray-400">{{ $placeholder_text }}</span>
    @endif
    
    {{-- Error Messages --}}
    @if(isset($error) && $error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
