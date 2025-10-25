<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-white min-h-screen">
            @if($orderDetail)
                {{-- Header Section --}}
                <div class="flex items-center mb-6">
                    <button wire:click="goBack" class="mr-4 p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Order Details</h1>
                        <p class="text-sm text-gray-600 mt-1">View all your order details here</p>
                    </div>
                </div>

                {{-- Order Summary --}}
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Order Number</p>
                            <p class="font-medium">{{ $orderDetail['order']['order_number'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Amount</p>
                            <p class="font-medium">${{ number_format($orderDetail['order']['total_amount'], 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Date of Order</p>
                            <p class="font-medium">{{ \Carbon\Carbon::parse($orderDetail['order']['placed_at'])->format('d F Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Items</p>
                            <p class="font-medium">{{ $orderDetail['order']['total_items'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Order Items --}}
                <div class="space-y-6 mb-8">
                    @foreach($orderDetail['items'] as $item)
                        <div class="border border-gray-200 rounded-lg p-6">
                            {{-- Item Header --}}
                            <div class="flex items-start mb-4">
                                {{-- Product Image --}}
                                <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    @if($item['image_url'])
                                        <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover rounded-lg">
                                    @else
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    @endif
                                </div>

                                {{-- Item Details --}}
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900 mb-1">{{ $item['name'] }}</h3>
                                    @if($item['variant'])
                                        <p class="text-sm text-gray-600 mb-2">{{ $item['variant'] }}</p>
                                    @endif
                                    @if(!empty($item['attributes']))
                                        <div class="text-sm text-gray-600 mb-2">
                                            @foreach($item['attributes'] as $key => $value)
                                                <span class="inline-block mr-4">{{ $key }}: {{ $value }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <p class="text-sm text-gray-600">Quantity: {{ $item['qty'] }}</p>
                                </div>

                                {{-- Price and Delivery Info --}}
                                <div class="text-right">
                                    <p class="text-lg font-medium">${{ number_format($item['total_price'], 2) }}</p>
                                    @if($orderDetail['order']['estimated_delivery'])
                                        <p class="text-sm text-gray-600 mt-1">
                                            Arriving on {{ \Carbon\Carbon::parse($orderDetail['order']['estimated_delivery'])->format('jS F, Y') }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Progress Steps --}}
                            <div class="mb-4">
                                @php $steps = $this->getProgressSteps($orderDetail['order']['status']); @endphp
                                <div class="flex items-center justify-between">
                                    @foreach($steps as $index => $step)
                                        <div class="flex flex-col items-center flex-1">
                                            {{-- Step Icon --}}
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $step['active'] ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-400' }}">
                                                @switch($step['icon'])
                                                    @case('clipboard-list')
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                                        </svg>
                                                        @break
                                                    @case('cog')
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        </svg>
                                                        @break
                                                    @case('truck')
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        @break
                                                    @case('check-circle')
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        @break
                                                @endswitch
                                            </div>
                                            
                                            {{-- Step Label --}}
                                            <p class="text-xs text-center mt-2 {{ $step['active'] ? 'text-blue-600 font-medium' : 'text-gray-500' }}">
                                                {{ $step['label'] }}
                                            </p>
                                        </div>
                                        
                                        {{-- Progress Line --}}
                                        @if($index < count($steps) - 1)
                                            <div class="flex-1 h-0.5 mx-4 {{ $steps[$index + 1]['active'] ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            {{-- Addons --}}
                            @if(!empty($item['addons']))
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">Add-ons:</h4>
                                    @foreach($item['addons'] as $addon)
                                        <div class="flex items-center justify-between text-sm text-gray-600 mb-3">
                                            <div class="flex items-center gap-3">
                                                @if(!empty($addon['image_url']))
                                                    <img src="{{ $addon['image_url'] }}" class="w-10 h-10 rounded object-cover" alt="{{ $addon['name'] }}"/>
                                                @else
                                                    <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </div>
                                                @endif
                                                <span>{{ $addon['name'] }} (Qty: {{ $addon['qty'] }})</span>
                                            </div>
                                            <span>${{ number_format($addon['total_price'], 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Address and Payment Information --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Address Details --}}
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Address Details</h3>
                        
                        {{-- Billing Address --}}
                        @if($orderDetail['addresses']['billing'])
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Billing Address</h4>
                                <div class="text-sm text-gray-600">
                                    <p>{{ $orderDetail['addresses']['billing']['address_type'] ?? 'Home' }}</p>
                                    <p>{{ $orderDetail['addresses']['billing']['first_name'] }} {{ $orderDetail['addresses']['billing']['last_name'] }}</p>
                                    <p>{{ $orderDetail['addresses']['billing']['phone'] ?? '+65 9846 3689' }}</p>
                                    <p>{{ $orderDetail['addresses']['billing']['address_line_1'] }}</p>
                                    @if($orderDetail['addresses']['billing']['address_line_2'])
                                        <p>{{ $orderDetail['addresses']['billing']['address_line_2'] }}</p>
                                    @endif
                                    <p>{{ $orderDetail['addresses']['billing']['city'] }}, {{ $orderDetail['addresses']['billing']['postal_code'] }}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Shipping Address --}}
                        @if($orderDetail['addresses']['shipping'])
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Shipping Address</h4>
                                <div class="text-sm text-gray-600">
                                    <p>{{ $orderDetail['addresses']['shipping']['address_type'] ?? 'Home' }}</p>
                                    <p>{{ $orderDetail['addresses']['shipping']['first_name'] }} {{ $orderDetail['addresses']['shipping']['last_name'] }}</p>
                                    <p>{{ $orderDetail['addresses']['shipping']['phone'] ?? '+65 9846 3689' }}</p>
                                    <p>{{ $orderDetail['addresses']['shipping']['address_line_1'] }}</p>
                                    @if($orderDetail['addresses']['shipping']['address_line_2'])
                                        <p>{{ $orderDetail['addresses']['shipping']['address_line_2'] }}</p>
                                    @endif
                                    <p>{{ $orderDetail['addresses']['shipping']['city'] }}, {{ $orderDetail['addresses']['shipping']['postal_code'] }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Payment Information --}}
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Information</h3>
                        
                        {{-- Order Breakdown --}}
                        <div class="space-y-2 mb-4">
                            @foreach($orderDetail['items'] as $item)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ $item['name'] }}</span>
                                    <span class="text-gray-900">${{ number_format($item['total_price'], 2) }}</span>
                                </div>
                                @if(!empty($item['addons']))
                                    @foreach($item['addons'] as $addon)
                                        <div class="flex justify-between text-sm pl-4">
                                            <span class="text-gray-500">+ {{ $addon['name'] }}</span>
                                            <span class="text-gray-700">${{ number_format($addon['total_price'], 2) }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            @endforeach
                            
                            @if($orderDetail['order']['shipping_amount'] > 0)
                                <div class="flex justify-between text-sm pt-2 border-t">
                                    <span class="text-gray-600">Shipping</span>
                                    <span class="text-gray-900">${{ number_format($orderDetail['order']['shipping_amount'], 2) }}</span>
                                </div>
                            @endif
                            
                            @if($orderDetail['order']['tax_amount'] > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">
                                        GST{{ isset($orderDetail['order']['applied_tax_rate']) ? ' (' . number_format($orderDetail['order']['applied_tax_rate'], 0) . '%)' : '' }}
                                    </span>
                                    <span class="text-gray-900">${{ number_format($orderDetail['order']['tax_amount'], 2) }}</span>
                                </div>
                            @endif
                            
                            @if($orderDetail['order']['discount_amount'] > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Discount</span>
                                    <span class="text-green-600">-${{ number_format($orderDetail['order']['discount_amount'], 2) }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Total --}}
                        <div class="flex justify-between text-lg font-medium pt-2 border-t border-gray-300">
                            <span>Total Amount</span>
                            <span>${{ number_format($orderDetail['order']['total_amount'], 2) }}</span>
                        </div>

                        {{-- Invoice Information --}}
                        @if(!empty($orderDetail['payment']['history']) && !empty($orderDetail['payment']['history'][0]['invoice_url']))
                            @php 
                                $payment = $orderDetail['payment']['history'][0]; 
                                // Get the order number from the order detail
                                $orderNumber = $orderDetail['order']['order_number'] ?? '';
                            @endphp
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Invoice</h4>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                    @if($payment['invoice_url'])
                                        <a href="{{ $payment['invoice_url'] }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            View Invoice
                                        </a>
                                    @endif
                                    
                                    @if($payment['invoice_pdf_url'] && $orderNumber)
                                        <a href="{{ route('customer.invoice.download', ['orderNumber' => $orderNumber, 'paymentId' => $payment['id']]) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                            Download PDF
                                        </a>
                                    @endif
                                    
                                    @if($payment['invoice_number'])
                                        <div class="text-sm text-gray-500">
                                            Invoice #: {{ $payment['invoice_number'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Payment Method --}}
                        @if($orderDetail['payment']['display_text'])
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                    <span class="text-sm text-gray-900">{{ $orderDetail['payment']['display_text'] }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
</div>