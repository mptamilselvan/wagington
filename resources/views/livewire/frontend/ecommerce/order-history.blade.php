<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-gray-50 min-h-screen">
            {{-- Header Section --}}
            <div class="flex items-center mb-6">
                <button onclick="closeDashboardSlider()" class="mr-4 p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">My Order History</h1>
                    <p class="text-sm text-gray-600 mt-1">View all your order details here</p>
                </div>
            </div>

            {{-- Orders List --}}
            @if($orders->count() > 0)
                <div class="space-y-6">
                    @foreach($orders as $order)
                        <div class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-shadow cursor-pointer"
                             role="button"
                             tabindex="0"
                             wire:click="goToOrderDetail('{{ $order['order_number'] }}')"
                             wire:keydown.enter="goToOrderDetail('{{ $order['order_number'] }}')"
                             wire:keydown.space.prevent="goToOrderDetail('{{ $order['order_number'] }}')">
                            {{-- Order Header --}}
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                                <div class="mb-2 sm:mb-0">
                                    <div class="flex items-center gap-4">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900">Order Number: {{ $order['order_number'] }}</h3>
                                            <p class="text-sm text-gray-600">
                                                Date of Order: {{ \Carbon\Carbon::parse($order['placed_at'])->format('d F Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="text-right mr-4">
                                        <p class="text-sm font-medium text-gray-900">Total Amount: ${{ number_format($order['total_amount'], 2) }}</p>
                                        <p class="text-sm text-gray-600">Total Items: {{ $order['total_items'] }}</p>
                                    </div>
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>

                            {{-- Items Preview --}}
                            <div class="space-y-3">
                                @foreach($order['items_preview'] as $item)
                                    <div class="flex items-center">
                                        {{-- Product image or placeholder --}}
                                        <div class="w-12 h-12 mr-4 flex-shrink-0">
                                            @if(!empty($item['image_url']))
                                                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="w-12 h-12 object-cover rounded-lg"/>
                                            @else
                                                <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <div class="flex-1">
                                            <h4 class="text-sm font-medium text-gray-900">{{ $item['name'] }}</h4>
                                            @if(!empty($item['variant']))
                                                <p class="text-xs text-gray-600">{{ $item['variant'] }}</p>
                                            @endif
                                        </div>
                                        
                                        {{-- Status Badge --}}
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                            {{ ucfirst($order['status']) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($orders->hasPages())
                    <div class="mt-8">
                        {{ $orders->links() }}
                    </div>
                @endif
            @else
                {{-- Empty State --}}
                <div class="flex flex-col items-center justify-center py-16">
                    {{-- Empty State Illustration --}}
                    <div class="w-32 h-32 mb-6">
                        <svg viewBox="0 0 200 200" class="w-full h-full text-gray-400">
                            {{-- Isometric boxes illustration --}}
                            <g fill="currentColor" opacity="0.3">
                                <polygon points="60,80 100,60 140,80 100,100" />
                                <polygon points="60,80 100,100 100,140 60,120" />
                                <polygon points="100,100 140,80 140,120 100,140" />
                                <polygon points="80,100 120,80 160,100 120,120" />
                                <polygon points="80,100 120,120 120,160 80,140" />
                                <polygon points="120,120 160,100 160,140 120,160" />
                            </g>
                            {{-- Character figure --}}
                            <g fill="currentColor" opacity="0.4">
                                <circle cx="150" cy="70" r="8" />
                                <rect x="145" y="78" width="10" height="20" rx="2" />
                                <rect x="140" y="98" width="6" height="12" rx="1" />
                                <rect x="154" y="98" width="6" height="12" rx="1" />
                                <rect x="142" y="85" width="16" height="8" rx="2" />
                            </g>
                        </svg>
                    </div>
                    
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No orders yet</h3>
                    <p class="text-gray-600 text-center max-w-md mb-8">
                        No orders made yet, order now to checkout the details.
                    </p>
                    
                    <a href="{{ route('shop.list') }}" 
                       class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Start Shopping
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>
            @endif
</div>