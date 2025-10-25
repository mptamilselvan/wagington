{{-- BLUE buttons --}}
{{-- type submit ,  primary button in blue  --}}
@if ($type == 'submit')
    <button type="submit" class="w-full button-primary" @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($wire_load)) wire:loading.attr="disabled" @endif
        @if (isset($onClickFn)) onclick='{{ $onClickFn }}' @endif
        @if (isset($disabled) && $disabled == true) disabled="" @endif>
        <span
            class="absolute w-0 h-0 transition-all duration-300 ease-out bg-white rounded-full group-hover:w-full group-hover:h-32 opacity-10"></span>
        <span class="relative">{{ ucfirst($label) }}</span>
    </button>
@endif

@if ($type == 'submitSmall')
    <button type="submit" class="button-primary-small bg-[#1B85F3]" @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($wire_load)) wire:loading.attr="disabled" @endif
        @if (isset($onClickFn)) onclick='{{ $onClickFn }}' @endif
        @if (isset($disabled) && $disabled == true) disabled="" @endif>
        {{-- <span class="">{{ ucfirst($label) }}</span> --}}
        <span wire:loading.remove wire:target="save">
            {{ ucfirst($label) }}
        </span>
        <!-- Loader text / spinner when saving -->
        <span wire:loading wire:target="save">
            <i class="fas fa-spinner fa-spin"></i> Saving...
        </span>
    </button>
@endif


{{-- type button , primary button in blue --}}
@if ($type == 'button')
    <button type="button" class="w-full h-12 button-primary bg-[#1B85F3]"
        @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($onClickFn)) onclick='{{ $onClickFn }}' @endif
        @if (isset($disabled) && $disabled == true) disabled="" @endif>
        <span
            class="absolute w-0 h-0 transition-all duration-300 ease-out bg-white rounded-full group-hover:w-full group-hover:h-32 opacity-10"></span>
        <span class="relative">{{ ucfirst($label) }}</span>
    </button>
@endif

@if ($type == 'buttonSmall')
    <button type="button" class="button-primary bg-[#1B85F3] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] "
        @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($disabled) && $disabled == true) disabled="" @endif wire:loading.attr="disabled">
        {{-- <span
        class="absolute w-0 h-0 transition-all duration-300 ease-out bg-white rounded-full group-hover:w-full group-hover:h-32 opacity-10"></span> --}}
        <span wire:loading.remove wire:target="save">
            {{ ucfirst($label) }}
        </span>
        <!-- Loader text / spinner when saving -->
        <span wire:loading wire:target="save">
            <i class="fas fa-spinner fa-spin"></i> Saving...
        </span>
    </button>


@endif

{{-- type button , gray button --}}
@if ($type == 'graybuttonSmall')
    <button type="button" class="bg-[#F6F6F6]  rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] @if(isset($class) && $class != '') {{ $class }} @endif"
        @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($disabled)) disabled="" @endif>
        {{-- <span
        class="absolute w-0 h-0 transition-all duration-300 ease-out bg-white rounded-full group-hover:w-full group-hover:h-32 opacity-10"></span> --}}
        <span class="">{{ ucfirst($label) }}</span>
    </button>
@endif


{{-- type button , secondary button in blue --}}
@if ($type == 'cancel')
    <button type="button" @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        class="button-secondary" @if (isset($id)) id="{{ $id }}" @endif>
        {{ ucfirst($label) }}
    </button>
@endif

@if ($type == 'cancelSmall')
    <button type="button" @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] "
        @if (isset($id)) id="{{ $id }}" @endif  @if (isset($disabled) && $disabled == true) disabled="" @endif>
        <label class="font-semibold text-gray-700">{{ ucfirst($label) }}</label>
    </button>
@endif



{{-- type button ,blue text buttons , with underline --}}
@if ($type == 'TextButtonUnder')
    <button type="button" @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        class="text-button-underline" @if (isset($id)) id="{{ $id }}" @endif>
        {{ ucfirst($label) }}
    </button>
@endif

{{-- type button ,blue text buttons , without underline --}}
@if ($type == 'TextButton')
    <button type="button" @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        class="text-button" @if (isset($id)) id="{{ $id }}" @endif>
        {{ ucfirst($label) }}
    </button>
@endif



{{-- RED Buttons --}}

{{-- type button , secondary button in red --}}
@if ($type == 'cancelRed')
    <button type="button" @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        class="button-secondary-red" @if (isset($id)) id="{{ $id }}" @endif>
        {{ ucfirst($label) }}
    </button>
@endif

@if ($type == 'cancelRedSmall')
    <button type="button" @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        class="button-secondary-red rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] "
        @if (isset($id)) id="{{ $id }}" @endif>
        {{ ucfirst($label) }}
    </button>
@endif


{{-- red text button , without underline --}}
@if ($type == 'TextButtonRed')
    <button type="button" @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        class="text-button text-state-red hover:text-state-red/80"
        @if (isset($id)) id="{{ $id }}" @endif>
        {{ ucfirst($label) }}
    </button>
@endif

