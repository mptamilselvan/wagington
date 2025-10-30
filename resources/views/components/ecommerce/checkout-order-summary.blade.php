@props([
    'cart' => [],
    'checkoutSummary' => [
        'summary' => [
            'subtotal' => 0,
            'discount_total' => 0,
            'total' => 0,
        ],
        'shipping_amount' => 0,
        'tax' => [
            'amount' => 0,
            'rate' => 0,
        ],
        'applied_vouchers' => [],
        'errors' => [],
        'stackability_message' => null,
    ],
    'shippingAmount' => 0,
    'taxAmount' => 0,
    'taxRate' => 0,
    'appliedCoupons' => [],
    'totalCouponDiscount' => 0,
    'currentCouponInput' => '',
    'couponMessage' => '',
    'maxCoupons' => 5,
    'selectedShippingAddressId' => null,
    'selectedBillingAddressId' => null,
    'billingAddressSameAsShipping' => true,
    'selectedPaymentMethodId' => null,
    'totalSaved' => 0,
    'loading' => false,
    'requiresShipping' => true
])

@php
use App\Helpers\CurrencyHelper;
@endphp

@php
    $summary = $checkoutSummary['summary'] ?? [];
    $serviceSubtotal = (float)($summary['subtotal'] ?? 0);
    $serviceDiscount = (float)($summary['discount_total'] ?? 0);
    $serviceTotal = (float)($summary['total'] ?? 0);
    $serviceTaxAmount = (float)($checkoutSummary['tax']['amount'] ?? 0);
    $serviceTaxRate = (float)($checkoutSummary['tax']['rate'] ?? 0);
    $serviceShipping = (float)($checkoutSummary['shipping_amount'] ?? 0);
    
    // Show discounted subtotal when coupons are applied
    $displaySubtotal = max(0, $serviceSubtotal - $serviceDiscount);

    $itemTotals = [];
    foreach($cart['items'] ?? [] as $item) {
        $label = trim(($item['name'] ?? '') . (!empty($item['variant_display_name']) ? ' (' . $item['variant_display_name'] . ')' : '')) . ' × ' . ($item['qty'] ?? 1);
        $lineTotal = (float)($item['total'] ?? $item['subtotal'] ?? 0);
        // Compute discounted total for the item, prefer per-item discounted_total then fallbacks
        $itemDiscounted = isset($item['discounted_total']) ? (float)$item['discounted_total'] : null;
        if ($itemDiscounted === null) {
            // fall back to any totals/subtotals only when discounted_total is not explicitly provided
            $itemDiscounted = (float)($item['total'] ?? $item['subtotal'] ?? 0);
        }

        if (!empty($item['addons']) && is_array($item['addons'])) {
            foreach ($item['addons'] as $addon) {
                $lineTotal += (float)($addon['total'] ?? $addon['subtotal'] ?? 0);
                // Sum addon discounted_total falling back to total/subtotal
                $addonDiscount = isset($addon['discounted_total']) ? (float)$addon['discounted_total'] : (float)($addon['total'] ?? $addon['subtotal'] ?? 0);
                $itemDiscounted = ($itemDiscounted ?? 0) + $addonDiscount;
            }
        }

        $row = [
            'label' => $label,
            'total' => $lineTotal,
        ];

        // Only include discounted_total when it's explicitly present/derived and differs from the line total
        if ($itemDiscounted !== null && (float)$itemDiscounted !== (float)$lineTotal) {
            $row['discounted_total'] = $itemDiscounted;
        }

        $itemTotals[] = $row;
    }

    $displayShipping = $requiresShipping ? $serviceShipping : 0;
    $grandTotal = $serviceTotal;
@endphp

