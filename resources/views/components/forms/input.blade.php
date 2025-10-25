@props([
    'name' => '',
    'label' => '',
    'type' => 'text',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'value' => '',
    'autocomplete' => '',
    'maxlength' => null,
    'class' => '',
    'id' => null,
    'wireModelLive' => false,
    'wireModel' => null,
    'error' => null
])

<div>
    @if($label)
        <label for="{{ $id ?? $name }}" class="block mb-2 text-sm font-normal text-gray-700">
            {{ $label }}
            @if($required)
                <span class="text-black-500">*</span>
            @endif
        </label>
    @endif
    
    <input 
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($value) value="{{ $value }}" @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if($maxlength) maxlength="{{ $maxlength }}" @endif
        @if($wireModel) 
            @if($wireModelLive)
                wire:model.live="{{ $wireModel }}"
            @else
                wire:model.defer="{{ $wireModel }}"
            @endif
        @endif
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