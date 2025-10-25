<div>
    <label for="{{ $wireModel }}" class="block mb-2 text-sm font-normal text-gray-700"> {{ $label ?? '' }}
        @if (isset($star) && $star == true)
            <span class="">*</span>
        @else
            <span class="">&nbsp;</span>
        @endif
    </label>
    <div class="mt-1">
        <textarea @if (isset($readonly) && $readonly === true) readonly @endif @if (isset($height)) rows="{{ $height }}" @endif id="{{ $id }}"
            wire:model.defer="{{ $wireModel }}" wire:key="{{ $id }}" name="{{ $wireModel }}"
            value="{!! $wireModel !!}" @if (isset($rows)) rows="{{ $rows }}" @endif
            
            {{-- {{ isset($readonly) && $readonly == true ? 'readonly' : '' }} --}}
            class="form-textarea
        @if (isset($class)) {{ $class }} @endif"
            placeholder="{{ $placeholder ?? '' }}"></textarea>
    </div>
    @if (isset($error))
        <span class="error-message ">{{ $error }}</span>
    @endif

</div>