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
                
                <!-- Fulfillment Status Badge -->
                <div class="mt-2">
                    @php
                        $fulfillmentStatus = $item->fulfillment_status ?? 'pending';
                        $statusConfig = match($fulfillmentStatus) {
                            'pending' => ['bg-gray-100 text-gray-800', 'Pending'],
                            'processing' => ['bg-yellow-100 text-yellow-800', 'Processing'],
                            'shipped' => ['bg-blue-100 text-blue-800', 'Shipped'],
                            'delivered' => ['bg-green-100 text-green-800', 'Delivered'],
                            'awaiting_handover' => ['bg-purple-100 text-purple-800', 'Digital'],
                            default => ['bg-gray-100 text-gray-800', ucfirst($fulfillmentStatus)]
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusConfig[0] }}">
                        @if($fulfillmentStatus === 'delivered')
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @elseif($fulfillmentStatus === 'shipped')
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"></path>
                            </svg>
                        @elseif($fulfillmentStatus === 'processing')
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                            </svg>
                        @elseif($fulfillmentStatus === 'awaiting_handover')
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                        {{ $statusConfig[1] }}
                    </span>
                    
                    <!-- Fulfillment Progress -->
                    @if($fulfillmentStatus !== 'awaiting_handover')
                        @php
                            $progress = (int) ($item->getFulfillmentProgress() ?? 0);
                            $progress = max(0, min(100, $progress)); // Clamp between 0-100
                        @endphp
                        <div class="mt-1">
                            <div class="flex items-center space-x-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-1.5 w-16">
                                    <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" 
                                         style="width: {{ $progress }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $progress }}%</span>
                            </div>
                        </div>
                    @endif
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