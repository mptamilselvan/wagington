@props([
    'icon' => null,
    'variant' => 'default', // default, success, warning, danger
    'disabled' => false
])

@php
$classes = match($variant) {
    'success' => 'text-green-700 hover:bg-green-50 hover:text-green-700',
    'warning' => 'text-yellow-700 hover:bg-yellow-50 hover:text-yellow-700',
    'danger' => 'text-red-700 hover:bg-red-50 hover:text-red-700',
    default => 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
};

$classes .= $disabled ? ' opacity-50 cursor-not-allowed' : '';
@endphp

<button {{ $attributes->merge([
    'type' => 'button',
    'class' => "flex items-center w-full px-4 py-2 text-sm text-left transition-colors duration-150 {$classes}",
    'disabled' => $disabled
]) }}
    @click="open = false">
    
    @if($icon)
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            @if($icon === 'eye')
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            @elseif($icon === 'edit')
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            @elseif($icon === 'trash')
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            @elseif($icon === 'check')
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            @elseif($icon === 'x')
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            @elseif($icon === 'refresh')
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            @endif
        </svg>
    @endif
    
    {{ $slot }}
</button>