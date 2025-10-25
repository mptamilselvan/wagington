@props(['wireModel', 'id', 'label' => '', 'star' => false, 'readonly' => false, 'placeholder' => '', 'prefix' => '',
'value' => '', 'maxlength' => null, 'wireClickFn' => null, 'wireChangeFn' => null, 'wireOnBlur' => null, 'debounce' =>
null, 'error' => null, 'class' => ''])

<div class="relative">
    <label for="{{ $wireModel }}" class="label">
        {{ $label }}
        <span class="text-primary-blue">{{ $star ? '*' : '' }}</span>
    </label>

    <div class="relative">
        @if ($prefix)
        <span class="absolute top-0 left-0 flex items-center h-full pl-3 mb-4 text-stone-500 ">{{ $prefix }}</span>
        @endif


        <input type="text" wire:model{{ $debounce ? ".debounce.$debounce" : '.defer' }}="{{ $wireModel }}"
            name="{{ $wireModel }}" id="{{ $id }}" placeholder="{{ $placeholder }}" value="{{ $value }}"
            autocomplete="given-name" {{ $readonly ? 'readonly' : '' }} {{ $maxlength ? 'maxlength=' . $maxlength : ''
            }} {{ $wireClickFn ? "wire:click=$wireClickFn" : '' }} {{ $wireChangeFn ? "wire:change=$wireChangeFn" : ''
            }} {{ $wireOnBlur ? "wire:blur=$wireOnBlur" : '' }} class="form-input {{ $class }} w-full"
            style="{{ $prefix ? 'padding-left: '. $prefixPadding.'px;' : '' }}" />

        @if ($error)
        <div id="error_{{ $wireModel }}" class="absolute mt-2 error-message">{{ $error }}</div>
        @endif
    </div>
</div>