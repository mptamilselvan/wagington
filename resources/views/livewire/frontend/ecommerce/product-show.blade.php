@section('meta_tags')
    @php
        $canonicalUrl = route('shop.product', $product['slug']);
    @endphp
    
    <x-seo.meta-tags 
        :title="$product['meta_title'] ?? $product['name'] ?? ''"
        :description="$product['meta_description'] ?? $product['short_description'] ?? ''"
        :keywords="$product['meta_keywords'] ?? ''"
        :canonicalUrl="$canonicalUrl"
        :type="'product'"
    />
    
    @if(isset($product['gallery'][0]) && !empty($product['gallery'][0]))
        <meta property="og:image" content="{{ $product['gallery'][0] }}">
        <meta property="twitter:image" content="{{ $product['gallery'][0] }}">
    @endif
@endsection

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6">
        <ol class="flex flex-wrap items-center gap-1">
            <li><a href="{{ route('shop.list') }}" class="hover:underline">Shop</a></li>
            @foreach(($product['breadcrumbs'] ?? []) as $bc)
                <li aria-hidden="true">/</li>
                <li><a href="{{ $bc['url'] }}" class="hover:underline">{{ $bc['name'] }}</a></li>
            @endforeach
            <li aria-hidden="true">/</li>
            <li class="text-gray-700">{{ $product['name'] ?? '' }}</li>
        </ol>
    </nav>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Gallery with bottom thumbnails -->
        <div class="lg:w-1/2">
            <div class="aspect-square bg-white rounded border flex items-center justify-center overflow-hidden relative" wire:key="main-{{ $selectedVariantId }}-{{ $activeIndex }}">
                @php $main = $activeGallery[$activeIndex] ?? null; @endphp
                @if($main)
                    <img src="{{ $main }}" alt="{{ $product['name'] }}" class="object-contain w-full h-full" />
                @else
                    <span class="text-gray-400">No image</span>
                @endif
                {{-- Optional sale badge --}}
                @php
                    $sel = collect($product['variants'] ?? [])->firstWhere('id', $selectedVariantId);
                    $discount = $sel ? ($sel['discount_percent'] ?? 0) : 0;
                @endphp
                @if($discount)
                    <span class="absolute top-3 left-3 bg-rose-500 text-white text-xs font-semibold px-2 py-1 rounded">-{{ $discount }}%</span>
                @endif
            </div>

            {{-- Bottom thumbnail carousel --}}
            <div class="mt-3 relative">
                <button type="button" class="absolute -left-3 top-1/2 -translate-y-1/2 z-10 bg-white/90 border rounded-full w-8 h-8 flex items-center justify-center shadow" x-data @click="$el.nextElementSibling.scrollBy({left:-160,behavior:'smooth'})">
                    <span class="sr-only">Prev</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1 0 1.06L9.08 10l3.7 3.71a.75.75 0 1 1-1.06 1.06l-4.24-4.24a.75.75 0 0 1 0-1.06l4.24-4.24a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd"/></svg>
                </button>
                <div class="flex gap-2 overflow-x-auto no-scrollbar px-6" style="scroll-snap-type: x mandatory;">
                    @php $gid = $selectedVariantId ?: 'base'; @endphp
                    @foreach(($activeGallery ?? []) as $idx => $g)
                        <button type="button" wire:key="thumb-{{ $gid }}-{{ $idx }}" wire:click="setActiveIndex({{ $idx }})" class="shrink-0 w-20 h-20 border rounded overflow-hidden {{ $activeIndex === $idx ? 'ring-2 ring-blue-600' : '' }}" style="scroll-snap-align: start;">
                            <img src="{{ $g }}" class="w-full h-full object-cover" />
                        </button>
                    @endforeach
                </div>
                <button type="button" class="absolute -right-3 top-1/2 -translate-y-1/2 z-10 bg-white/90 border rounded-full w-8 h-8 flex items-center justify-center shadow" x-data @click="$el.previousElementSibling.scrollBy({left:160,behavior:'smooth'})">
                    <span class="sr-only">Next</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.92 10l-3.7-3.71a.75.75 0 1 1 1.06-1.06l4.24 4.24a.75.75 0 0 1 0 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd"/></svg>
                </button>
            </div>
        </div>

        <!-- Right: product info - More compact layout -->
        <div class="lg:w-1/2 space-y-4">
            <div class="space-y-2">
                <h1 class="text-2xl font-bold text-gray-900">{{ $product['name'] }}</h1>

                @php
                    $sel = collect($product['variants'] ?? [])->firstWhere('id', $selectedVariantId);
                    $exact = $sel ? $sel['price'] : null;
                    $compare = $sel ? $sel['compare_price'] : null;
                    $discount = $sel ? ($sel['discount_percent'] ?? 0) : 0;
                    $sku = $sel ? $sel['sku'] : null;
                    $stock = $sel ? $sel['stock'] : null;
                    $min = $product['price_min'] ?? 0; $max = $product['price_max'] ?? 0;
                @endphp
                <div class="flex items-center gap-3">
                    <div class="text-2xl font-bold text-gray-900">
                        @if(!is_null($exact))
                            <div class="flex items-baseline gap-2">
                                @if($compare && $compare > $exact)
                                    <span class="text-gray-400 line-through text-lg">S${{ number_format($compare, 2) }}</span>
                                    <span class="text-blue-600">S${{ number_format($exact, 2) }}</span>
                                    @if($discount)
                                        <span class="bg-red-100 text-red-700 text-xs font-semibold px-2 py-1 rounded">-{{ $discount }}%</span>
                                    @endif
                                @else
                                    <span class="text-blue-600">S${{ number_format($exact, 2) }}</span>
                                @endif
                            </div>
                        @else
                            <span class="text-blue-600">{{ $min && $max && $min != $max ? ('S$'.number_format($min,2).' – S$'.number_format($max,2)) : ('S$'.number_format($min,2)) }}</span>
                        @endif
                    </div>
                </div>
                @if($sku)
                    <div class="text-gray-500 text-xs">Product Code: {{ $sku }}</div>
                @endif
                @php
                    $available = $sel ? ($sel['available'] ?? null) : null;
                    $label = $sel ? ($sel['availability_label'] ?? null) : null;
                @endphp
            </div>

            {{-- Attribute options (hide section if there are none) --}}
            @if(!empty($attributeOptions))
                @foreach(($attributeOptions ?? []) as $attr => $values)
                    @php $availableForAttr = $availableValues[$attr] ?? []; @endphp
                    <div class="space-y-2">
                        <div class="text-sm font-medium text-gray-800">{{ ucfirst($attr) }}</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($values as $val)
                                @php
                                    $active = ($selectedAttributes[$attr] ?? null) === $val;
                                    $enabled = in_array($val, $availableForAttr, true);
                                    $meta = $attributeMeta[$attr][$val] ?? null;
                                    $hex = $meta['color_hex'] ?? null;
                                @endphp
                                @if(strtolower($attr) === 'color')
                                    <button
                                        type="button"
                                        wire:click="selectAttribute({{ \Illuminate\Support\Js::from($attr) }}, {{ \Illuminate\Support\Js::from($val) }})"
                                        @disabled(!$enabled)
                                        class="group relative inline-flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all
                                            {{ $active ? 'ring-2 scale-110' : 'border-gray-300 bg-white hover:border-gray-400' }}
                                            {{ $enabled ? '' : 'opacity-40 cursor-not-allowed' }}"
                                        style="{{ $active ? 'ring-color: #1B85F3; border-color: #1B85F3;' : '' }}"
                                        title="{{ $val }}">
                                        @if($active)
                                            <span class="absolute inset-0 flex items-center justify-center text-white text-xs font-bold">✓</span>
                                        @endif
                                        <span class="w-7 h-7 rounded-full" style="background-color: {{ $hex ?: strtolower($val) }}"></span>
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="selectAttribute({{ \Illuminate\Support\Js::from($attr) }}, {{ \Illuminate\Support\Js::from($val) }})"
                                        @disabled(!$enabled)
                                        class="px-4 py-2 rounded-lg border text-sm font-medium transition-all
                                            {{ $active ? '' : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100 hover:border-gray-300' }}
                                            {{ $enabled ? '' : 'opacity-40 cursor-not-allowed' }}"
                                        style="{{ $active ? 'background-color: #1B85F3; border-color: #1B85F3; color: white;' : '' }}">
                                        <span>{{ $val }}</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif



            {{-- Add-ons section (required + optional) - More compact styling --}}
            @if(!empty($product['addons']))
                <div class="mt-4 space-y-2">
                    <div class="text-sm font-medium text-gray-800">Add-ons</div>
                    @foreach($product['addons'] as $ad)
                        @php $row = $selectedAddons[$ad['id']] ?? null; @endphp
                        <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-2">
                        {{-- Addon thumbnail (smaller, cleaner) --}}
                            @if(!empty($ad['image']))
                                <img src="{{ $ad['image'] }}" alt="{{ $ad['name'] }}" class="w-10 h-10 rounded object-cover" />
                            @else
                                <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-[10px] text-gray-400">No img</div>
                           @endif    
                        <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <div class="font-medium text-sm truncate">{{ $ad['name'] }}</div>
                                    @if(!empty($ad['is_required']))
                                        <span class="text-[10px] text-red-600 bg-red-100 px-1.5 py-0.5 rounded font-medium">Required</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-600">S${{ number_format($row['unit_price'] ?? 0, 2) }}</div>
                            </div>
                            @if(empty($ad['is_required']))
                                <label class="text-xs flex items-center gap-1.5 text-blue-600">
                                    <input type="checkbox" wire:model="selectedAddons.{{ $ad['id'] }}.selected" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                    <span>Include</span>
                                </label>
                            @else
                                <span class="text-xs text-gray-500 bg-green-100 px-2 py-0.5 rounded font-medium">Included</span>
                            @endif
                            <div class="flex items-center gap-1.5">
                                <div class="text-xs font-medium text-gray-800">S${{ number_format($row['subtotal'] ?? 0, 2) }}</div>
                                <input type="number" min="1" wire:model.live="selectedAddons.{{ $ad['id'] }}.qty" class="border rounded px-1.5 py-1 w-12 text-xs text-center" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Quantity + CTA - More compact, Figma-inspired styling --}}
            <div class="mt-4 bg-gray-50 rounded-xl p-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700">Quantity:</span>
                            @php 
                                $maxQty = 10; // default value
                                if ($sel) {
                                    // Get the max quantity per order setting (if set)
                                    $maxQtyPerOrder = (int)($sel['max_quantity_per_order'] ?? 0);
                                    $available = (int)($sel['available'] ?? 0);
                                    $allowBackorders = (bool)($sel['allow_backorders'] ?? false);
                                    
                                    // If max_quantity_per_order is set and greater than 0, use it as the primary limit
                                    if ($maxQtyPerOrder > 0) {
                                        if ($allowBackorders) {
                                            // With backorders allowed, can go up to max_quantity_per_order
                                            $maxQty = $maxQtyPerOrder;
                                        } else {
                                            // Without backorders, limited by available stock
                                            // and max_quantity_per_order
                                            $maxQty = min($maxQtyPerOrder, $available);
                                        }
                                    } else {
                                        // No max_quantity_per_order set
                                        if ($allowBackorders) {
                                            $maxQty = 10; // Default limit for backorders
                                        } else {
                                            $maxQty = $available; // May be 0 if out of stock
                                        }
                                    }

                                    // Only ensure minimum of 1 if there is actual stock available
                                    // and backorders are not allowed
                                    if (!$allowBackorders && $available > 0) {
                                        $maxQty = max(1, $maxQty);
                                    }
                                }
                            @endphp
                            <!-- Use live binding so availability/backorder hint updates while typing -->
                            <input type="number" min="1" max="{{ $maxQty }}" wire:model.live="qty" @input="$dispatch('qty-changed')" class="border-0 bg-white rounded-lg px-3 py-1.5 w-16 text-center text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-500" />
                        </div>
                        @php $subtotal = $exact ? $exact * max(1,(int)$qty) : null; @endphp
                        @if($subtotal)
                            <div class="text-lg font-bold text-gray-900">S${{ number_format($subtotal,2) }}</div>
                        @endif
                    </div>
                    @php 
                        $canAdd = true; // default to true
                        $hasQtyError = false; // default to false
                        if ($sel) {
                            $canAdd = !$sel['track_inventory'] || ($sel['available'] ?? 0) > 0 || ($sel['allow_backorders'] ?? false);
                            // Check if there's a quantity error
                            $requested = max(1, (int)($qty ?? 1));
                            $maxQtyPerOrder = (int)($sel['max_quantity_per_order'] ?? 0);
                            $hasQtyError = $maxQtyPerOrder > 0 && $requested > $maxQtyPerOrder;
                        }
                        // Also disable button if there's a component error
                        $hasComponentError = !empty($errorMessage);
                    @endphp
                    <button wire:click="addToCart" @disabled(!$canAdd || $hasQtyError || $hasComponentError) class="px-8 py-2.5 rounded-lg font-semibold text-sm transition-all
                            {{ ($canAdd && !$hasQtyError && !$hasComponentError) ? 'bg-white border-2 shadow-md hover:shadow-lg' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}"
                            style="{{ ($canAdd && !$hasQtyError && !$hasComponentError) ? 'color: #1B85F3; border-color: #1B85F3;' : '' }}">
                        Add to cart
                    </button>
                </div>
                @if($sel && !$canAdd && !($sel['allow_backorders'] ?? false))
                    <div class="text-xs text-red-600 mt-2">Out of stock</div>
                @endif
                @if (!empty($errorMessage))
                    <div class="text-xs text-red-600 mt-2 font-medium">{{ $errorMessage }}</div>
                @endif
            </div>
            {{-- Live availability/backorder hint for typed qty --}}
            @php
                $avail = 0; // default value
                $requested = max(1, (int)($qty ?? 1));
                $allowBO = false; // default value
                $maxQtyPerOrder = 0; // default value
                if ($sel) {
                    $avail = (int)($sel['available'] ?? 0);
                    $allowBO = (bool)($sel['allow_backorders'] ?? false);
                    $maxQtyPerOrder = (int)($sel['max_quantity_per_order'] ?? 0);
                }
            @endphp
            @if($sel)
                @if($maxQtyPerOrder > 0 && $requested > $maxQtyPerOrder)
                    <div class="text-xs text-red-600 mt-1 font-medium">Maximum quantity allowed per order is {{ $maxQtyPerOrder }}</div>
                @elseif(!empty($sel['track_inventory']))
                    @if($allowBO)
                        @php $res = max(0, min($requested, $avail)); $bo = max(0, $requested - $res); @endphp
                        @if($bo > 0)
                            <span class="text-green-700 font-medium">{{ $res }} available</span>
                            <span class="text-red-600 ml-1">, {{ $bo }} backordered</span>
                            @if(!empty($sel['backorder_eta']))
                                <span class="text-amber-700"> • ETA: {{ $sel['backorder_eta'] }}</span>
                            @endif
                        @else
                            <span class="text-green-700 font-medium">{{ $res }} available</span>
                        @endif
                    @else
                        @if($requested <= $avail)
                            <span class="text-green-700 font-medium">{{ $avail }} available</span>
                        @else
                            @if($avail <= 0)
                                <span class="text-red-600 font-medium">Out of stock</span>
                            @else
                                <span class="text-red-600 font-medium">Only {{ $avail }} available</span>
                            @endif
                        @endif
                    @endif
                @endif
            @endif
            <div x-data="{ open:false, errorMessage: '' }" 
                 x-on:cart-updated.window="open=true; setTimeout(()=>open=false, 1800)"
                 x-on:cart-error.window="errorMessage = $event.detail.message; open=true; setTimeout(()=>open=false, 5000)"
                 x-on:qty-changed.window="errorMessage = ''">
                <div x-show="open" class="fixed bottom-6 right-6 bg-green-600 text-white px-4 py-2 rounded shadow" x-cloak>Added to cart</div>
                <div x-show="errorMessage" class="fixed bottom-6 right-6 bg-red-600 text-white px-4 py-2 rounded shadow" x-cloak>
                    <span x-text="errorMessage"></span>
                    <button @click="errorMessage = ''" class="ml-2 text-white hover:text-gray-200">×</button>
                </div>
            </div>



            {{-- Tabs --}}
            <div class="mt-8">
                <div class="flex gap-6 border-b">
                    <button type="button" wire:click="switchTab('features')" class="py-2 {{ $activeTab==='features' ? 'border-b-2 border-yellow-400 font-medium' : 'text-gray-500' }}">Features</button>
                    <button type="button" wire:click="switchTab('description')" class="py-2 {{ $activeTab==='description' ? 'border-b-2 border-yellow-400 font-medium' : 'text-gray-500' }}">Description</button>
                    <!-- <button type="button" wire:click="switchTab('info')" class="py-2 {{ $activeTab==='info' ? 'border-b-2 border-yellow-400 font-medium' : 'text-gray-500' }}">Additional Info</button> -->
                </div>
                <div class="py-4 text-sm text-gray-700">
                    @if($activeTab==='features')
                        {{-- Show Short Description --}}
                        <div class="prose prose-sm max-w-none">{!! nl2br(e($product['short_description'] ?? '')) !!}</div>
                    @elseif($activeTab==='description')
                        {{-- Show Long Description --}}
                        <div class="prose prose-sm max-w-none">{!! nl2br(e($product['description'] ?? '')) !!}</div>
                    @else
                        {{-- Additional Info left blank intentionally for now --}}
                        <div class="text-gray-400"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(!empty($recommendations))
        <div class="mt-16">
            <h2 class="text-xl font-semibold mb-3">You may also like</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach($recommendations as $p)
                    <a href="{{ $p['url'] }}" class="border rounded-lg p-3 hover:shadow">
                        <div class="aspect-square bg-gray-50 rounded flex items-center justify-center overflow-hidden">
                            @if($p['image'])
                                <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="object-contain w-full h-full" />
                            @else
                                <span class="text-gray-400 text-sm">No image</span>
                            @endif
                        </div>
                        <div class="mt-3">
                            <div class="text-sm text-gray-900 line-clamp-2">{{ $p['name'] }}</div>
                            <div class="text-sm font-semibold mt-1">{{ $p['price_text'] }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
