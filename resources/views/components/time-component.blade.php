

<div>
    <label for="{{ $wireModel }}" class="block mb-2 text-sm font-normal text-gray-700">{{ $label ?? '' }}
        @if (isset($star) && $star == true)
        <span class="">*</span>
        @endif
    </label>
    <div>
        <input type="time" wire:model.defer="{{  $wireModel }}" name="{{ $wireModel }}" id="{{ $id }}"
            @if (isset($placeholder)) placeholder="{{ $placeholder }}" @endif 
            @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
             value="{{ $value ?? '' }}" autocomplete="given-name" 
            {{ isset($readonly) && $readonly==true ? 'readonly' : '' }} 
            @if (isset($wireChangeFn)) wire:change="{{ $wireChangeFn }}" @endif 
            @if (isset($wireOnBlur)) wire:change="{{ $wireOnBlur }}" @endif 
            class="form-input  @if (isset($class)) {{ $class }} @else w-full @endif">

         @if (isset($error))
        <span id="error_{{ $wireModel }}" class="error-message">{{ $error }}</span>
        @endif
    </div>
</div>




