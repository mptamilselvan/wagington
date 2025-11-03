@section('meta_tags')
    @php
        $metaTitle = 'Shopping Cart - ' . config('app.name');
        $metaDescription = 'Review your shopping cart and proceed to checkout. Manage your selected items before completing your purchase.';
        $canonicalUrl = route('shop.cart');
    @endphp
    
    <x-seo.meta-tags 
        :title="$metaTitle"
        :description="$metaDescription"
        :canonicalUrl="$canonicalUrl"
        :type="'website'"
    />
@endsection

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-white">
    <h1 class="text-2xl font-semibold mb-6">My Cart</h1>

    <div class="space-y-4">
        @forelse($cart['items'] as $item)
            <div wire:key="cart-item-{{ $item['id'] }}" class="border rounded p-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-16 h-16 flex items-center justify-center bg-gray-50 border rounded overflow-hidden">
                            @if(!empty($item['image_url']))
                                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] ?? 'Item' }}" class="object-cover w-full h-full" />
                            @else
                                <div class="text-[11px] text-gray-400">No image</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium">{{ $item['name'] }}</div>
                            <div class="text-xs text-gray-500">{{ $item['variant_display_name'] }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @php
                            $maxQty = 999999;
                            if (!empty($item['track_inventory'])) {
                                // Get the max quantity per order setting (if set)
                                $maxQtyPerOrder = (int)($item['max_quantity_per_order'] ?? 0);
                                
                                // If max_quantity_per_order is set and greater than 0, use it as the primary limit
                                if ($maxQtyPerOrder > 0) {
                                    // If backorders are allowed, we can go up to max_quantity_per_order
                                    if ($item['allow_backorders']) {
                                        $maxQty = $maxQtyPerOrder;
                                    } else {
                                        // If backorders are not allowed, we're limited by the lesser of:
                                        // 1. max_quantity_per_order
                                        // 2. available stock
                                        $available = (int)($item['available'] ?? 0);
                                        $maxQty = min($maxQtyPerOrder, $available);
                                        // Ensure we have at least 1 if we have any available stock
                                        $maxQty = max(1, $maxQty);
                                    }
                                } else {
                                    // No max_quantity_per_order set, use original logic
                                    $maxQty = (int)($item['allow_backorders'] ? 999999 : ($item['available'] ?? 0));
                                    $maxQty = max(1, $maxQty);
                                }
                            }
                        @endphp
                        <input type="number" min="1" max="{{ $maxQty }}" wire:model.live="cart.items.{{ $loop->index }}.qty" @if(($item['catalog_id'] ?? null) == 2) disabled @endif class="w-24 text-center border rounded @if(($item['catalog_id'] ?? null) == 2) bg-gray-100 cursor-not-allowed @endif" title="@if(($item['catalog_id'] ?? null) == 2) Quantity is fixed for room bookings @endif" />
                        <div class="w-24 text-right">${{ number_format($item['subtotal'], 2) }}</div>
                        @php 
                            $isLow = ($item['available'] ?? 0) > 0 && ($item['available'] ?? 0) <= ($item['min_quantity_alert'] ?? 0);
                            $maxQtyPerOrder = (int)($item['max_quantity_per_order'] ?? 0);
                            $currentQty = (int)($item['qty'] ?? 0);
                        @endphp
                        @if($maxQtyPerOrder > 0 && $currentQty > $maxQtyPerOrder)
                            <div class="text-xs text-red-600 font-medium">Maximum quantity allowed is {{ $maxQtyPerOrder }}</div>
                        @else
                            <div class="w-40 text-right text-xs {{ ($item['available'] ?? 0) > 0 ? ($isLow ? 'text-yellow-600' : 'text-green-600') : 'text-red-600' }}" @if($isLow) title="Only {{ $item['available'] }} left" @endif>
                                {{ $item['availability_label'] ?? '' }}
                                @php
                                    $bq = (int)($item['backorder_qty'] ?? 0);
                                @endphp
                                @if($bq > 0)
                                    <div class="mt-1 text-[11px] text-gray-500">
                                        Backorder {{ $bq }}
                                    </div>
                                @endif
                            </div>
                        @endif
                        <button wire:click="remove('{{ $item['id'] }}')" class="text-red-600 text-sm">Remove</button>
                    </div>
                </div>
                @php
                    $hasAddons = !empty($item['addons']) && is_array($item['addons']) && count($item['addons']) > 0;
                    $hasPets = !empty($item['pets']) && is_array($item['pets']) && count($item['pets']) > 0;
                @endphp
                @if($hasAddons)
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="text-xs font-medium text-gray-600 mb-2">Add-ons:</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($item['addons'] as $addon)
                                <div class="px-2 py-1 bg-blue-50 border border-blue-200 rounded text-xs">
                                    <span class="font-medium text-blue-800">
                                        {{ $addon['name'] ?? ($addon['addon_name'] ?? 'Add-on') }}
                                        @if(!empty($addon['variant_name']))
                                            - {{ $addon['variant_name'] }}
                                        @endif
                                    </span>
                                    @if(isset($addon['qty']) && $addon['qty'] > 1)
                                        <span class="text-blue-600">(×{{ $addon['qty'] }})</span>
                                    @endif
                                    @if(isset($addon['price']) || isset($addon['unit_price']))
                                        <span class="text-blue-600 ml-1">
                                            ${{ number_format(($addon['price'] ?? $addon['unit_price'] ?? 0) * ($addon['qty'] ?? 1), 2) }}
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if($hasPets)
                    <div class="mt-3 @if(!$hasAddons) pt-3 border-t border-gray-200 @endif">
                        <div class="text-xs font-medium text-gray-600 mb-2">Pet Profiles:</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($item['pets'] as $pet)
                                <div class="px-2 py-1 bg-emerald-50 border border-emerald-200 rounded text-xs text-emerald-800">
                                    <span class="font-medium">{{ $pet['pet_name'] ?? 'Pet' }}</span>
                                    @if(!empty($pet['pet_size_name']))
                                        <span class="ml-1">({{ $pet['pet_size_name'] }})</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-gray-500">Your cart is empty.</div>
        @endforelse
    </div>

    @php
        $region = 'default';
        if(auth()->check()){
            $addr = optional(auth()->user()->shipping_address);
            if($addr && $addr->country){ $region = $addr->country; }
        }
        $shipping = app(\App\Services\ShippingService::class)->calculate($region, $cart);
        $subtotal = $cart['total'] ?? 0;
        // Compute discount total defensively: prefer calculated_discount, then applied_amount, else 0
        $discountTotal = 0;
        if (!empty($appliedCoupons) && is_array($appliedCoupons)) {
            $discountTotal = array_sum(array_map(function($c){
                return (float)($c['calculated_discount'] ?? $c['applied_amount'] ?? 0);
            }, $appliedCoupons));
        }
        
        // Calculate tax using the tax service (guarded)
        $taxService = app(\App\Services\TaxService::class);
        $taxableAmount = max(0, $subtotal - $discountTotal);

        // Defaults
        $taxAmount = 0.0;
        $taxRate = 0.0;

        try {
            $taxResult = $taxService->calculateTax($taxableAmount);

            // Validate shape and numeric values before using
            if (is_array($taxResult)
                && array_key_exists('amount', $taxResult)
                && array_key_exists('rate', $taxResult)
                && is_numeric($taxResult['amount'])
                && is_numeric($taxResult['rate'])) {

                $taxAmount = (float) $taxResult['amount'];
                $taxRate = (float) $taxResult['rate'];
            } else {
                \Illuminate\Support\Facades\Log::error('TaxService::calculateTax returned invalid structure', [
                    'taxResult' => $taxResult,
                    'taxableAmount' => $taxableAmount,
                    'cart' => $cart ?? null,
                    'user_id' => optional(auth()->user())->id,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('TaxService::calculateTax threw exception', [
                'exception' => $e,
                'taxableAmount' => $taxableAmount,
                'cart' => $cart ?? null,
                'user_id' => optional(auth()->user())->id,
            ]);
            // keep defaults (0.0) so page remains functional
        }

        // Ensure numeric, non-negative values before computing grand total
        $taxableAmount = max(0, $taxableAmount);
        $taxAmount = max(0, (float) $taxAmount);
        $shipping = max(0, (float) $shipping);

        $grand = $taxableAmount + $taxAmount + $shipping;
    @endphp
    
    <div class="mt-6 space-y-1">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">Subtotal</div>
            <div class="text-sm font-medium">${{ number_format($subtotal, 2) }}</div>
        </div>
        
        <!-- Applied Coupons -->
        @if($discountTotal > 0)
            @foreach($appliedCoupons as $coupon)
                <div class="flex items-center justify-between text-green-600">
                    <div class="text-sm flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Coupon ({{ $coupon['voucher_code'] }})
                    </div>
                    <div class="text-sm font-medium">-${{ number_format($coupon['calculated_discount'] ?? $coupon['applied_amount'] ?? 0, 2) }}</div>
                </div>
            @endforeach
        @endif
        
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">Shipping</div>
            <div class="text-sm font-medium">${{ number_format($shipping, 2) }}</div>
        </div>
        
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">GST ({{ number_format($taxRate, 2) }}%)</div>
            <div class="text-sm font-medium">${{ number_format($taxAmount, 2) }}</div>
        </div>
        
        <div class="flex items-center justify-between pt-2 border-t">
            <div class="text-lg">Total</div>
            <div class="text-xl font-bold">${{ number_format($grand, 2) }}</div>
        </div>
    </div>

    <div class="mt-6 flex justify-end" x-data="{ showAuthPrompt:false }">
        @if($this->hasQuantityErrors())
            <div class="text-red-600 text-sm mb-2">Please fix quantity errors before proceeding to checkout.</div>
        @endif
        @auth
            <button wire:click="saveAndProceed" @disabled($this->hasQuantityErrors()) class="px-5 py-3 rounded {{ $this->hasQuantityErrors() ? 'bg-gray-400 text-gray-200 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700' }}">Proceed to checkout</button>
        @else
            <button type="button" @click="showAuthPrompt=true" @disabled($this->hasQuantityErrors()) class="px-5 py-3 rounded {{ $this->hasQuantityErrors() ? 'bg-gray-400 text-gray-200 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700' }}">Proceed to checkout</button>

            <!-- Guest Auth Prompt -->
            <div x-cloak x-show="showAuthPrompt" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/50" @click="showAuthPrompt=false"></div>
                <div class="relative z-10 w-[92%] max-w-md bg-white rounded-xl shadow-xl p-6">
                    <h3 class="text-lg font-semibold mb-2">Create an account or login</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        You're not registered or logged in. To continue to checkout, please create an account or login.
                        Your items will remain in your cart.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('customer.register.form') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">Create account</a>
                        <a href="{{ route('customer.login') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-gray-300 text-gray-800 font-medium hover:bg-gray-50">Login</a>
                    </div>
                </div>
            </div>
        @endauth
    </div>
</div>