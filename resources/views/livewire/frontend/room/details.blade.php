@section('meta_tags')
    @php
        $metaTitle = $room->name . ' - ' . config('app.name');
        $metaDescription = $room->room_description;
        $canonicalUrl = route('room.details', ['slug' => $room->slug]);
    @endphp
    
    <x-seo.meta-tags 
        :title="$metaTitle"
        :description="$metaDescription"
        :canonicalUrl="$canonicalUrl"
        :type="'room'"
    />
@endsection

<x-room.page-wrapper heroImage="/images/hero-room.png">
    <x-slot name="header">
        <div class="flex flex-col">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $room->name }}</h1>
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

    <!-- Room Details Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ activeTab: 'overview' }">
        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200 mb-8">
            <nav class="-mb-px flex space-x-8">
                <button 
                    @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                >
                    Overview
                </button>
                <button 
                    @click="activeTab = 'highlights'"
                    :class="activeTab === 'highlights' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                >
                    Highlights
                </button>
                <button 
                    @click="activeTab = 'terms'"
                    :class="activeTab === 'terms' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                >
                    Terms & Conditions
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column - Text Content -->
            <div class="space-y-6">
                <!-- Overview Tab -->
                <div x-show="activeTab === 'overview'" x-transition>
                    <div class="prose max-w-none">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Room Description</h3>
                        <div class="text-gray-700 leading-relaxed">
                            {!! nl2br(e($room->room_description)) !!}
                        </div>
                    </div>
                </div>

                <!-- Highlights Tab -->
                <div x-show="activeTab === 'highlights'" x-transition>
                    <div class="prose max-w-none">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Room Highlights</h3>
                        <div class="text-gray-700 leading-relaxed">
                            {!! nl2br(e($room->room_highlights)) !!}
                        </div>
                    </div>
                </div>

                <!-- Terms & Conditions Tab -->
                <div x-show="activeTab === 'terms'" x-transition>
                    <div class="prose max-w-none">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Terms & Conditions</h3>
                        <div class="text-gray-700 leading-relaxed">
                            {!! nl2br(e($room->room_terms_and_conditions)) !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Images -->
            <div class="space-y-4">
                @if($room->images && is_array($room->images) && count($room->images) > 0)
                    @php
                        $primaryImage = null;
                        $otherImages = [];
                        
                        // Find primary image and separate others
                        foreach($room->images as $image) {
                            if(is_array($image) && isset($image['primary']) && $image['primary'] === true) {
                                $primaryImage = $image;
                            } else {
                                $otherImages[] = $image;
                            }
                        }
                        
                        // If no primary image found, use first image as primary
                        if(!$primaryImage && count($otherImages) > 0) {
                            $primaryImage = array_shift($otherImages);
                        }
                    @endphp

                    <!-- Images Layout: Other Images (Left) + Primary Image (Right) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Other Images (Left Column) -->
                        @if(count($otherImages) > 0)
                            @php
                                $totalOtherImages = count($otherImages);
                                $primaryImageHeight = 320; // h-80 = 320px
                                $gapBetweenImages = 22; // space-y-2 = 8px
                                $totalGapHeight = ($totalOtherImages - 1) * $gapBetweenImages;
                                $availableHeight = $primaryImageHeight - $totalGapHeight;
                                $individualImageHeight = $totalOtherImages > 0 ? $availableHeight / $totalOtherImages : 0;
                            @endphp
                            <div class="md:col-span-1 space-y-2">
                                @foreach($otherImages as $index => $image)
                                    <div style="height: {{ $individualImageHeight }}px;">
                                        <img 
                                            src="{{ $image['url'] ?? $image }}" 
                                            alt="{{ $room->name }} - Image {{ $index + 1 }}"
                                            class="w-full h-full object-cover rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                                            @click="
                                                // Simple image modal functionality
                                                $dispatch('open-modal', {
                                                    type: 'image',
                                                    src: '{{ $image['url'] ?? $image }}',
                                                    alt: '{{ $room->name }} - Image {{ $index + 1 }}'
                                                })
                                            "
                                        >
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Primary Image (Right Column) -->
                        @if($primaryImage)
                            <div class="md:col-span-2">
                                <img 
                                    src="{{ $primaryImage['url'] ?? $primaryImage }}" 
                                    alt="{{ $room->name }} - Primary Image"
                                    class="w-full h-80 object-cover rounded-lg shadow-lg"
                                >
                            </div>
                        @endif
                    </div>
                @else
                    <!-- No Images Placeholder -->
                    <div class="w-full h-80 bg-gray-200 rounded-lg flex items-center justify-center">
                        <div class="text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="mt-2 text-sm">No images available</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-room.page-wrapper>