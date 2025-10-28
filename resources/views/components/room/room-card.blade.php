@props([
    'url' => '#',
    'image' => null,
    'name' => '',
    'price' => null,
    'description' => null,
])
<div class="group bg-white border rounded-[20px] overflow-hidden hover:shadow transition flex flex-col hover:border-blue-500 h-96 w-full">
    <!-- Room image -->
    <a href="{{ $url }}" class="relative bg-gray-50 overflow-hidden flex items-center justify-center h-56 w-full">
        @if($image)
            <img src="{{ $image }}" alt="{{ $name }}" style="object-fit: cover;height: 320px;" class="object-cover w-full h-full" />
            <!-- Price overlay at left bottom -->
            @if($price)
                <div class="absolute bottom-6 left-3 bg-white bg-opacity-95 rounded-[20px] px-6 py-4 text-center shadow-sm">
                    <div class="text-sm font-semibold text-blue-600">From {{ $price }}</div>
                </div>
            @endif
        @else
            <span class="text-gray-400 text-sm">No image</span>
        @endif  
    </a>

    <!-- Room info -->
     <div class="flex flex-col justify-between flex-1 p-5 min-h-0" style="height: 280px;">
        <div class="overflow-hidden" style="height: 150px;">
            <div class="text-2xl font-bold text-gray-900 line-clamp-2 ">{{ $name }}</div>
            @if($description)
                <div class="mt-2 text-lg text-gray-600 line-clamp-5 leading-loose " style="line-height: 1.6;">{{ \Illuminate\Support\Str::limit($description, 250) }}</div>
            @endif
        </div>

        <!-- Actions -->
        <div class="mt-4 flex-shrink-0">
            <a href="{{ $url }}" class="inline-flex items-center text-blue-600 text-sm font-medium hover:text-blue-700">
                More info →
            </a>
        </div>
    </div>

</div>