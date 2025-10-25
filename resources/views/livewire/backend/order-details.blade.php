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
</div>