{{-- red  button for submit --}}
@if ($type == 'buttonSmallRed')
    <button type="button" class="button text-white bg-[#D93D2E]  rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] "
        @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($disabled) && $disabled == true) disabled="" @endif>
        {{-- <span
        class="absolute w-0 h-0 transition-all duration-300 ease-out bg-white rounded-full group-hover:w-full group-hover:h-32 opacity-10"></span> --}}
        <span class="">{{ ucfirst($label) }}</span>
    </button>
@endif

{{-- custom primary --}}
@if ($type == 'customPrimary')
    <button type="submit"
        class="sm:whitespace-nowrap button-strip rounded-[8px] manrope-700  capitalize tracking-wider leading-tight px-1 h-[36px] w-full  max-w-[123px]  lg:w-[123px] text-[13px] lg:text-[14px] @if (isset($classNames)) {{ $classNames }} @endif"
        @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($wire_load)) wire:loading.attr="disabled" @endif
        @if (isset($onClickFn)) onclick='{{ $onClickFn }}' @endif
        @if (isset($disabled) && $disabled == true) disabled="" @endif>

        @if (isset($label))
            <span class="">{{ ucfirst($label) }}</span>
        @endif

        @if (isset($slot))
            {{ $slot }}
        @endif
    </button>
@endif


@if ($type == 'customSecondary')
    <button type="submit"
        class="sm:whitespace-nowrap button-strip rounded-[8px] manrope-700  capitalize tracking-wider leading-tight px-1 h-[36px] w-full  max-w-[123px]  lg:w-[123px] text-[13px] lg:text-[14px] @if (isset($classNames)) {{ $classNames }} @endif"
        @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($wire_load)) wire:loading.attr="disabled" @endif
        @if (isset($onClickFn)) onclick='{{ $onClickFn }}' @endif
        @if (isset($disabled) && $disabled == true) disabled="" @endif>

        @if (isset($label))
            <span class="">{{ ucfirst($label) }}</span>
        @endif

        @if (isset($slot))
            {{ $slot }}
        @endif
    </button>
@endif


@if ($type == 'inputSubmitSmall')
    <input type="submit" class="button-primary-small"
        @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($wire_load)) wire:loading.attr="disabled" @endif
        @if (isset($onClickFn)) onclick='{{ $onClickFn }}' @endif
        @if (isset($disabled) && $disabled == true) disabled="" @endif value="{{ ucfirst($label) }}">
@endif


@if ($type == 'buttongv')
    <button type="button" class="w-full bg-[#9EAEFF] rounded-md h-[40px] px-3"
        @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($onClickFn)) onclick='{{ $onClickFn }}' @endif
        @if (isset($disabled) && $disabled == true) disabled="" @endif>
        <span wire:loading.remove class="relative">{{ ucfirst($label) }}</span>
        <span wire:loading class="relative">
            <!-- Add your spinner HTML here -->
            <svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647zM12 20a8 8 0 100-16 8 8 0 000 16zM20 4.709l-3 2.646A7.963 7.963 0 0120 12h4c0-3.042-1.135-5.823-3-7.938z"></path>
            </svg>
        </span>
    </button>
@endif

@if ($type == 'buttonfc')
    <button type="button" class="w-full bg-[#2aaaac] text-white rounded-md h-[40px] px-3"
        @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($onClickFn)) onclick='{{ $onClickFn }}' @endif
        @if (isset($disabled) && $disabled == true) disabled="" @endif>
        <span wire:loading.remove class="relative">{{ ucfirst($label) }}</span>
        <span wire:loading class="relative">
            <!-- Add your spinner HTML here -->
            <svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647zM12 20a8 8 0 100-16 8 8 0 000 16zM20 4.709l-3 2.646A7.963 7.963 0 0120 12h4c0-3.042-1.135-5.823-3-7.938z"></path>
            </svg>
        </span>
    </button>
@endif

@if ($type == 'cancelSmallgv')
    <button type="button" @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        class="border border-[#9EAEFF] px-3 h-[40px] w-full rounded-[8px]  text-[13px] lg:text-[14px] "
        @if (isset($id)) id="{{ $id }}" @endif>
        {{ ucfirst($label) }}
    </button>
@endif

@if ($type == 'buttonwaith')
    <button type="button"
        class="w-full bg-[#4e68eb] text-white rounded-md h-[40px] px-3"
        wire:loading.attr="disabled"
        @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif
        @if (isset($id)) id="{{ $id }}" @endif
        @if (isset($onClickFn)) onclick="{{ $onClickFn }}" @endif
        @if (isset($disabled) && $disabled == true) disabled @endif>
        <span wire:loading.remove class="relative">{{ ucfirst($label) }}</span>
        <span wire:loading class="relative">
            <!-- Add your spinner HTML here -->
            <svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647zM12 20a8 8 0 100-16 8 8 0 000 16zM20 4.709l-3 2.646A7.963 7.963 0 0120 12h4c0-3.042-1.135-5.823-3-7.938z"></path>
            </svg>
        </span>
    </button>
@endif