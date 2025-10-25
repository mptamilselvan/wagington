@props([
    'url' => '#',
    'image' => null,
    'name' => '',
    'price' => null,
    'description' => null,
])
<div class="group bg-white border rounded-xl p-3 hover:shadow transition flex flex-col hover:border-blue-500">
    <!-- Room image -->
    <a href="{{ $url }}" class="relative aspect-square bg-gray-50 rounded overflow-hidden flex items-center justify-center">
        @if($image)
            <img src="{{ $image }}" alt="{{ $name }}" class="object-contain w-full h-full" />
            <!-- Price overlay at left bottom -->
            @if($price)
                <div class="absolute bottom-2 left-2 bg-white bg-opacity-95 rounded-[20px] px-6 py-4 text-center shadow-sm">
                    <div class="text-sm font-semibold text-blue-600">From {{ $price }}</div>
                </div>
            @endif
        @else
            <span class="text-gray-400 text-sm">No image</span>
        @endif
    </a>

    <!-- Room info -->
     <div class="flex flex-col justify-between h-full pl-5 pr-5">
        <div class="mt-3 flex-1">
            <div class="text-sm text-gray-900 line-clamp-2">{{ $name }}</div>
            @if($description)
                <div class="mt-2 text-xs text-gray-600 line-clamp-3">{{ $description }}</div>
            @endif
        </div>

        <!-- Actions -->
        <div class="mt-3">
            <a href="{{ $url }}" class="inline-flex items-center text-blue-600 text-sm font-medium hover:text-blue-700">
                More info →
            </a>
        </div>
    </div>

</div>