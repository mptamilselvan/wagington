@props([
    'heroImage' => '/images/hero-room.png',
    'title' => null,
])
<div>
<div class="relative">
    <!-- Hero banner -->
    <div class="h-40 sm:h-48 md:h-56 lg:h-64 bg-gray-200">
        <img src="{{ $heroImage }}" alt="Rooms banner" class="w-full h-full object-cover" />
    </div>

    <!-- Floating card for title/search/tools -->
    <div class="absolute inset-x-0 -bottom-6 sm:-bottom-8 md:-bottom-10">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Limit width so it doesn't stretch full page -->
            <div class="max-w-7xl backdrop-blur-md rounded-2xl shadow-sm border border-gray-100 bg-gray-100">
                <div class="px-6 sm:px-8 lg:px-10 py-5 sm:py-6 lg:py-7">
                    @isset($header)
                        {{ $header }}
                    @else
                        @if($title)
                            <h1 class="text-xl sm:text-2xl font-semibold text-gray-700">{{ $title }}</h1>
                        @endif
                    @endisset
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main content placed with top padding to accommodate floating card -->
<div class="w-full max-w-screen-2xl 2xl:max-w-[1660px] mx-auto px-4 sm:px-6 lg:px-8 pt-10 sm:pt-12 md:pt-16 bg-gradient-to-b from-blue-50 to-white min-h-screen">
     {{ $slot }}

    <!-- Common ecommerce bottom sections -->
    <div class="mt-10 md:mt-14">
        <x-room.cta-contact />
    </div>
    <div class="mt-8 md:mt-10">
        <x-room.instagram-feed />
    </div>
</div>
</div>