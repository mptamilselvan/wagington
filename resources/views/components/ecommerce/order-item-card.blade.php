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
                            'awaiting_handover' => ['bg-purple-100 text-purple-800', 'Awaiting Handover'],
                            'awaiting_stock' => ['bg-red-100 text-red-800', 'Awaiting Stock'],
                            'handed_over' => ['bg-green-100 text-green-800', 'Handed Over'],
                            default => ['bg-gray-100 text-gray-800', ucfirst(str_replace('_', ' ', $fulfillmentStatus))]
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
                        @elseif($fulfillmentStatus === 'pending')
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @elseif($fulfillmentStatus === 'awaiting_stock')
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        @elseif($fulfillmentStatus === 'handed_over')
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                        {{ $statusConfig[1] }}
                    </span>
                    
                    <!-- Fulfillment Progress Steps - Match customer side layout -->
                    @php
                        // Determine if non-shippable
                        $isNonShippable = in_array($fulfillmentStatus, ['awaiting_handover', 'handed_over']);
                        
                        if ($isNonShippable) {
                            // Non-shippable: awaiting_handover -> handed_over
                            $progressSteps = [
                                ['icon' => 'clipboard', 'label' => 'Awaiting Handover', 'active' => in_array($fulfillmentStatus, ['awaiting_handover', 'handed_over'])],
                                ['icon' => 'check', 'label' => 'Handed Over', 'active' => $fulfillmentStatus === 'handed_over']
                            ];
                        } else {
                            // Shippable products
                            $progressSteps = [];
                            
                            // Only add awaiting_stock if item is in that status
                            if ($fulfillmentStatus === 'awaiting_stock') {
                                $progressSteps[] = ['icon' => 'clock', 'label' => 'Awaiting Stock', 'active' => true];
                            }
                            
                            $progressSteps[] = ['icon' => 'cog', 'label' => 'Processing', 'active' => in_array($fulfillmentStatus, ['processing', 'shipped', 'delivered'])];
                            $progressSteps[] = ['icon' => 'truck', 'label' => 'Shipped', 'active' => in_array($fulfillmentStatus, ['shipped', 'delivered'])];
                            $progressSteps[] = ['icon' => 'check', 'label' => 'Delivered', 'active' => $fulfillmentStatus === 'delivered'];
                        }
                    @endphp
                    <div class="mt-3">
                        <div class="flex items-center justify-between">
                            @foreach($progressSteps as $index => $step)
                                <div class="flex flex-col items-center flex-1">
                                    <!-- Step Icon -->
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $step['active'] ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-400' }}">
                                        @if($step['icon'] === 'clock')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @elseif($step['icon'] === 'clipboard')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        @elseif($step['icon'] === 'cog')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        @elseif($step['icon'] === 'truck')
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"/></svg>
                                        @elseif($step['icon'] === 'check')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    </div>
                                    
                                    <!-- Step Label -->
                                    <p class="text-xs text-center mt-1 {{ $step['active'] ? 'text-blue-600 font-medium' : 'text-gray-500' }}">
                                        {{ $step['label'] }}
                                    </p>
                                </div>
                                
                                <!-- Progress Line -->
                                @if($index < count($progressSteps) - 1)
                                    <div class="flex-1 h-0.5 mx-2 {{ $progressSteps[$index + 1]['active'] ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
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