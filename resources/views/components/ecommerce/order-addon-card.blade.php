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
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500">
                    Qty: {{ $addon->quantity }}
                </p>
                <!-- Fulfillment Status -->
                @php
                    $fulfillmentStatus = $addon->fulfillment_status ?? 'pending';
                    $statusConfig = match($fulfillmentStatus) {
                        'pending' => ['bg-gray-100 text-gray-800', 'Pending'],
                        'processing' => ['bg-yellow-100 text-yellow-800', 'Processing'],
                        'shipped' => ['bg-blue-100 text-blue-800', 'Shipped'],
                        'delivered' => ['bg-green-100 text-green-800', 'Delivered'],
                        'awaiting_handover' => ['bg-purple-100 text-purple-800', 'Awaiting Handover'],
                        'awaiting_stock' => ['bg-red-100 text-red-800', 'Awaiting Stock'],
                        'handed_over' => ['bg-green-100 text-green-800', 'Handed Over'],
                        default => ['bg-gray-100 text-gray-800', ucfirst(str_replace('_', ' ', $fulfillmentStatus))]
                    };
                @endphp
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium {{ $statusConfig[0] }}">
                    @if($fulfillmentStatus === 'delivered')
                        <svg class="w-2.5 h-2.5 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @elseif($fulfillmentStatus === 'shipped')
                        <svg class="w-2.5 h-2.5 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"></path>
                        </svg>
                    @elseif($fulfillmentStatus === 'awaiting_handover')
                        <svg class="w-2.5 h-2.5 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                    {{ $statusConfig[1] }}
                </span>
            </div>
        </div>
    </div>

    <!-- Addon Price -->
    <div class="flex-shrink-0">
        <p class="text-sm font-medium text-gray-700">
            ${{ number_format($addon->total_price, 2) }}
        </p>
    </div>
</div>