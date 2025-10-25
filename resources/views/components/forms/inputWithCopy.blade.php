<div>
    @if (isset($label))
        <label @if (isset($wireModel)) for="{{ $wireModel }}" @endif class="label">{{ $label ?? '' }}
            @if (isset($star) && $star == true)
                <span class="">*</span>
            @else
                <span class="">&nbsp;</span>
            @endif
        </label>
    @endif
    <div class="relative flex">
        <input type="text"
            @if (isset($wireModel)) wire:model.defer="{{ $wireModel }}" name="{{ $wireModel }}" @endif
            id="{{ $id }}" @if (isset($placeholder)) placeholder="{{ $placeholder }}" @endif
            @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif value="{{ $value ?? '' }}"
            autocomplete="given-name" {{ isset($readonly) && $readonly == true ? 'readonly' : '' }}
            {{ isset($maxlength) ? 'maxlength="' . $maxlength . '"' : '' }}
            @if (isset($wireChangeFn)) wire:change="{{ $wireChangeFn }}" @endif
            @if (isset($wireOnBlur)) wire:change="{{ $wireOnBlur }}" @endif
            class=" @if (isset($classInput)) {{ $classInput }}  @else form-input @endif
           @if (isset($class)) {{ $class }} w-full @else @endif flex-grow">

        <button type="button" @if (isset($onClickFn)) onclick='{{ $onClickFn }}' @endif
            class="absolute bg-white top-1 md:top-2 right-2 button-strip text-primary-blue hover:text-blue-hover urbanist-500 ">
            <x-icons.copy class=" {{ isset($classSvg) ? $classSvg : ' w-[24px] h-[24px]' }}" />

        </button>
    </div>

    @if (isset($error))
        <div id="error_{{ $wireModel }}" class="mt-2 error-message">{{ $error }}</div>
    @endif

</div>
