<div>
    <label for="{{ $wireModel }}" class="label">{{ $label ?? '' }}
        @if (isset($star) && $star == true)
            <span class="">*</span>
        @else
            <span class="">&nbsp;</span>
        @endif
    </label>
    <br>
    <input type="tel"
        @if (isset($debounce)) wire:model.debounce.500ms="{{ $wireModel }}" 
    @else
        wire:model.defer="{{ $wireModel }}" @endif
        name="{{ $wireModel }}" id="{{ $id }}"
        @if (isset($placeholder)) placeholder="{{ $placeholder }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif value="{{ $value ?? '' }}"
        autocomplete="given-name" {{ isset($readonly) && $readonly == true ? 'readonly' : '' }}
        {{ isset($maxlength) ? 'maxlength="' . $maxlength . '"' : '' }}
        @if (isset($wireChangeFn)) wire:change="{{ $wireChangeFn }}" @endif
        @if (isset($wireOnBlur)) wire:change="{{ $wireOnBlur }}" @endif
        class="form-input 
    @if (isset($class)) {{ $class }} w-full @else w-full @endif" >

    @if (isset($error))
        <div id="error_{{ $wireModel }}" class="mt-2 error-message">{{ $error }}</div>
    @endif

    @if (isset($error))
        <div id="error_{{ $wireModel }}_dial_code" class="mt-2 error-message">{{ $error }}</div>
    @endif


    <!-- Hidden field to store dial code -->
    <input type="hidden" wire:model.defer="{{ $wireModel }}_dial_code" id="{{ $id }}_dial_code" name="{{ $wireModel }}_dial_code" >


</div>

