{{-- Enhanced Text Input Component --}}
@props([
'label' => '',
'name' => '',
'type' => 'text',
'value' => '',
'placeholder' => '',
'required' => false,
'readonly' => false,
'disabled' => false,
'error' => '',
'icon' => null,
'suffix' => null,
'maxlength' => null,
'pattern' => null,
'class' => '',
'containerClass' => 'mb-3',
'wireModel' => null,
'debounce' => null,
'autocomplete' => 'off',
'id' => null,
'helpText' => ''
])

@php
$inputId = $id ?? $name;
$hasError = $error || $errors->has($name);
$wireModelAttribute = $wireModel ? ($debounce ? "wire:model.debounce.{$debounce}ms=\"{$wireModel}\"" :
"wire:model=\"{$wireModel}\"") : '';
@endphp

<div class="{{ $containerClass }}">
    {{-- Label --}}
    @if($label)
    <label for="{{ $inputId }}" class="block mb-2 text-sm font-normal text-gray-700"
        style="font-family: 'Rubik', sans-serif;">
        {{ $label }}
        @if($required)
        <span class="text-black-500">*</span>
        @endif
    </label>
    @endif

    {{-- Input Container --}}
    <div class="relative">
        {{-- Left Icon --}}
        @if($icon)
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
            {!! $icon !!}
        </div>
        @endif

        {{-- Input Field --}}
        @php
        $computedValue = is_scalar(old($name, $value)) ? old($name, $value) : (is_scalar($value) ? $value : '');
        @endphp
        <input type="{{ $type }}" id="{{ $inputId }}" name="{{ $name }}" @unless(isset($attributes['x-model']) ||
            str_contains((string) $attributes, 'x-model' )) value="{{ $computedValue }}" @endunless
            placeholder="{{ $placeholder }}" @if($required) required @endif @if($readonly) readonly @endif
            @if($disabled) disabled @endif @if($maxlength) maxlength="{{ $maxlength }}" @endif @if($pattern)
            pattern="{{ $pattern }}" @endif @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif {!!
            $wireModelAttribute !!} {{ $attributes }} class="w-full h-[38px] px-4 border border-gray-300 text-sm rounded-xl bg-white
           focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500
           transition-all duration-200 ease-in-out shadow-sm
           placeholder-gray-400
           {{ $icon ? 'pl-10' : '' }} 
           {{ $suffix ? 'pr-20' : '' }} 
           {{ $hasError ? 'border-red-500 focus:ring-red-500/30 focus:border-red-500' : '' }} 
           {{ $disabled ? 'bg-gray-100 cursor-not-allowed opacity-70' : '' }}
           {{ $class }}" style="font-family: 'Rubik', sans-serif;">


        {{-- Right Suffix --}}
        @if($suffix)
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
            {!! $suffix !!}
        </div>
        @endif
    </div>

    {{-- Help Text --}}
    @if($helpText && !$hasError)
    <p class="mt-1 text-sm text-gray-500" style="font-family: 'Rubik', sans-serif;">{{ $helpText }}</p>
    @endif

    {{-- Error Messages --}}
    @if($error)
    <p class="mt-1 text-sm text-red-600" style="font-family: 'Rubik', sans-serif;">{{ $error }}</p>
    @elseif($errors->has($name))
    <p class="mt-1 text-sm text-red-600" style="font-family: 'Rubik', sans-serif;">{{ $errors->first($name) }}</p>
    @endif
</div>