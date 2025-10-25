@props([
    'url' => '#',
    'image' => null,
    'name' => '',
    'price' => '',
    // Optional service-provided fields for sale badge and crossed price
    'discountPercent' => null,
    'comparePrice' => null,
])
<div class="group bg-white border rounded-xl p-3 hover:shadow transition flex flex-col hover:border-blue-500">
    <!-- Product image -->
    <a href="{{ $url }}" class="relative aspect-square bg-gray-50 rounded overflow-hidden flex items-center justify-center">
        @if($image)
            <img src="{{ $image }}" alt="{{ $name }}" class="object-contain w-full h-full" />
        @else
            <span class="text-gray-400 text-sm">No image</span>
        @endif
        @if(($discountPercent ?? 0) > 0)
            <span class="absolute top-2 left-2 inline-flex items-center px-2 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
                {{ $discountPercent }}% Off
            </span>
        @endif
    </a>

    <!-- Product info -->
    <div class="mt-3 flex-1">
        <div class="text-sm text-gray-900 line-clamp-2">{{ $name }}</div>
        <div class="mt-1 flex items-center gap-2">
            <div class="text-sm font-semibold">{{ $price }}</div>
            @if(!is_null($comparePrice) && ($discountPercent ?? 0) > 0)
                <div class="text-xs text-gray-500 line-through">{{ $comparePrice }}</div>
            @endif
        </div>
    </div>

    <!-- Actions: keep simple to avoid breaking existing cart logic -->
    <div class="mt-3">
        <a href="{{ $url }}" class="inline-flex w-full items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">View details</a>
    </div>
</div>