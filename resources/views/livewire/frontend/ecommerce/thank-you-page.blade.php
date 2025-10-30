@if(!empty($order))
    @section('meta_tags')
        @php
            $metaTitle = 'Thank You for Your Order - ' . config('app.name');
            $metaDescription = 'Your order has been successfully placed. Thank you for shopping with us. View your order details and track your shipment.';
            // Safely access order number
            $canonicalUrl = route('shop.thank-you', ['orderNumber' => optional($order)->order_number ?: '']);
        @endphp
        
        <x-seo.meta-tags 
            :title="$metaTitle"
            :description="$metaDescription"
            :canonicalUrl="$canonicalUrl"
            :type="'website'"
        />
    @endsection
@endif

<x-ecommerce.page-wrapper heroImage="/images/e-comerce-1.png">    <x-slot name="header">
        <div class="flex flex-col">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Thank You!</h1>
            <p class="text-gray-600">Your order has been placed successfully</p>
        </div>
  </x-slot>
        @if($order)
            <!-- Header Section -->
            <div class="mb-8">
                <div class="bg-gradient-to-r from-white via-green-50 to-white border border-gray-200 rounded-xl p-6 shadow-lg">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="mb-4 sm:mb-0">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h1 class="text-3xl font-bold text-gray-900">Order Confirmed!</h1>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-2 text-sm text-gray-600 space-y-1 sm:space-y-0">
                                <span>Your order</span>
                                <span class="hidden sm:inline text-gray-400">|</span>
                                <span>Order ID: <span class="font-mono font-medium text-gray-900 bg-gray-100 px-2 py-1 rounded">{{ $order->order_number }}</span></span>
                                <span class="hidden sm:inline text-gray-400">|</span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    has been placed successfully
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left Column - Order Details -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Order Items -->
                    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-900">Total Products: {{ $order->items->count() }}</h2>
                        </div>
                        
                        <div class="space-y-4">
                            @foreach($order->items as $item)
                                <x-ecommerce.order-item-card 
                                    :item="$item" 
                                    :productImage="$itemImages[$item->id] ?? null"
                                    :addonImages="$addonImages"
                                />
                            @endforeach
                        </div>
                    </div>

                    <!-- Addresses Section - Styled to match checkout -->
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Delivery & Billing Address
                            </h3>
                        </div>
                        
                        <div class="p-6">
                            <div class="grid gap-4 md:grid-cols-2">
                                <!-- Shipping Address -->
                                @if($order->shippingAddress)
                                    <div class="border rounded-lg p-4 transition-all duration-200 border-gray-200 hover:border-gray-300">
                                        <div class="mb-3">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-medium text-gray-900 text-sm">{{ $order->shippingAddress->label ?? 'Address' }}</div>
                                                    <div class="text-gray-600 text-xs space-y-0.5 mt-1">
                                                        <div>{{ $order->shippingAddress->address_line1 }}</div>
                                                        @if($order->shippingAddress->address_line2)
                                                            <div>{{ $order->shippingAddress->address_line2 }}</div>
                                                        @endif
                                                        <div>{{ $order->shippingAddress->country }} {{ $order->shippingAddress->postal_code }}</div>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center text-green-600 text-xs font-medium ml-2">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                    Ship
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Billing Address -->
                                @if($order->billingAddress)
                                    <div class="border rounded-lg p-4 transition-all duration-200 border-gray-200 hover:border-gray-300">
                                        <div class="mb-3">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-medium text-gray-900 text-sm">{{ $order->billingAddress->label ?? 'Address' }}</div>
                                                    <div class="text-gray-600 text-xs space-y-0.5 mt-1">
                                                        <div>{{ $order->billingAddress->address_line1 }}</div>
                                                        @if($order->billingAddress->address_line2)
                                                            <div>{{ $order->billingAddress->address_line2 }}</div>
                                                        @endif
                                                        <div>{{ $order->billingAddress->country }} {{ $order->billingAddress->postal_code }}</div>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center text-blue-600 text-xs font-medium ml-2">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                    </svg>
                                                    Bill
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Savings Banner -->
                    @if($totalSavings > 0)
                        <div class="bg-gradient-to-r from-green-50 via-emerald-50 to-green-50 border-2 border-green-200 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 relative overflow-hidden">
                            <!-- Background decoration -->
                            <div class="absolute top-0 right-0 w-32 h-32 bg-green-100 rounded-full opacity-20 transform translate-x-16 -translate-y-8"></div>
                            <div class="absolute bottom-0 left-0 w-20 h-20 bg-emerald-100 rounded-full opacity-30 transform -translate-x-8 translate-y-8"></div>
                            
                            <div class="relative flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center shadow-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-lg font-bold text-green-800 flex items-center">
                                         You saved on this order!
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-bold text-green-800 mb-1">
                                        ${{ number_format($totalSavings, 2) }}
                                    </div>
                                    <div class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full">
                                        Total Savings
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Order Summary for Mobile (Hidden on Desktop) -->
                    <div class="lg:hidden bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium">${{ number_format($order->subtotal ?? 0, 2) }}</span>
                            </div>
                            
                            @php
                                $mobileCouponDiscount = floatval($order->discount_amount ?? 0);
                                $mobileTaxAmount = floatval($order->tax_amount ?? 0);
                                $mobileShippingAmount = floatval($order->shipping_amount ?? 0);
                                // Get the actual tax rate from the order model
                                $mobileGstRate = floatval($order->applied_tax_rate ?? 0);
                            @endphp
                            
                            @if($mobileCouponDiscount > 0)
                                <div class="flex justify-between text-green-700">
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        Coupon @if(!empty($order->coupon_code)) ({{ $order->coupon_code }}) @endif
                                    </span>
                                    <span class="font-medium">-${{ number_format($mobileCouponDiscount, 2) }}</span>
                                </div>
                            @endif
                            
                            @if($mobileTaxAmount > 0)
                                <div class="flex justify-between">
                                    <span class="flex items-center text-gray-600">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        GST Tax ({{ $mobileGstRate }}%)
                                    </span>
                                    <span class="font-medium">${{ number_format($mobileTaxAmount, 2) }}</span>
                                </div>
                            @endif
                            
                            @php
                                // Check if the order contains shippable products by checking if shipping address exists
                                $mobileHasShippableProducts = $order->shippingAddress !== null;
                            @endphp
                            
                            @if($mobileHasShippableProducts)
                                @if($mobileShippingAmount > 0)
                                    <div class="flex justify-between">
                                        <span class="flex items-center text-gray-600">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                            Shipping Charges
                                        </span>
                                        <span class="font-medium">${{ number_format($mobileShippingAmount, 2) }}</span>
                                    </div>
                                @elseif($mobileShippingAmount == 0)
                                    <div class="flex justify-between text-green-700">
                                        <span class="flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                            Shipping
                                        </span>
                                        <span class="font-medium">Free</span>
                                    </div>
                                @endif
                            @endif
                            
                            <div class="border-t pt-3 flex justify-between text-base">
                                <span class="font-semibold text-gray-900">Grand Total</span>
                                <span class="font-bold text-lg text-gray-900">${{ number_format($order->total_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    @php
                        // Safely compute invoice availability once
                        $payment = $order && $order->payments->isNotEmpty() ? $order->payments->first() : null;
                        $hasInvoice = $payment && !empty($payment->invoice_url);
                    @endphp

                    <!-- Invoice Information for Mobile -->
                    @if($hasInvoice)
                        <x-ecommerce.invoice-section :payment="$payment" :order="$order" wrapperClass="mt-6 pt-6 border-t border-gray-200 lg:hidden" />
                    @endif
                </div>

                <!-- Right Column - Order Actions (Desktop Only) -->
                <div class="hidden lg:block lg:col-span-4">
                    <x-ecommerce.order-actions :order="$order" :taxRate="$taxRate" />
                    
                    <!-- Invoice Information -->
                    @if($hasInvoice)
                        <x-ecommerce.invoice-section :payment="$payment" :order="$order" wrapperClass="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden" />
                    @endif
                </div>
            </div>

        @else
            <!-- Error State -->
            <div class="max-w-md mx-auto">
                <div class="bg-white border border-gray-200 rounded-xl p-8 text-center shadow-sm">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Order Not Found</h3>
                    <p class="text-gray-600 mb-4">We couldn't find this order. Please check the link or visit our shop.</p>
                    <a 
                        href="{{ route('shop.list') }}" 
                        class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                    >
                        Go to Shop
                    </a>
                </div>
            </div>
        @endif
</x-ecommerce.page-wrapper>