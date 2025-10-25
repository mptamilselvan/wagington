<div class="relative">
    <input type="text" @if (isset($debounce)) wire:model.live.debounce.150ms="{{ $wireModel }}" @else
        wire:model.defer="{{ $wireModel }}" @endif name="{{ $wireModel }}" @if(isset($id)) id="{{ $id }}" @endif
        @if(isset($placeholder)) placeholder="{{ $placeholder }}" @endif @if (isset($wireClickFn))
        wire:click="{{ $wireClickFn }}" @endif value="{{ $value ?? '' }}" autocomplete="given-name" {{ isset($readonly)
        && $readonly==true ? 'readonly' : '' }} {{ isset($maxlength) ? 'maxlength="' . $maxlength . '"' : '' }}
        @if(isset($wireChangeFn)) wire:change="{{ $wireChangeFn }}" @endif @if (isset($wireOnBlur))
        wire:change="{{ $wireOnBlur }}" @endif class="pl-7 sm:pl-8 form-input w-auto lg:w-[291px]">
    <span class="absolute inline-block left-2 top-2.5 text-[#9CA3AF]">
        <x-icons.search class="w-[19px] h-[19px] sm:w-[21px] sm:h-[21px]" />
    </span>
</div>