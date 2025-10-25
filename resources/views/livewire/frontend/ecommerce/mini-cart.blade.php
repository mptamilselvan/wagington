<div x-data="{ open:false }" x-on:cart-updated.window="open=true; $wire.refreshCart();" x-cloak>
    <!-- Backdrop -->
    <div x-show="open" class="fixed inset-0 z-[60] bg-black/40" @click="open=false"></div>

    <!-- Panel -->
    <div x-show="open" class="fixed right-0 top-0 bottom-0 z-[61] w-full max-w-md bg-white shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-4 bg-white">
            <h2 class="text-lg font-semibold">My Cart</h2>
            <button @click="open=false" class="text-gray-500">✕</button>
        </div>

        <div class="flex-1 overflow-y-auto bg-white p-4 space-y-3">
            @forelse(($cart['items'] ?? []) as $it)
                @php
                    $itemName = $it['name'] ?? $it['product_name'] ?? null;
                    $itemName = is_string($itemName) && trim($itemName) !== '' ? trim($itemName) : __('Item');
                @endphp
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="flex gap-4">
                        <!-- Product Image -->
                        <div class="flex-shrink-0">
                            @if(!empty($it['image_url']))
                                <img src="{{ $it['image_url'] }}" alt="{{ $itemName }}" class="w-16 h-16 object-cover rounded-full border border-gray-200">
                            @else
                                <div class="w-16 h-16 bg-gray-100 rounded-full border border-gray-200 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Product Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-medium text-gray-900 truncate">{{ $itemName }}</h3>
                                    @if(!empty($it['variant_display_name']))
                                        <p class="text-sm text-gray-500 mt-1">{{ $it['variant_display_name'] }}</p>
                                    @endif
                                    @php 
                                        $isLow = ($it['available'] ?? 0) > 0 && ($it['available'] ?? 0) <= ($it['min_quantity_alert'] ?? 0);
                                        $bq = (int)($it['backorder_qty'] ?? 0);
                                    @endphp
                                    @if(!empty($it['availability_label']) || $bq > 0)
                                        <div class="mt-2 text-sm">
                                            @if(!empty($it['availability_label']))
                                                <span class="{{ ($it['available'] ?? 0) > 0 ? ($isLow ? 'text-yellow-600' : 'text-green-600') : 'text-red-600' }}" @if($isLow) title="Only {{ $it['available'] }} left" @endif>
                                                    {{ $it['availability_label'] }}
                                                </span>
                                            @endif
                                            @if($bq > 0)
                                                <span class="text-gray-500">
                                                    @if(!empty($it['availability_label'])) • @endif
                                                    Backorder {{ $bq }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <button wire:click="remove('{{ $it['id'] }}')" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Remove</button>
                                    <div class="text-lg font-semibold text-gray-900">S$ {{ number_format($it['subtotal'], 2) }}</div>
                                </div>
                            </div>
                            
                            <!-- Quantity Controls -->
                            <div class="flex items-center gap-3 mt-4">
                                @php
                                    $maxQty = 999999;
                                    if (!empty($it['track_inventory'])) {
                                        $maxQty = (int)($it['allow_backorders'] ? ($it['max_quantity_per_order'] ?: 999999) : ($it['available'] ?? 0));
                                        $maxQty = max(1, $maxQty);
                                    }
                                @endphp
                                <button class="flex items-center justify-center w-8 h-8 border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" wire:click="decrement('{{ $it['id'] }}')">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                    </svg>
                                </button>
                                <div class="flex items-center justify-center w-12 h-8 text-base font-medium text-gray-900 bg-gray-50 rounded-lg border border-gray-200">{{ $it['qty'] }}</div>
                                <button class="flex items-center justify-center w-8 h-8 border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" wire:click="increment('{{ $it['id'] }}')" @disabled($it['qty'] >= $maxQty)>
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            
                            @if(!empty($it['addons']))
                                <div class="mt-4 pt-3 border-t border-gray-100">
                                    <div class="space-y-2">
                                        @foreach($it['addons'] as $ad)
                                            @php
                                                $addonName = $ad['name'] ?? $ad['product_name'] ?? null;
                                                $addonName = is_string($addonName) && trim($addonName) !== '' ? trim($addonName) : __('Addon');
                                            @endphp
                                            <div class="flex items-center justify-between text-sm">
                                                <div class="flex items-center gap-2">
                                                    @if(!empty($ad['is_required']) && $ad['is_required'])
                                                        <span class="px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-red-600 bg-red-50 rounded">Required</span>
                                                    @endif
                                                    <span class="text-gray-700">{{ $addonName }}</span>
                                                    @if(!empty($ad['variant_name']))
                                                        <span class="text-gray-500">• {{ $ad['variant_name'] }}</span>
                                                    @endif
                                                    <span class="text-gray-500">× {{ $ad['qty'] }}</span>
                                                </div>
                                                <div class="font-medium text-gray-900">S$ {{ number_format($ad['subtotal'] ?? 0, 2) }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-sm text-gray-500">Your cart is empty.</div>
            @endforelse
        </div>

        <div class="p-4 border-t bg-white space-y-3">
            <!-- Compact per-item totals only -->
            @php
                $rows = [];
                foreach (($cart['items'] ?? []) as $it) {
                    $labelName = $it['name'] ?? $it['product_name'] ?? null;
                    $labelName = is_string($labelName) && trim($labelName) !== '' ? trim($labelName) : __('Item');
                    $variantSuffix = !empty($it['variant_display_name']) ? ' (' . $it['variant_display_name'] . ')' : '';
                    $label = $labelName . $variantSuffix . ' × ' . ($it['qty'] ?? 1);
                    $rows[] = ['label' => $label, 'total' => (float)($it['subtotal'] ?? 0)];
                }
                $grand = (float)($cart['total'] ?? 0);
            @endphp
            <div class="text-sm text-gray-700">Order Summary</div>
            <div class="space-y-1 text-sm">
                @foreach($rows as $r)
                    <div class="flex justify-between"><span>{{ $r['label'] }}</span><span>S$ {{ number_format($r['total'], 2) }}</span></div>
                @endforeach
            </div>
            <div class="flex justify-between text-sm pt-2 border-t">
                <div class="font-medium">Total</div>
                <div class="font-semibold">S$ {{ number_format($grand, 2) }}</div>
            </div>
            @php($reserved = session('cart.reserved_until'))
            @if($reserved)
                <div class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded p-2">
                    Items reserved until {{ \Carbon\Carbon::parse($reserved)->timezone(config('app.timezone'))->format('H:i') }}.
                </div>
            @endif
            <div class="text-[11px] text-gray-600">
                In-stock items ship now; backordered items ship when available.
            </div>
            <div class="flex gap-3" x-data="{ showAuthPrompt: false }" x-on:show-auth-prompt.window="showAuthPrompt = true">
                @auth
                    <a href="{{ route('shop.cart') }}" class="flex-1 px-4 py-2 border rounded text-center">View cart</a>
                    <button wire:click="proceed" class="flex-1 px-4 py-2 rounded bg-blue-600 text-white text-center">Proceed to pay</button>
                @else
                    <button type="button" @click="showAuthPrompt = true" class="flex-1 px-4 py-2 border rounded text-center">View cart</button>
                    <button type="button" wire:click="guestProceed" class="flex-1 px-4 py-2 rounded bg-blue-600 text-white text-center">Proceed to pay</button>

                    <!-- Guest Auth Prompt -->
                    <div x-cloak x-show="showAuthPrompt" class="fixed inset-0 z-[70] flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/50" @click="showAuthPrompt=false"></div>
                        <div class="relative z-[71] w-[92%] max-w-md bg-white rounded-xl shadow-xl p-6">
                            <h3 class="text-lg font-semibold mb-2">Create an account or login</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                You’re not registered or logged in. To continue to checkout, please create an account or login.
                                Your items will remain in your cart.
                            </p>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <a href="{{ route('customer.register.form') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">Create account</a>
                                <a href="{{ route('customer.login') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-gray-300 text-gray-800 font-medium hover:bg-gray-50">Login</a>
                            </div>
                            <div class="mt-3 text-xs text-gray-500">If you choose not to register or login now, you can continue browsing and come back later.</div>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</div>