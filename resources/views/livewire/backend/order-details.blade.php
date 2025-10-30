<div class="min-h-screen bg-gray-50 lg:ml-72">
    <!-- Header -->
    <div class="px-4 sm:px-6 py-2 mt-3">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-4 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h1 class="text-lg sm:text-xl font-semibold text-gray-900">
                    <span class="hidden sm:inline">← </span>View Order Details
                </h1>
                <div>
                    <a href="{{ route('order-management') }}" class="inline-flex items-center px-3 py-1.5 border rounded-lg text-gray-700 hover:bg-gray-50 text-sm">
                        <svg class="w-4 h-4 mr-1 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Grid -->
    <div class="px-4 sm:px-6 pb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                    <p class="text-xs text-gray-500">Customer Name</p>
                    <p class="text-sm font-medium text-gray-900">{{ $order->user->name ?? '—' }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                    <p class="text-xs text-gray-500">Order Number</p>
                    <p class="text-sm font-medium text-gray-900">#{{ $order->order_number }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                    <p class="text-xs text-gray-500">Date of Order</p>
                    <p class="text-sm font-medium text-gray-900">{{ $order->created_at->format('d/m/Y') }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                    <p class="text-xs text-gray-500">Total Items</p>
                    <p class="text-sm font-medium text-gray-900">{{ $order->items->sum('quantity') }}</p>
                </div>
            </div>

            <!-- Fulfillment Status -->
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-4">Fulfillment Status</h3>
                @php
                    $fulfillmentSummary = $this->getFulfillmentSummary();
                @endphp
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            <div class="flex-1 bg-gray-200 rounded-full h-3 w-32">
                                <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: {{ $fulfillmentSummary['progress_percentage'] }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-700">{{ $fulfillmentSummary['progress_percentage'] }}% Complete</span>
                        </div>
                        <div>
                            @if($fulfillmentSummary['is_fully_fulfilled'])
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Fully Fulfilled
                                </span>
                            @elseif($order->status === 'partially_shipped')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Partially Shipped
                                </span>
                            @elseif($order->status === 'shipped')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                                        <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"></path>
                                    </svg>
                                    Shipped
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                    </svg>
                                    Pending
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Fulfillment Actions -->
                    <!-- Debug: is_fully_fulfilled = {{ $fulfillmentSummary['is_fully_fulfilled'] ? 'true' : 'false' }} -->
                    @if(!$fulfillmentSummary['is_fully_fulfilled'])
                        <div class="flex flex-wrap gap-2">
                            <button wire:click="openFulfillmentAction('processing')" type="button" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" wire:loading.attr="disabled">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                </svg>
                                Mark Processing
                            </button>
                            <button wire:click="openFulfillmentAction('shipped')" type="button" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" wire:loading.attr="disabled">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                Mark Shipped
                            </button>
                            <button wire:click="openFulfillmentAction('delivered')" type="button" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" wire:loading.attr="disabled">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Mark Delivered
                            </button>
                            <button wire:click="openFulfillmentAction('handed_over')" type="button" class="inline-flex items-center px-3 py-1.5 border border-purple-300 rounded-md text-sm font-medium text-purple-700 bg-purple-50 hover:bg-purple-100" wire:loading.attr="disabled">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Mark as Handed Over
                            </button>
                        </div>
                    @else
                        <!-- Order is fully fulfilled, showing debug info -->
                        <div class="text-sm text-gray-500">Order is fully fulfilled. No fulfillment actions available.</div>
                    @endif
                </div>
            </div>

            <!-- Addresses -->
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-4">Address Details</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                    <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                        <p class="text-xs text-gray-500 mb-2">Billing Address</p>
                        <x-ecommerce.address-display :address="$order->billingAddress" title="Billing Address" />
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                        <p class="text-xs text-gray-500 mb-2">Shipping Address</p>
                        <x-ecommerce.address-display :address="$order->shippingAddress" title="Shipping Address" />
                    </div>
                </div>
            </div>

            <!-- Payment Details -->
            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-sm font-medium text-gray-900">Payment Details</h3>
                    <button wire:click="toggleBreakdown" class="text-blue-600 text-sm hover:underline">View Price Breakdown</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                        <p class="text-xs text-gray-500">Total Amount</p>
                        <p class="text-sm font-medium text-gray-900">${{ number_format($order->total_amount, 2) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                        <p class="text-xs text-gray-500">Payment Date</p>
                        <p class="text-sm font-medium text-gray-900">{{ optional($order->payments->first())->created_at?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                        <p class="text-xs text-gray-500">Payment Status</p>
                        @php $p = $order->payments->first(); @endphp
                        @if($p && ($p->status === 'paid' || $p->status === 'succeeded'))
                            <p class="text-sm font-medium text-green-800">Paid</p>
                        @elseif($p && $p->status === 'failed')
                            <p class="text-sm font-medium text-red-800">Failed</p>
                        @else
                            <p class="text-sm font-medium text-yellow-800">Pending</p>
                        @endif
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                        <p class="text-xs text-gray-500">Mode of Payment</p>
                        <p class="text-sm font-medium text-gray-900">{{ $p?->payment_gateway ?? '—' }} {{ $p?->card_last4 ? ' •••• '.$p->card_last4 : '' }}</p>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="space-y-4">
                <h3 class="text-sm font-medium text-gray-900">Item Details</h3>
                @foreach($order->items as $item)
                    <x-ecommerce.order-item-card 
                        :item="$item" 
                        :order="$order"
                        :productImage="$productImages[$item->product_id] ?? null"
                        :addonImages="collect($item->addons)->mapWithKeys(fn($addon) => [$addon->id => $addonImages[$addon->addon_product_id] ?? null])->all()"
                    />
                @endforeach
            </div>
        </div>
    </div>

    <!-- Price Breakdown Modal -->
    <div x-data="{ open: @entangle('showBreakdown') }" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-40" @click="open = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-[90%] max-w-md p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold">Price Breakdown</h3>
                <button class="text-gray-500 hover:text-gray-700" @click="open = false">✕</button>
            </div>
            <div class="space-y-2 max-h-80 overflow-y-auto">
                @foreach($order->items as $item)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-700 truncate">{{ $item->product_name }}</span>
                        <span class="text-gray-900 font-medium">${{ number_format($item->total_price, 2) }}</span>
                    </div>
                @endforeach
                <div class="border-t pt-3 mt-3 space-y-1 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="text-gray-900">${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    @if($order->discount_amount > 0)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Discount</span>
                        <span class="text-gray-900">- ${{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Tax</span>
                        <span class="text-gray-900">${{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Shipping</span>
                        <span class="text-gray-900">${{ number_format($order->shipping_amount, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between font-semibold text-gray-900 pt-2">
                        <span>Total Amount</span>
                        <span>${{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fulfillment Modal -->
    @if($showFulfillmentModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeFulfillmentAction">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        @if($fulfillmentAction === 'processing')
                            Mark Items as Processing
                        @elseif($fulfillmentAction === 'shipped')
                            Mark Items as Shipped
                        @elseif($fulfillmentAction === 'delivered')
                            Mark Items as Delivered
                        @elseif($fulfillmentAction === 'handed_over')
                            Mark Items as Handed Over
                        @endif
                    </h3>
                    
                    <!-- Item Selection -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-medium text-gray-700">Select Items & Add-ons</label>
                            <div class="flex gap-2">
                                <button wire:click="selectAllShippableItems" class="text-xs text-blue-600 hover:text-blue-800">Select All</button>
                                <button wire:click="clearSelection" class="text-xs text-gray-600 hover:text-gray-800">Clear</button>
                            </div>
                        </div>
                        <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-2 space-y-2">
                            @foreach($order->items as $item)
                                <div class="border-b border-gray-100 pb-2 last:border-0 last:pb-0">
                                    <!-- Main Item -->
                                    <div class="flex items-center space-x-2">
                                        <!-- Show checkbox only if item is eligible for current action -->
                                        @if($this->isItemEligibleForAction($item, $fulfillmentAction))
                                            <input type="checkbox" 
                                                   wire:model="selectedItems" 
                                                   value="item_{{ $item->id }}"
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        @else
                                            <!-- Show disabled checkbox or no checkbox for non-eligible items -->
                                            <div class="w-4 h-4"></div>
                                        @endif
                                        <div class="flex-1">
                                            <span class="text-sm text-gray-900">{{ $item->product_name }}</span>
                                            <span class="text-xs text-gray-500 ml-2">({{ $item->quantity }}x)</span>
                                            <span class="text-xs ml-2 px-2 py-0.5 rounded-full 
                                                @if($item->fulfillment_status === 'pending') bg-gray-100 text-gray-800
                                                @elseif($item->fulfillment_status === 'processing') bg-yellow-100 text-yellow-800
                                                @elseif($item->fulfillment_status === 'shipped') bg-blue-100 text-blue-800
                                                @elseif($item->fulfillment_status === 'delivered') bg-green-100 text-green-800
                                                @elseif($item->fulfillment_status === 'awaiting_handover') bg-purple-100 text-purple-800
                                                @endif">
                                                {{ ucfirst($item->fulfillment_status) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Add-ons for this item -->
                                    @if($item->addons && $item->addons->count() > 0)
                                        <div class="ml-6 mt-1 space-y-1">
                                            @foreach($item->addons as $addon)
                                                <div class="flex items-center space-x-2 text-sm">
                                                    <!-- Show checkbox only if addon is eligible for current action -->
                                                    @if($this->isItemEligibleForAction($addon, $fulfillmentAction))
                                                        <input type="checkbox" 
                                                               wire:model="selectedItems" 
                                                               value="addon_{{ $addon->id }}"
                                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    @else
                                                        <!-- Show disabled checkbox or no checkbox for non-eligible addons -->
                                                        <div class="w-4 h-4"></div>
                                                    @endif
                                                    <div class="flex-1">
                                                        <span class="text-gray-700">{{ $addon->addon_name }}</span>
                                                        <span class="text-xs text-gray-500 ml-2">({{ $addon->quantity }}x)</span>
                                                        <span class="text-xs ml-2 px-2 py-0.5 rounded-full 
                                                            @if($addon->fulfillment_status === 'pending') bg-gray-100 text-gray-800
                                                            @elseif($addon->fulfillment_status === 'processing') bg-yellow-100 text-yellow-800
                                                            @elseif($addon->fulfillment_status === 'shipped') bg-blue-100 text-blue-800
                                                            @elseif($addon->fulfillment_status === 'delivered') bg-green-100 text-green-800
                                                            @elseif($addon->fulfillment_status === 'awaiting_handover') bg-purple-100 text-purple-800
                                                            @endif">
                                                            {{ ucfirst($addon->fulfillment_status) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @error('selectedItems') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Tracking Information (for shipped action) -->
                    @if($fulfillmentAction === 'shipped')
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tracking Number</label>
                            <input type="text" wire:model="trackingNumber" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter tracking number">
                            @error('trackingNumber') 
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Carrier</label>
                            <input type="text" wire:model="carrier" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="e.g., FedEx, UPS, DHL">
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3">
                        <button wire:click="closeFulfillmentAction" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button wire:click="processFulfillment" 
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span wire:loading.remove wire:target="processFulfillment">
                                @if($fulfillmentAction === 'processing')
                                    Mark Processing
                                @elseif($fulfillmentAction === 'shipped')
                                    Mark Shipped
                                @elseif($fulfillmentAction === 'delivered')
                                    Mark Delivered
                                @elseif($fulfillmentAction === 'handed_over')
                                    Mark as Handed Over
                                @endif
                            </span>
                            <span wire:loading wire:target="processFulfillment">
                                Processing...
                            </span>
                        </button>
                    </div>
                    
                    @error('fulfillment') 
                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        </div>
                    @enderror
                </div>
            </div>
        </div>
    @endif
</div>