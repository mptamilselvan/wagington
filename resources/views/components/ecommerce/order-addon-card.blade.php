@props([
    'addon',
    'addonImage' => null
])

<div class="flex items-center justify-between py-2">
    <div class="flex items-center space-x-3 flex-1 min-w-0">
        <!-- Addon Image -->
        <div class="flex-shrink-0 w-10 h-10 bg-gray-50 rounded overflow-hidden">
            @if($addonImage)
                <img 
                    src="{{ $addonImage }}" 
                    alt="{{ $addon->addon_name }}" 
                    class="w-full h-full object-cover"
                    loading="lazy"
                />
            @else
                <div class="w-full h-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
            @endif
        </div>

        <!-- Addon Details -->
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-700 truncate">
                {{ $addon->addon_name }}
            </p>
            @if($addon->addon_variant_display_name)
                <p class="text-xs text-gray-500 truncate">
                    {{ $addon->addon_variant_display_name }}
                </p>
            @endif
            <p class="text-xs text-gray-500">
                Qty: {{ $addon->quantity }}
            </p>
        </div>
    </div>

    <!-- Addon Price -->
    <div class="flex-shrink-0">
        <p class="text-sm font-medium text-gray-700">
            ${{ number_format($addon->total_price, 2) }}
        </p>
    </div>
</div>