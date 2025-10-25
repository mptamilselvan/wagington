{{-- Enhanced Form Buttons Component --}}
@props([
'submitText' => '',
'cancelText' => 'Cancel',
'showCancel' => false,
'cancelAction' => '',
'submitClass' => '',
'cancelClass' => '',
'layout' => 'right', // left, center, right, full, space-between
'size' => 'default', // small, default, large
'loading' => false,
'loadingText' => 'Processing...',
'disabled' => false,
'submitType' => 'submit', // submit, button
'cancelType' => 'button',
'submitVariant' => 'primary', // primary, secondary, success, danger
'cancelVariant' => 'secondary',
'type' => '',
'submit' => 'Save',

])

@php
// Size classes
$sizeClasses = [
'small' => 'px-2 py-1 text-sm',
'default' => 'px-10 py-3 text-base',
'large' => 'px-6 py-2 text-lg'
];

// Variant classes
$variantClasses = [
'primary' => 'bg-blue-500 hover:bg-blue-600/90 text-white focus:ring-blue-500 disabled:bg-blue-300
shadow-[0_1px_0_0_rgba(255,255,255,0.25)_inset,0_0_0_1px_rgba(0,0,0,0.04),0_2px_6px_rgba(0,0,0,0.08)]',
'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-900 focus:ring-gray-500 disabled:bg-gray-100',
'success' => 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500 disabled:bg-green-300',
'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500 disabled:bg-red-300'
];

// Layout classes
$layoutClasses = [
'left' => 'justify-start',
'center' => 'justify-center',
'right' => 'justify-end',
'full' => 'justify-stretch',
'space-between' => 'justify-between'
];

$buttonSize = $sizeClasses[$size] ?? $sizeClasses['default'];
$submitVariantClass = $variantClasses[$submitVariant] ?? $variantClasses['primary'];
$cancelVariantClass = $variantClasses[$cancelVariant] ?? $variantClasses['secondary'];
$layoutClass = $layoutClasses[$layout] ?? $layoutClasses['right'];
@endphp

<div class="flex {{ $layoutClass }} {{ $layout === 'full' ? 'flex-col space-y-3' : 'space-x-3' }} mt-3">
    @if($showCancel && $layout === 'space-between')
    {{-- Cancel Button (Left side for space-between) --}}
    <button type="{{ $cancelType }}" @if($cancelAction) {{ $cancelAction }} @endif @if($disabled || $loading) disabled
        @endif
        class="inline-flex items-center justify-center {{ $buttonSize }} font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $cancelVariantClass }} {{ $layout === 'full' ? 'w-full' : '' }} font-rubik-button {{ $cancelClass }}">
        {{ $cancelText }}
    </button>
    @endif



    @if($showCancel && $layout !== 'space-between')
    {{-- Cancel Button (Right side or below for other layouts) --}}
    <button type="{{ $cancelType }}" @if($cancelAction) {{ $cancelAction }} @endif @if($disabled || $loading) disabled
        @endif
        class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] {{ $cancelVariantClass }} {{ $layout === 'full' ? 'w-full' : '' }} font-rubik-button {{ $cancelClass }}">
        {{ $cancelText }}
    </button>
    @endif

    {{-- Submit Button --}}
    @if($submitText)
    <button type="{{ $submitType }}" @if($disabled || $loading) disabled @endif
        class="w-full px-10 py-3 mt-1 text-sm font-semibold text-white transition-colors bg-blue-500 rounded-lg hover:bg-blue-600">
        @if($loading)
        {{-- Loading Spinner --}}
        <svg class="w-5 h-5 mr-3 -ml-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
        </svg>
        {{ $loadingText }}
        @else
        {{ $submitText }}
        @endif
    </button>
    @endif
    @if($type == 'save-cancel')
    <div class="flex flex-col items-center justify-end pt-6 space-y-3 sm:flex-row sm:space-y-0 sm:space-x-4">
        <button type="{{ $cancelType }}" @if($cancelAction) {{ $cancelAction }} @endif @if($disabled || $loading)
            disabled @endif
             class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] text-gray-500" {{ $cancelVariantClass }} {{ $layout === 'full' ? 'w-full' : '' }} font-rubik-button {{ $cancelClass }}">
            {{ $cancelText }}
        </button>
        <button type="submit" class="button-primary-small bg-[#1B85F3]">
            {{ $submit }}
        </button>
    </div>
    @endif
</div>