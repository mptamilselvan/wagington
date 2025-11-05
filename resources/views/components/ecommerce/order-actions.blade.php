@props(['order', 'taxRate' => null, 'roomBookings' => null])

<div class="bg-white border border-gray-200 rounded-xl p-6 sticky top-6">
    <!-- Order Summary Header -->
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900">Success! Your Order is Confirmed</h3>
        <p class="text-sm text-gray-600 mt-1">Thank you for your order</p>
    </div>

    <!-- Order Details Summary -->
    <div class="space-y-4 mb-6">
        <p class="text-sm font-medium text-gray-700 mb-3">Order Details</p>

        <!-- Detailed Bill Breakdown -->
        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Subtotal</span>
                <span class="font-medium">${{ number_format($order->subtotal ?? 0, 2) }}</span>
            </div>

            @php
                // Use the correct field name from the Order model
                $couponDiscount = floatval($order->coupon_discount_amount ?? 0);
                $taxAmount = floatval($order->tax_amount ?? 0);
                $shippingAmount = floatval($order->shipping_amount ?? 0);
                // Use passed tax rate, fallback to order's applied_tax_rate, then to 18% if not available
                $gstRate = $taxRate ?? floatval($order->applied_tax_rate ?? 18);
            @endphp

            {{-- Display all applied coupons in priority order --}}
            @if ($order->appliedVouchers && $order->appliedVouchers->count() > 0)
                @foreach ($order->appliedVouchers->sortBy('stack_order') as $voucher)
                    <div class="flex justify-between text-green-700">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            Coupon ({{ $voucher->voucher_code }})
                        </span>
                        <span class="font-medium">-${{ number_format($voucher->calculated_discount, 2) }}</span>
                    </div>
                @endforeach
            @elseif($couponDiscount > 0)
                {{-- Fallback for single coupon or when appliedVouchers relationship is not loaded --}}
                <div class="flex justify-between text-green-700">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Coupon
                    </span>
                    <span class="font-medium">-${{ number_format($couponDiscount, 2) }}</span>
                </div>
            @endif

            @if (($order->tax_amount ?? 0) > 0)
                <div class="flex justify-between">
                    <span class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        GST Tax ({{ $gstRate }}%)
                    </span>
                    <span class="font-medium">${{ number_format($order->tax_amount ?? 0, 2) }}</span>
                </div>
            @endif

            @php
                // Check if the order contains shippable products by checking if shipping address exists
                // If no shipping address, it means all products were non-shippable
                $hasShippableProducts = $order->shippingAddress !== null;
            @endphp

            @if ($hasShippableProducts)
                @if ($shippingAmount > 0)
                    <div class="flex justify-between">
                        <span class="flex items-center text-gray-600">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Shipping Charges
                        </span>
                        <span class="font-medium">${{ number_format($shippingAmount, 2) }}</span>
                    </div>
                @elseif($shippingAmount == 0)
                    <div class="flex justify-between text-green-700">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Shipping
                        </span>
                        <span class="font-medium">Free</span>
                    </div>
                @endif
            @endif

            <div class="border-t pt-3 flex justify-between text-base">
                <span class="font-semibold text-gray-900">Grand Total</span>
                <span class="font-bold text-lg text-gray-900">${{ number_format($order->total_amount ?? 0, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Payment Summary -->
    <div class="border-t border-gray-200 pt-4">
        <div class="text-center mb-3">
            <p class="text-xl font-bold text-gray-900">${{ number_format($order->total_amount, 2) }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-2">
            <a href="{{ route('customer.order-history') }}"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors text-sm inline-flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                    </path>
                </svg>
                My Order History
            </a>
            <a href="/shop"
                class="w-full bg-gray-900 hover:bg-gray-800 text-white font-medium py-2.5 px-4 rounded-lg transition-colors text-sm inline-flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 1.5M7 13l1.5-1.5M20 13v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6" />
                </svg>
                Continue Shopping
            </a>
        </div>
    </div>

    <!-- Order Information -->
    <div class="mt-6 text-xs text-gray-500 text-center">
        <p>Order confirmation and tracking details have been sent to your email.</p>
        <p class="mt-2">Thank you for choosing The Wagington! Your furry friends are going to love their new items.
        </p>
    </div>
</div>
