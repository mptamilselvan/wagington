<div x-data="{ open:false }" x-on:open-booking.window="open=true; $wire.refreshBooking(); $wire.setRoom($event.detail.roomId);" x-cloak>
    <!-- Backdrop -->
    <div x-show="open" class="fixed inset-0 z-[60] bg-black/40" @click="open=false"></div>

    <!-- Panel -->
    <div x-show="open" class="fixed right-0 top-0 bottom-0 z-[61] w-full max-w-xl bg-white shadow-xl flex flex-col p-[20px]">
        <div class="flex items-center justify-between p-4 bg-white">
            <h2 class="text-lg font-semibold">Booking</h2>
            <button @click="open=false" class="text-gray-500">✕</button>
        </div>

        <!-- Pet Selection Section -->
        <div class="px-4 pb-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Select pet profile.</h3>
                <div class="flex items-center gap-3">
                    @if(count($selectedPets) > 0)
                        <div class="bg-yellow-100 text-orange-600 px-3 py-1 rounded-full text-sm font-medium">
                            {{ count($selectedPets) }} pet{{ count($selectedPets) > 1 ? 's' : '' }} selected
                        </div>
                    @endif
                    <div class="flex gap-1">
                        <button 
                            wire:click="previousPage" 
                            @disabled($currentPage <= 0)
                            class="w-8 h-8 rounded-lg border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            ‹
                        </button>
                        <button 
                            wire:click="nextPage" 
                            @disabled(!$hasMorePets)
                            class="w-8 h-8 rounded-lg border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            ›
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Pet Cards -->
            <div class="flex gap-4 overflow-x-auto pb-2 justify-center">
                @forelse($pets as $pet)
                    <div 
                        wire:click="togglePetSelection({{ $pet->id }})"
                        class="flex-shrink-0 w-32 cursor-pointer transition-all duration-200 hover:scale-105"
                    >
                        <div class="bg-white rounded-xl border-2 {{ in_array($pet->id, $selectedPets) ? 'border-blue-500' : 'border-gray-200' }} p-3 shadow-sm hover:shadow-md">
                            <!-- Pet Image -->
                            <div class="w-20 h-20 mx-auto mb-3 rounded-full overflow-hidden border-2 border-gray-200">
                                @if($pet->profile_image)
                                    <img 
                                        src="{{ Storage::url($pet->profile_image) }}" 
                                        alt="{{ $pet->name }}" 
                                        class="w-full h-full object-cover"
                                    />
                                @else
                                    <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Pet Name -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium {{ in_array($pet->id, $selectedPets) ? 'text-blue-600' : 'text-gray-900' }}">
                                    {{ $pet->name }}
                                </span>
                                <div class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-sm text-gray-500 w-full">
                        No pets found with sterilisation status.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Add-ons and Agreements -->
        <div class="bg-white px-4 pt-4 pb-1 space-y-4 border-b border-gray-100">
            <!-- Add-ons selector -->
            <div>
                <div class="text-xl font-semibold text-gray-900 mb-2">Choose add-ons</div>
                <div class="relative">
                    <select 
                        class="w-full appearance-none border border-gray-300 rounded-2xl px-5 py-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-12"
                        wire:change="selectAddon($event.target.value)"
                    >
                        <option value="">Choose add-ons</option>
                        @foreach($addons as $ad)
                            <option value="{{ $ad['id'] }}">{{ $ad['name'] }} — S$ {{ number_format(0, 2) }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">▾</div>
                </div>
            </div>

            <!-- Selected add-ons list -->
            @if(count($selectedAddons) > 0)
                <div class="space-y-3">
                    @foreach($selectedAddons as $sel)
                        <div class="flex items-center justify-between bg-blue-50 text-gray-800 rounded-2xl px-5 py-4">
                            <div class="text-lg">{{ $sel['name'] }}</div>
                            <div class="flex items-center gap-3">
                                <button class="w-9 h-9 rounded-xl border border-gray-300 flex items-center justify-center text-gray-700 bg-white" wire:click="decrementAddon({{ $sel['id'] }})">−</button>
                                <div class="w-8 text-center font-medium">{{ $sel['qty'] }}</div>
                                <button class="w-9 h-9 rounded-xl border border-gray-300 flex items-center justify-center text-gray-700 bg-white" wire:click="incrementAddon({{ $sel['id'] }})">＋</button>
                                <div class="w-24 text-right font-semibold">S$ {{ number_format(1, 2) }}</div>
                                <button class="ml-2 text-gray-400 hover:text-gray-600" wire:click="removeAddon({{ $sel['id'] }})" aria-label="Remove">✕</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Total amount summary -->
            <div class="bg-gray-50 border border-gray-200 rounded-2xl px-6 py-4 flex items-center justify-between">
                <div class="text-lg font-semibold text-gray-800">Total Amount : S$ {{ number_format($this->addonsTotal, 2) }}</div>
                <div class="w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600">▾</div>
            </div>

            <!-- Agreements -->
            <div class="space-y-4 pt-1">
                <label class="flex items-center gap-3 text-gray-700">
                    <input type="checkbox" class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" wire:model="agreeLeashed">
                    <span>{{ $agreementContent }}</span>
                </label>
                <label class="flex items-center gap-3 text-gray-700">
                    <input type="checkbox" class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" wire:model="agreeTerms">
                    <span>
                        I have read the agreement: 
                        <a href="{{ $agreementDocumentUrl }}" target="_blank" class="text-blue-600 underline">Click here</a>
                    </span>
                </label>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto bg-white p-4 space-y-3">
            @forelse(($booking['items'] ?? []) as $it)
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
                <div class="text-center py-8 text-sm text-gray-500">Your booking is empty.</div>
            @endforelse
        </div>

        <div class="p-4 border-t bg-white space-y-3">
            <!-- Compact per-item totals only -->
            @php
                $rows = [];
                foreach (($booking['items'] ?? []) as $it) {
                    $labelName = $it['name'] ?? $it['product_name'] ?? null;
                    $labelName = is_string($labelName) && trim($labelName) !== '' ? trim($labelName) : __('Item');
                    $variantSuffix = !empty($it['variant_display_name']) ? ' (' . $it['variant_display_name'] . ')' : '';
                    $label = $labelName . $variantSuffix . ' × ' . ($it['qty'] ?? 1);
                    $rows[] = ['label' => $label, 'total' => (float)($it['subtotal'] ?? 0)];
                }
                $grand = (float)($booking['total'] ?? 0);
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
            @php($reserved = session('booking.reserved_until'))
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
                    <a href="" class="flex-1 px-4 py-2 border rounded text-center">View booking</a>
                    <button wire:click="proceed" class="flex-1 px-4 py-2 rounded bg-blue-600 text-white text-center">Proceed to pay</button>
                @else
                    <button type="button" @click="showAuthPrompt = true" class="flex-1 px-4 py-2 border rounded text-center">View booking</button>
                    <button type="button" wire:click="guestProceed" class="flex-1 px-4 py-2 rounded bg-blue-600 text-white text-center">Proceed to pay</button>

                    <!-- Guest Auth Prompt -->
                    <div x-cloak x-show="showAuthPrompt" class="fixed inset-0 z-[70] flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/50" @click="showAuthPrompt=false"></div>
                        <div class="relative z-[71] w-[92%] max-w-md bg-white rounded-xl shadow-xl p-6">
                            <h3 class="text-lg font-semibold mb-2">Create an account or login</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                You’re not registered or logged in. To continue to booking, please create an account or login.
                                Your items will remain in your booking.
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