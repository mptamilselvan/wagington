@props([
    'name' => '',
    'label' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'value' => '',
    'class' => '',
    'id' => null,
    'wireModel' => null,
    'error' => null,
    'min' => null,
    'max' => null
])

<div>
    @if($label)
        <label for="{{ $id ?? $name }}" class="block text-gray-700 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <input 
        type="date"
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($value) value="{{ $value }}" @endif
        @if($min) min="{{ $min }}" @endif
        @if($max) max="{{ $max }}" @endif
        @if($wireModel) wire:model.defer="{{ $wireModel }}" @endif
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        {!! $attributes->merge([
            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 font-rubik-input ' . $class
        ]) !!}
    >
    
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