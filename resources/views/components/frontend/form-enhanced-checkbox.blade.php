{{-- Enhanced Checkbox Component --}}
@props([
    'label' => '',
    'name' => '',
    'value' => '1',
    'checked' => false,
    'required' => false,
    'disabled' => false,
    'error' => '',
    'wireModel' => null,
    'class' => '',
    'helpText' => '',
    'id' => null,
    'size' => 'default', // default, small, large
    'style' => 'default', // default, switch
    'color' => 'blue' // blue, green, red, purple
])

@php
    $inputId = $id ?? $name;
    $hasError = $error || $errors->has($name);
    
    // Size classes
    $sizeClasses = [
        'small' => 'w-3 h-3',
        'default' => 'w-4 h-4',
        'large' => 'w-5 h-5'
    ];
    
    // Color classes
    $colorClasses = [
        'blue' => 'text-blue-600 focus:ring-blue-500',
        'green' => 'text-green-600 focus:ring-green-500',
        'red' => 'text-red-600 focus:ring-red-500',
        'purple' => 'text-purple-600 focus:ring-purple-500'
    ];
    
    $checkboxSize = $sizeClasses[$size] ?? $sizeClasses['default'];
    $checkboxColor = $colorClasses[$color] ?? $colorClasses['blue'];
@endphp

<div class="mb-4">
    @if($style === 'switch')
        {{-- Switch Style --}}
        <div class="flex items-center" x-data="{ checked: {{ $checked || old($name) ? 'true' : 'false' }} }">
            <input 
                type="checkbox"
                id="{{ $inputId }}"
                name="{{ $name }}"
                value="{{ $value }}"
                @if($checked || old($name)) checked @endif
                @if($wireModel) wire:model.live="{{ $wireModel }}" @endif
                @if($required) required @endif
                @if($disabled) disabled @endif
                x-model="checked"
                class="sr-only"
            >
            
            <button 
                type="button"
                @click="checked = !checked; document.getElementById('{{ $inputId }}').checked = checked; document.getElementById('{{ $inputId }}').dispatchEvent(new Event('change'));"
                :class="checked ? 'bg-{{ $color }}-600' : 'bg-gray-200'"
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-{{ $color }}-500 focus:ring-offset-2 {{ $disabled ? 'cursor-not-allowed opacity-50' : '' }}"
                :aria-pressed="checked"
                @if($disabled) disabled @endif
            >
                <span 
                    :class="checked ? 'translate-x-5' : 'translate-x-0'"
                    class="inline-block w-5 h-5 transition duration-200 ease-in-out transform bg-white rounded-full shadow pointer-events-none ring-0"
                ></span>
            </button>
            
            @if($label)
                <label for="{{ $inputId }}" class="ml-3 text-sm {{ $disabled ? 'text-gray-400' : 'text-gray-700' }}">
                    {!! $label !!}
                    @if($required)
                        <span class="text-red-500">*</span>
                    @endif
                </label>
            @endif
        </div>
    @else
        {{-- Default Checkbox Style --}}
        <div class="flex items-start">
            <div class="flex items-center h-5 pt-1">
                <input 
                    type="checkbox"
                    id="{{ $inputId }}"
                    name="{{ $name }}"
                    value="{{ $value }}"
                    @if($checked || old($name)) checked @endif
                    @if($wireModel) wire:model.live="{{ $wireModel }}" @endif
                    @if($required) required @endif
                    @if($disabled) disabled @endif
                    class="{{ $checkboxSize }} {{ $checkboxColor }} bg-gray-100 border-gray-300 rounded focus:ring-2 transition-colors duration-200 {{ $hasError ? 'border-red-500 focus:ring-red-500' : '' }} {{ $disabled ? 'cursor-not-allowed opacity-50' : '' }} {{ $class }}"
                >
            </div>
            
            @if($label)
                <div class="ml-3 text-sm leading-6">
                    <label for="{{ $inputId }}" class="{{ $disabled ? 'text-gray-400' : 'text-gray-400' }} cursor-pointer" style="font-family: 'Rubik', sans-serif;">
                        {!! $label !!}
                        @if($required)
                            <span class="text-red-500">*</span>
                        @endif
                    </label>
                    
                    @if($helpText)
                        <p class="mt-1 text-gray-500" style="font-family: 'Rubik', sans-serif;">{{ $helpText }}</p>
                    @endif
                </div>
            @endif
        </div>
    @endif
    
    {{-- Error Messages --}}
    @if($error)
        <p class="mt-1 text-sm text-red-600" style="font-family: 'Rubik', sans-serif;">{{ $error }}</p>
    @endif
    
    @error($name)
        <p class="mt-1 text-sm text-red-600" style="font-family: 'Rubik', sans-serif;">{{ $message }}</p>
    @enderror
</div>