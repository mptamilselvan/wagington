@php
    // Compute header title and canonical URL early so they are available throughout the template
    $headerTitle = 'Products';
    $canonicalUrl = route('shop.list');

    if (!empty($filters['breadcrumbs']) && is_array($filters['breadcrumbs'])) {
        $lastIndex = count($filters['breadcrumbs']) - 1;
        if ($lastIndex >= 0) {
            $last = $filters['breadcrumbs'][$lastIndex];
            if (!empty($last['name'])) {
                $headerTitle = strip_tags($last['name']);
                if (!empty($last['url'])) {
                    // Validate URL format before using
                    $canonicalUrl = filter_var($last['url'], FILTER_VALIDATE_URL) 
                        ? $last['url'] 
                        : url($last['url']);
                }
            }
        }
    }

    $metaTitle = $headerTitle . ' - ' . config('app.name');
    $metaDescription = 'Browse our collection of ' . mb_strtolower($headerTitle) . '. Find the perfect products at great prices.';
@endphp

@section('meta_tags')
    <x-seo.meta-tags 
        :title="$metaTitle"
        :description="$metaDescription"
        :canonicalUrl="$canonicalUrl"
        :type="'website'"
    />
@endsection

<x-ecommerce.page-wrapper heroImage="/images/e-comerce-1.png">
    <x-slot name="header">
        <!-- Floating header: show actual category/product group title only -->
        <div class="flex">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-700">{{ $headerTitle }}</h1>
        </div>
    </x-slot>
    
    <!-- Breadcrumbs -->
    @if(!empty($filters['breadcrumbs']))
        <nav class="text-sm text-gray-500 mb-4"><ol class="flex flex-wrap items-center gap-1">
            <li><a href="{{ route('shop.list') }}" class="hover:underline">Shop</a></li>
            @foreach($filters['breadcrumbs'] as $bc)
                <li aria-hidden="true">/</li>
                <li><a href="{{ $bc['url'] }}" class="hover:underline">{{ $bc['name'] }}</a></li>
            @endforeach
        </ol></nav>
    @endif

    <!-- Toolbar: count (left) + search/filter (right) in one line above grid -->
    <div class="mt-10 sm:mt-12 mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="text-sm text-gray-600">
            @if(method_exists($products, 'total'))
                Showing {{ number_format($products->total()) }} Products
            @endif
        </div>
        <div class="flex w-full md:w-auto items-center gap-3">
            <div class="relative w-full md:w-80">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                    <x-icons.search class="h-4 w-4" />
                </span>
                <input type="text" wire:model.live.debounce.400ms="q" placeholder="Search" class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-filter'))" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-sm">
                <span>Filter</span>
            </button>
        </div>
    </div>

    <!-- Slide-over Filter Panel (Alpine) -->
    <div x-data="{ open: false }" @open-filter.window="open=true" @keydown.escape.window="open=false" x-cloak>
        <!-- Overlay -->
        <div x-show="open" class="fixed inset-0 z-40" aria-hidden="true">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open=false"></div>
        </div>
        <!-- Panel -->
        <div x-show="open" class="fixed inset-y-0 right-0 max-w-full flex z-50">
            <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col rounded-l-2xl">
                <div class="flex items-center justify-between p-5 border-b">
                    <h2 class="text-lg font-semibold">Filter</h2>
                    <button @click="open=false" class="h-8 w-8 inline-flex items-center justify-center rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">✕</button>
                </div>
                <div class="p-5 overflow-y-auto flex-1">
                    <button wire:click="clearFilters" class="text-sm text-gray-600 hover:underline mb-4">Reset</button>

                    <!-- Sort -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-1">Sort</label>
                        <select wire:model="sort" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(($filters['sort'] ?? []) as $s)
                                <option value="{{ $s['key'] }}">{{ $s['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category (hidden if category_id present in route) -->
                    @if(!$category_id)
                    <div class="mb-6">
                        <div class="text-sm font-medium mb-2">Categories</div>
                        <div class="max-h-60 overflow-auto space-y-2 pr-1">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="radio" name="category_id" wire:model.live="category_id" value="" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                <span>All</span>
                            </label>
                            @foreach(($filters['categories'] ?? []) as $cat)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="radio" name="category_id" wire:model.live="category_id" value="{{ $cat['id'] }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                    <span>{{ $cat['name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Price -->
                    <div class="mb-6">
                        <div class="text-sm font-medium mb-2">Price</div>
                        <div class="flex gap-2">
                            <input type="number" step="0.01" inputmode="decimal" autocomplete="off" wire:model.live.debounce.600ms="price_min" placeholder="Min" class="w-full rounded-xl border border-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <input type="number" step="0.01" inputmode="decimal" autocomplete="off" wire:model.live.debounce.600ms="price_max" placeholder="Max" class="w-full rounded-xl border border-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        @if(isset($filters['price']))
                            <div class="text-xs text-gray-500 mt-1">
                                Range: ${{ number_format($filters['price']['min'] ?? 0, 2) }} – ${{ number_format($filters['price']['max'] ?? 0, 2) }}
                            </div>
                        @endif
                    </div>
                    <!-- Shippable Filter -->
                    <div class="mb-6">
                        <div class="text-sm font-medium mb-2">Shipping</div>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="radio" name="shippable" wire:model.live="shippable" value="" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                <span>All</span>
                            </label>
                            @foreach(($filters['shipping_filter'] ?? []) as $shipOption)
                                <label class="flex items-center gap-2 text-sm" wire:key="ship-{{ $shipOption['key'] }}">
                                    <input type="radio" name="shippable" wire:model.live="shippable" value="{{ $shipOption['key'] }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                    <span>{{ $shipOption['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Dynamic Variant Attributes -->
                    
                    @if(isset($filters['attributes']) && count($filters['attributes']) > 0)
                        @foreach($filters['attributes'] as $group)
                            @if(isset($group['values']) && count($group['values']) > 0)
                                <div class="mb-6">
                                    <div class="text-sm font-medium mb-2">{{ $group['name'] }}</div>
                                    <div class="grid grid-cols-1 gap-2 max-h-56 overflow-auto pr-1">
                                        @foreach($group['values'] as $val)
                                            <label class="flex items-center gap-2 text-sm" wire:key="attr-{{ md5($group['name'].'|'.$val) }}">
                                                <input type="checkbox" wire:model.live="attrs.{{ $group['name'] }}" value="{{ $val }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                                <span>{{ $val }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
                <div class="p-5 border-t flex justify-end gap-3">
                    <button wire:click="clearFilters" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-700 bg-white hover:bg-gray-50">Reset</button>
                    <button @click="open=false" wire:click="$refresh" class="px-4 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700">Apply Filter</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Grid -->
    <section class="mt-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($products as $p)
                @php($url = $p['url'] ?? '#')
                <x-ecommerce.product-card
                    :url="$url"
                    :image="$p['image'] ?? null"
                    :name="$p['name'] ?? ''"
                    :price="$p['price_text'] ?? ''"
                    :discountPercent="$p['discount_percent'] ?? null"
                    :comparePrice="$p['compare_price_min'] ?? null"
                />
            @endforeach
        </div>
        <div class="mt-6 flex justify-center">{{ $products->links() }}</div>
    </section>
</x-ecommerce.page-wrapper>