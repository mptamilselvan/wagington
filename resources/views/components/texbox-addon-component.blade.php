<div class="">
    @if(isset($label) && $label)
        <label for="{{ $wireModel }}" class="block mb-2 text-sm font-normal text-gray-700">{{ $label ?? '' }}
            @if (isset($star) && $star == true)
                <span class="">*</span>
            @else
                <span class="">&nbsp;</span>
            @endif
        </label>
    @endif
    <div class="relative w-full">
        <input type="text"
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
            class=" form-input 
        @if (isset($class)) {{ $class }} w-full @else w-full @endif" @if (isset($min) && $min != '') min="{{ $min }}" @endif @if (isset($max) && $max != '') max="{{ $max }}" @endif>
        @if(isset($placeholder_text) && $placeholder_text != '')<span class="text-sm text-gray-400">{{ $placeholder_text }}</span>@endif

        @if(isset($addon) && $addon)
            <span type="submit" class="absolute top-0 end-0 p-2.5 text-sm rounded-e-lg border border-gray-105">
                {{ $addon }}
            </span> 
        @endif
    </div>

    @if (isset($error))
        <div id="error_{{ $wireModel }}" class="mt-2 error-message">{{ $error }}</div>
    @endif

</div>