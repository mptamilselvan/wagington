@if ($type == 'gvInput')
    <div>

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
    @if (isset($class)) {{ $class }} w-full @else text-white text-center focus:bg-red-500 focus:text-white placeholder-white [border:none] h-[40px] font-montserrat text-sm bg-[#6F3535] rounded-8xs w-full overflow-hidden flex flex-row py-1.5 box-border items-center justify-center   sm:box-border @endif">

        @if (isset($error))
            <div id="error_{{ $wireModel }}" class="mt-2 text-xs italic text-white " style="padding-left:31px">
                {{ $error }}</div>
        @endif

    </div>
@endif



@if ($type == 'gvInputMobile')
    @props(['wireModel', 'id', 'label' => '', 'star' => false, 'readonly' => false, 'placeholder' => '', 'prefix' => '', 'value' => '', 'maxlength' => null, 'wireClickFn' => null, 'wireChangeFn' => null, 'wireOnBlur' => null, 'debounce' => null, 'error' => null, 'class' => ''])

    <div class="">


        <div class="relative">
            @if ($prefix)
                <span
                    class="absolute top-0 left-0 flex items-center h-full pl-3 mb-4 text-base text-white font-montserrat">{{ $prefix }}</span>
            @endif


            <input type="text" wire:model{{ $debounce ? ".debounce.$debounce" : '.defer' }}="{{ $wireModel }}"
                name="{{ $wireModel }}" id="{{ $id }}" placeholder="{{ $placeholder }}"
                value="{{ $value }}" autocomplete="given-name" {{ $readonly ? 'readonly' : '' }}
                {{ $maxlength ? 'maxlength=' . $maxlength : '' }} {{ $wireClickFn ? "wire:click=$wireClickFn" : '' }}
                {{ $wireChangeFn ? "wire:change=$wireChangeFn" : '' }}
                {{ $wireOnBlur ? "wire:blur=$wireOnBlur" : '' }}
                class="form-input {{ $class }} w-full h-[40px] text-center [border:none] text-white focus:text-white placeholder-white font-montserrat text-sm focus:bg-red-500 bg-[#6F3535] rounded-8xs  overflow-hidden flex flex-row  box-border items-center justify-center   sm:box-border"
                style="{{ $prefix ? 'padding-left: ' . $prefixPadding . 'px;' : '' }} font-family: 'Montserrat'" />



        </div>
        @if ($error)
            <div id="error_{{ $wireModel }}" class="my-2 text-xs italic text-white " style="padding-left:31px">
                {{ $error }}
            </div>
        @endif
    </div>
@endif


@if ($type == 'familycareInput')
    <div>

        <input type="text" style="font-family: 'Rubik'"
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
    @if (isset($class)) {{ $class }} w-full @else text-black text-center  focus:bg-[#DCEAEB] focus:text-black placeholder-black [border:none] h-[40px]   text-sm bg-[#DCEAEB] rounded-8xs w-full overflow-hidden flex flex-row py-1.5 box-border items-center justify-center   sm:box-border @endif">

        @if (isset($error))
            <div id="error_{{ $wireModel }}" class="mt-2 text-xs italic text-red-500 " style="padding-left:31px">
                {{ $error }}</div>
        @endif

    </div>
@endif


@if ($type == 'familycareInputMobile')
    @props(['wireModel', 'id', 'label' => '', 'star' => false, 'readonly' => false, 'placeholder' => '', 'prefix' => '', 'value' => '', 'maxlength' => null, 'wireClickFn' => null, 'wireChangeFn' => null, 'wireOnBlur' => null, 'debounce' => null, 'error' => null, 'class' => ''])

    <div class="">


        <div class="relative">
            @if ($prefix)
                <span class="absolute top-0 left-0 flex items-center h-full pl-3 mb-4 text-base text-black "
                    style="font-family: 'Rubik'">{{ $prefix }}</span>
            @endif


            <input type="text" wire:model{{ $debounce ? ".debounce.$debounce" : '.defer' }}="{{ $wireModel }}"
                style="font-family: 'Rubik'" name="{{ $wireModel }}" id="{{ $id }}"
                placeholder="{{ $placeholder }}" value="{{ $value }}" autocomplete="given-name"
                {{ $readonly ? 'readonly' : '' }} {{ $maxlength ? 'maxlength=' . $maxlength : '' }}
                {{ $wireClickFn ? "wire:click=$wireClickFn" : '' }}
                {{ $wireChangeFn ? "wire:change=$wireChangeFn" : '' }}
                {{ $wireOnBlur ? "wire:blur=$wireOnBlur" : '' }}
                class="form-input {{ $class }} w-full h-[40px] i [border:none] text-center text-black focus:text-black placeholder-black font-montserrat text-sm focus:bg-[#DCEAEB] bg-[#DCEAEB] rounded-8xs  overflow-hidden flex flex-row  box-border items-center justify-center   sm:box-border"
                style="{{ $prefix ? 'padding-left: ' . $prefixPadding . 'px;' : '' }} '" />



        </div>
        @if ($error)
            <div id="error_{{ $wireModel }}" class="my-2 text-xs italic text-red-600 " style="padding-left:31px">
                {{ $error }}
            </div>
        @endif
    </div>
@endif


<style>
    ::placeholder {
        /* Chrome, Firefox, Opera, Safari 10.1+ */
        color: white;
        opacity: 1;
        /* Firefox */
    }
</style>