<div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sticky top-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        Order Summary
    </h2>

    {{-- Items List --}}
    <div class="space-y-3 pb-4 border-b border-gray-200">
        @foreach($itemTotals as $row)
            <div class="flex items-center justify-between text-sm">
                <div class="text-gray-700 flex-1 mr-2">{{ $row['label'] }}</div>
                <div class="text-gray-900 font-medium">S${{ number_format($row['total'], 2) }}</div>
            </div>
            
            {{-- Show discounted price when an item actually differs after discount --}}
            @if(isset($row['discounted_total']))
                <div class="flex items-center justify-between text-sm pl-4">
                    <div class="text-gray-500 flex-1 mr-2">After discount</div>
                    <div class="text-gray-700 font-medium">S${{ number_format($row['discounted_total'], 2) }}</div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Price Breakdown --}}
    <div class="py-4 space-y-3 text-sm border-b border-gray-200">
        <div class="flex items-center justify-between">
            <span class="text-gray-600">Original Subtotal</span>
            <span class="text-gray-900 font-medium">{{ CurrencyHelper::format($serviceSubtotal) }}</span>
        </div>
        
        @if($serviceDiscount > 0)
            <div class="flex items-center justify-between text-green-700">
                <span class="text-green-700">Total Discount</span>
                <span class="text-green-700">-{{ CurrencyHelper::format($serviceDiscount) }}</span>
            </div>
            
            <div class="flex items-center justify-between font-medium">
                <span class="text-gray-600">Final Subtotal</span>
                <span class="text-gray-900">{{ CurrencyHelper::format($displaySubtotal) }}</span>
            </div>
        @endif
        
        @if($requiresShipping)
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Shipping</span>
                <span class="text-gray-900 font-medium">{{ CurrencyHelper::format($displayShipping) }}</span>
            </div>
        @endif
        <div class="flex items-center justify-between">
            <span class="text-gray-600">GST ({{ number_format($serviceTaxRate, 2) }}%)</span>
            <span class="text-gray-900 font-medium">S${{ number_format($serviceTaxAmount, 2) }}</span>
        </div>

        {{-- Multi-Coupon Section --}}
        @if(!empty($appliedCoupons))
            <div class="space-y-2">
                <div class="text-xs text-gray-600 font-medium">Applied Coupons ({{ count($appliedCoupons) }}/{{ $maxCoupons }})</div>
                @foreach($appliedCoupons as $coupon)
                    <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-green-800">{{ $coupon['code'] }}</div>
                            <div class="text-xs text-green-600">
                                @if($coupon['type'] === 'percentage')
                                    {{ $coupon['value'] }}% off
                                @else
                                    S${{ number_format($coupon['value'], 2) }} off
                                @endif
                            </div>
                        </div>
                        <div class="text-right mr-3">
                            <div class="text-sm font-medium text-green-700">-S${{ number_format($coupon['discount'], 2) }}</div>
                        </div>
                        <button 
                            type="button" 
                            class="text-red-500 hover:text-red-700 p-1"
                            wire:click="removeCoupon('{{ $coupon['code'] }}')"
                            title="Remove coupon"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                @endforeach
                
                {{-- Total Discount Summary --}}
            </div>
        @endif

        {{-- Add Coupon Input (always visible if under limit) --}}
        @if(count($appliedCoupons) < $maxCoupons)
            <div class="mt-3">
                <label class="block text-xs text-gray-600 mb-1">
                    @if(empty($appliedCoupons))
                        Have a coupon?
                    @else
                        Add another coupon ({{ count($appliedCoupons) }}/{{ $maxCoupons }})
                    @endif
                </label>
                <div class="flex gap-2">
                    <input 
                        type="text" 
                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                        placeholder="Enter code" 
                        wire:model.defer="currentCouponInput"
                        wire:keydown.enter="addCoupon"
                    >
                    <button 
                        type="button" 
                        class="px-3 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900 transition-colors duration-200" 
                        wire:click="addCoupon"
                    >
                        Apply
                    </button>
                </div>
            </div>
        @endif

        {{-- Coupon Message --}}
        @if(!empty($couponMessage))
            <div class="mt-2 text-xs">
                @php
                    // Split the message into parts
                    $parts = preg_split('/(?<=[.!?])\s+/', e($couponMessage));
                    $processedParts = [];
                    
                    foreach ($parts as $part) {
                        $part = trim($part);
                        if (empty($part)) continue;
                        
                        // Color applied vouchers in green
                        if (strpos($part, 'so it remains applied') !== false) {
                            $processedParts[] = '<span class="text-green-600 font-medium">' . $part . '</span>';
                        }
                        // Color removed vouchers in red
                        elseif (strpos($part, 'was removed') !== false) {
                            $processedParts[] = '<span class="text-red-600 font-medium">' . $part . '</span>';
                        }
                        // Color "Already applied", conflict, warning, and invalid promo code messages in red
                        elseif (stripos($part, 'already applied') !== false || stripos($part, 'conflict') !== false || stripos($part, 'warning') !== false || stripos($part, 'invalid promo code') !== false) {
                            $processedParts[] = '<span class="text-red-600 font-medium">' . $part . '</span>';
                        }
                        else {
                            $processedParts[] = $part;
                        }
                    }
                    
                    $processedMessage = implode(' ', $processedParts);
                @endphp
                {!! $processedMessage !!}
            </div>
        @endif
    </div>

    {{-- Grand Total --}}
    <div class="py-4 border-b border-gray-200">
        <div class="flex items-center justify-between text-lg font-semibold text-gray-900">
            <span>Grand Total</span>
            <span>S${{ number_format($grandTotal, 2) }}</span>
        </div>
        @if($serviceDiscount > 0)
            <div class="text-xs text-gray-500 mt-1">
                Includes S${{ number_format($serviceDiscount, 2) }} in coupon discounts
            </div>
        @endif
    </div>

    {{-- Confirm Order Button --}}
    <div class="pt-4">
        <button
            type="button"
            class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-600"
            wire:click="placeOrder"
            wire:loading.attr="disabled"
            wire:loading.class="cursor-wait"
            @if(!$selectedBillingAddressId || ($requiresShipping && !$selectedShippingAddressId) || !$selectedPaymentMethodId || $loading) disabled @endif
        >
            <span class="flex items-center justify-center" wire:loading.remove wire:target="placeOrder">
                @if($loading)
                    <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                @else
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Place Order
                @endif
            </span>
            <span class="flex items-center justify-center" wire:loading wire:target="placeOrder">
                <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            </span>
        </button>
        
        {{-- Button State Indicator --}}
        <div class="mt-2 text-center">
            @if($loading)
                <div class="text-xs text-blue-600 font-medium">
                    Processing your order...
                </div>
            @elseif(!$selectedBillingAddressId || ($requiresShipping && !$selectedShippingAddressId) || !$selectedPaymentMethodId)
                <div class="text-xs text-gray-500">
                    Complete address and payment selection to continue
                </div>
            @else
    
            @endif
        </div>
    </div>

    {{-- Savings Display --}}
    @if($totalSaved > 0)
        <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-center">
            <div class="text-sm font-medium text-green-800">
                 You will save S$ {{ number_format($totalSaved, 2) }} on this order!
            </div>
        </div>
    @endif

    {{-- Security & Trust Indicators --}}
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex items-center justify-center space-x-4 text-xs text-gray-500">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <!-- Heroicons outline: lock-closed (split into shackle and body paths) -->
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 1 1 8 0v4"></path>
                </svg>
                Secure Payment
            </div>
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                SSL Encrypted
            </div>
        </div>
    </div>
</div>