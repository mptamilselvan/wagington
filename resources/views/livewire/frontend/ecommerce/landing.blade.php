@section('meta_tags')
    @php
        $metaTitle = 'E-Commerce - ' . config('app.name');
        $metaDescription = 'Discover our amazing products in our online store. Shop the latest trends and find great deals.';
        $canonicalUrl = route('shop.home');
    @endphp
    
    <x-seo.meta-tags 
        :title="$metaTitle"
        :description="$metaDescription"
        :canonicalUrl="$canonicalUrl"
        :type="'website'"
    />
@endsection

<x-ecommerce.page-wrapper heroImage="/images/e-comerce-1.png">
    <x-slot name="header">
<!-- Only title in floating header with updated styling to match Figma -->        
 <div class="flex flex-col">
             <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">E-Commerce</h1>
        </div>
    </x-slot>

    <!-- Standalone search just above the first section, aligned to the same container paddings -->
    <div class="max-w-lg ml-auto mr-4 sm:mr-6 lg:mr-8 mt-10 sm:mt-14 mb-6 sm:mb-8">
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                <x-icons.search class="h-4 w-4" />
            </span>
            <input
                type="text"
                wire:model.debounce.400ms="q"
                placeholder="Search"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 focus:bg-white pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm"
            />
        </div>
    </div>

    @foreach($sections as $section)
        <div class="mb-10 md:mb-12" x-data="{ scrollBy(delta){ $refs.r.scrollBy({left: delta, behavior: 'smooth'}) } }">
            <!-- Section header with View all and arrow controls -->
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg sm:text-xl font-semibold">{{ $section['category']['name'] ?? 'Category' }}</h2>
                <div class="flex items-center gap-2">
                    <a href="{{ route('shop.list', ['category_id' => $section['category']['id'] ?? null]) }}" class="text-blue-600 text-sm font-medium hover:underline">View all</a>
                    <button type="button" @click="scrollBy(-($refs.r.clientWidth||300))" class="h-8 w-8 inline-flex items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:shadow transition">
                        <x-icons.arrow.leftArrow class="h-4 w-4" />
                    </button>
                    <button type="button" @click="scrollBy(($refs.r.clientWidth||300))" class="h-8 w-8 inline-flex items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:shadow transition">
                        <x-icons.arrow.rightArrow class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Product row: horizontal scroll on small screens, grid on lg -->
            <div class="overflow-x-auto lg:overflow-visible" x-ref="r">
                <div class="flex gap-4 lg:grid lg:grid-cols-4 lg:gap-4 min-w-max lg:min-w-0">
                    @forelse($section['products'] as $p)
                        @php($url = $p['url'] ?? '#')
                        <div class="w-56 lg:w-auto flex-shrink-0">
                            <x-ecommerce.product-card
                                :url="$url"
                                :image="$p['image'] ?? null"
                                :name="$p['name'] ?? ''"
                                :price="$p['price_text'] ?? ''"
                                :discountPercent="$p['discount_percent'] ?? null"
                                :comparePrice="$p['compare_price_min'] ?? null"
                            />
                        </div>
                    @empty
                        <div class="text-gray-500 text-sm">No products match your search in this category.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</x-ecommerce.page-wrapper>