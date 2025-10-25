@props([
    'item',
    'variant' => null,
    'productImage' => null,
    'addonImages' => [],
    'order' => null
])

<div class="flex items-start space-x-4 p-4 border border-gray-200 rounded-lg bg-white hover:shadow-sm transition-shadow">
    <!-- Product Image -->
    <div class="flex-shrink-0 w-20 h-20 bg-gray-100 rounded-lg overflow-hidden">
        @if($productImage)
            <img 
                src="{{ $productImage }}" 
                alt="{{ $item->product_name }}" 
                class="w-full h-full object-cover"
                loading="lazy"
            />
        @else
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        @endif
    </div>

    <!-- Product Details -->
    <div class="flex-1 min-w-0">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between">
            <div class="min-w-0 flex-1">
                <h3 class="text-base font-medium text-gray-900 truncate">
                    {{ $item->product_name }}
                </h3>
                @if($item->variant_display_name)
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $item->variant_display_name }}
                    </p>
                @endif
                
                <!-- Status Badge -->
                <div class="mt-2">
                    @php
                        $status = $order?->status ?? 'processing';
                        $statusConfig = match($status) {
                            'processing' => ['bg-blue-100 text-blue-800', 'Processing'],
                            'partially_backordered' => ['bg-yellow-100 text-yellow-800', 'Partially Backordered'],
                            'backordered' => ['bg-orange-100 text-orange-800', 'Backordered'],
                            'shipped' => ['bg-green-100 text-green-800', 'Shipped'],
                            'delivered' => ['bg-gray-100 text-gray-800', 'Delivered'],
                            default => ['bg-blue-100 text-blue-800', ucfirst($status)]
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusConfig[0] }}">
                        {{ $statusConfig[1] }}
                    </span>
                </div>

                <!-- Expected Delivery (if available) -->
                <div class="mt-1 text-xs text-gray-500">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m-6 8h6m-6 4h6m2-10h4a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V9a2 2 0 012-2h4z" />
                        </svg>
                        @if($item->estimated_delivery_at)
                            Delivering on {{ $item->estimated_delivery_at->format('jS F, Y') }}
                        @endif
                    </div>
                </div>
                
                <!-- Order Status Progress -->
                @if($order)
                    <div class="mt-4">
                        <x-ecommerce.order-status-progress :order="$order" />
                    </div>
                @endif
            </div>

            <!-- Quantity and Price -->
            <div class="flex-shrink-0 text-left sm:text-right mt-2 sm:mt-0 sm:ml-4">
                <p class="text-sm text-gray-500">Qty: {{ $item->quantity }}</p>
                <p class="text-base font-semibold text-gray-900">
                    ${{ number_format($item->total_price, 2) }}
                </p>
            </div>
        </div>

        <!-- Add-ons -->
        @if($item->addons && $item->addons->count())
            <div class="mt-4 pl-4 border-l-2 border-gray-100 space-y-2">
                <p class="text-sm font-medium text-gray-700">Add-ons:</p>
                @foreach($item->addons as $addon)
                    <x-ecommerce.order-addon-card 
                        :addon="$addon" 
                        :addonImage="$addonImages[$addon->id] ?? null" 
                    />
                @endforeach
            </div>
        @endif
    </div>
</div>