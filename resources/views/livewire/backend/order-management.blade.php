<div class="min-h-screen bg-gray-50 lg:ml-72">
    <!-- Header -->
    <div class="px-4 sm:px-6 py-2 mt-3">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-4 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Order Management</h1>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Search -->
                    <div class="relative">
                        <input type="text" wire:model.live="search" class="w-full sm:w-80 px-4 py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" placeholder="Order number, Product name">
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <!-- Filter -->
                    <select wire:model.live="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm min-w-0">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="backordered">Backordered</option>
                        <option value="partially_backordered">Partially Backordered</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="refunded">Refunded</option>
                        <option value="payment_failed">Payment Failed</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="px-4 sm:px-6 pb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <!-- Mobile view -->
            <div class="block sm:hidden">
                @forelse($orders as $order)
                    @php $lastPayment = $order->payments->first(); @endphp
                    <div class="border-b border-gray-200 p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="font-medium text-gray-900">{{ $order->user->name ?? '—' }}</p>
                                <p class="text-sm text-gray-500">#{{ $order->order_number }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-gray-900">${{ number_format($order->total_amount, 2) }}</p>
                                <p class="text-sm text-gray-500">{{ $order->created_at->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                                @if($lastPayment && ($lastPayment->status === 'paid' || $lastPayment->status === 'succeeded'))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                                @elseif($lastPayment && $lastPayment->status === 'failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Failed</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                @endif
                                
                                @if($order->status === 'backordered')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        Backordered
                                    </span>
                                @elseif($order->status === 'partially_backordered')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        Partial
                                    </span>
                                @endif
                                
                                <span class="text-sm text-gray-500">
                                    @php
                                        $itemsQty = $order->items->sum('quantity');
                                        $addonsQty = $order->items->flatMap->addons->sum('quantity');
                                    @endphp
                                    {{ $itemsQty + $addonsQty }} items
                                </span>
                            </div>
                            <a href="{{ route('order-details', $order->order_number) }}" class="px-3 py-1.5 border rounded-lg text-gray-700 hover:bg-gray-50 text-sm">View</a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-500">No orders found.</div>
                @endforelse
            </div>
            
            <!-- Desktop view -->
            <table class="hidden sm:table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date of Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fulfillment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Items</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                        @php $lastPayment = $order->payments->first(); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $order->user->name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">#{{ $order->order_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $order->created_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${{ number_format($order->total_amount, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($lastPayment && ($lastPayment->status === 'paid' || $lastPayment->status === 'succeeded'))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>
                                @elseif($lastPayment && $lastPayment->status === 'failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Failed</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $fulfillmentSummary = $order->getFulfillmentSummary() ?? [];
                                    $progress = min(max($fulfillmentSummary['progress_percentage'] ?? 0, 0), 100);
                                @endphp
                                <div class="flex items-center space-x-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 w-16">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-600">{{ $progress }}%</span>
                                </div>
                                <div class="mt-1">
                                    @if($order->status === 'backordered')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            Backordered
                                        </span>
                                    @elseif($order->status === 'partially_backordered')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            Partial Backorder
                                        </span>
                                    @elseif($fulfillmentSummary['is_fully_fulfilled'] ?? false)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Complete</span>
                                    @elseif($order->status === 'partially_shipped')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Partial</span>
                                    @elseif($order->status === 'shipped')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Shipped</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Pending</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                @php
                                    $itemsQty = $order->items->sum('quantity');
                                    $addonsQty = $order->items->flatMap->addons->sum('quantity');
                                @endphp
                                {{ $itemsQty + $addonsQty }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <a href="{{ route('order-details', $order->order_number) }}" class="px-3 py-1.5 border rounded-lg text-gray-700 hover:bg-gray-50">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-4 py-3 bg-white border-t">
                {{ $orders->onEachSide(1)->links() }}
            </div>
        </div>
    </div>
</div>