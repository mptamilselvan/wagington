@section('meta_tags')
    @php
        $metaTitle = $roomType->name . ' - ' . config('app.name');
        $metaDescription = $roomType->room_description;
        $canonicalUrl = route('room.details', ['slug' => $roomType->slug]);
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
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $roomType->name }}</h1>
            <div class="mt-4 sm:mt-0 flex items-center gap-4">
                @if(!empty($priceOptions))
                <div class="flex items-center gap-3">
                    <select wire:model.live="selectedPriceOptionId" class="appearance-none bg-white border border-gray-200 rounded-2xl px-4 py-2.5 shadow-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($priceOptions as $opt)
                            <option value="{{ $opt['value'] }}">{{ $opt['option'] }}</option>
                        @endforeach
                    </select>
                    <div class="text-2xl sm:text-3xl font-extrabold text-gray-900"><span class="text-base font-semibold tracking-wide mr-2">SGD</span>${{ number_format((float)($selectedPrice ?? 0), 0) }}<span class="text-xl font-bold text-gray-700">/day</span></div>
                </div>
                @endif
                <button wire:click="openBooking" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-full shadow-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Book Now
                </button>
            </div>
        </div>
    </x-slot>

    <!-- Standalone search just above the first section, aligned to the same container paddings -->
    <!--<div class="max-w-lg ml-auto mr-4 sm:mr-6 lg:mr-8 mt-10 sm:mt-14 mb-6 sm:mb-8">
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
    </div>-->

    <!-- Room Details Content -->
    <div class="ml-[20px] mr-[20px]">
    @php
        $hasHighlights = !empty(trim($roomType->room_highlights ?? ''));
        $hasTerms = !empty(trim($roomType->room_terms_and_conditions ?? ''));
    @endphp
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ activeTab: 'overview' }">
        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200 mb-8" style="padding-left: 30px; padding-right: 30px;">
            <nav class="-mb-px flex space-x-8" >
                <button 
                    @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-lg"
                >
                    Overview
                </button>
                @if($hasHighlights)
                <button 
                    @click="activeTab = 'highlights'"
                    :class="activeTab === 'highlights' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-lg"
                >
                    Highlights
                </button>
                @endif
                @if($hasTerms)
                <button 
                    @click="activeTab = 'terms'"
                    :class="activeTab === 'terms' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-lg"
                >
                    Terms & Conditions
                </button>
                @endif
            </nav>
        </div>

        <!-- Tab Content -->
        <!-- Overview Tab -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8" style="padding-left: 30px; padding-right: 30px;" x-show="activeTab === 'overview'" x-transition>
            <!-- Left Column - Text Content -->
            <div class="space-y-6">
                <div class="prose max-w-none">
                    <!--<h3 class="text-lg font-semibold text-gray-900 mb-4 ">Room Description</h3>-->
                    <div class="text-gray-700 leading-10 text-lg">
                        {!! nl2br(e($roomType->room_description)) !!}
                    </div>
                    @if(!empty($roomType->room_attributes) && is_array($roomType->room_attributes) && count($roomType->room_attributes) > 0)
                    <div class="mt-8">
                        <h4 class="text-xl font-semibold text-gray-900 mb-4">Room Attributes</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($roomType->room_attributes as $attribute)
                                @if(!empty($attribute))
                                <span class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-800 rounded-lg border border-blue-200 text-sm font-medium">{{ $attribute }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(!empty($roomType->room_amenities) && is_array($roomType->room_amenities) && count($roomType->room_amenities) > 0)
                    <div class="mt-6">
                        <h4 class="text-xl font-semibold text-gray-900 mb-4">Room Amenities</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($roomType->room_amenities as $amenity)
                                @if(!empty($amenity))
                                <span class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-800 rounded-lg border border-green-200 text-sm font-medium">{{ $amenity }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right Column - Images -->
            <div class="space-y-4" style="padding-left: 10px; padding-right: 30px;">
                @if($roomType->images && is_array($roomType->images) && count($roomType->images) > 0)
                    @php
                        $primaryImage = null;
                        $otherImages = [];
                        
                        // Find primary image and separate others
                        foreach($roomType->images as $image) {
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
                                $primaryImageHeight = 350; // h-80 = 320px
                                $gapBetweenImages = 22; // space-y-2 = 8px
                                $totalGapHeight = ($totalOtherImages - 1) * $gapBetweenImages;
                                $availableHeight = $primaryImageHeight - $totalGapHeight;
                                $individualImageHeight = $totalOtherImages > 0 ? $availableHeight / $totalOtherImages : 0;
                            @endphp
                            <div class="md:col-span-1 space-y-2">
                                @foreach($otherImages as $index => $image)
                                    <div style="height: {{ $individualImageHeight }}px; border-radius: 20px; overflow: hidden;" class="  cursor-pointer">
                                        <img 
                                            src="{{ $image['url'] ?? $image }}" 
                                            alt="{{ $roomType->name }} - Image {{ $index + 1 }}"
                                            class="w-full h-full object-cover rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
                                            @click="
                                                // Simple image modal functionality
                                                $dispatch('open-modal', {
                                                    type: 'image',
                                                    src: '{{ $image['url'] ?? $image }}',
                                                    alt: '{{ $roomType->name }} - Image {{ $index + 1 }}'
                                                })
                                            "
                                        >
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Primary Image (Right Column) -->
                        @if($primaryImage)
                            <div class="md:col-span-2" style="border-radius: 20px; overflow: hidden;">
                                <img 
                                    src="{{ $primaryImage['url'] ?? $primaryImage }}" 
                                    alt="{{ $roomType->name }} - Primary Image"
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

        <!-- Highlights Tab -->
        @if($hasHighlights)
        <div x-show="activeTab === 'highlights'" x-transition style="padding-left: 30px; padding-right: 30px;">
            <div class="prose max-w-none">
                <div class="text-gray-700 leading-10 text-lg">
                    {!! nl2br(e($roomType->room_highlights)) !!}
                </div>
            </div>
        </div>
        @endif

        <!-- Terms & Conditions Tab -->
        @if($hasTerms)
        <div x-show="activeTab === 'terms'" x-transition style="padding-left: 30px; padding-right: 30px;">
            <div class="prose max-w-none">
                <div class="text-gray-700 leading-10 text-lg">
                    {!! nl2br(e($roomType->room_terms_and_conditions)) !!}
                </div>
            </div>
        </div>
        @endif
    </div>
                    </div>

    <!-- You might also like section -->
    @if($relatedRooms && count($relatedRooms) > 0)
        <div class="mt-16 mb-8" x-data>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-3xl font-bold text-gray-900">You might also like</h2>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-gray-300 text-gray-600 hover:text-gray-900 hover:border-gray-400 bg-white shadow-sm"
                                wire:click="loadPreviousRelated"
                                @if($relatedOffset <= 0) disabled class="opacity-50 cursor-not-allowed" @endif
                                aria-label="Previous">
                            ‹
                        </button>
                        <button type="button"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-gray-300 text-gray-600 hover:text-gray-900 hover:border-gray-400 bg-white shadow-sm"
                                wire:click="loadNextRelated"
                                @if(!$hasMoreRelated) disabled class="opacity-50 cursor-not-allowed" @endif
                                aria-label="Next">
                            ›
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto no-scrollbar">
                    <div x-ref="relatedRail" class="flex gap-6 overflow-x-auto no-scrollbar scroll-smooth pb-2">
                        @foreach($relatedRooms as $relatedRoom)
                            @php($relatedSlug = $relatedRoom->slug ?? '#')
                            @php($relatedUrl = "/rooms/{$relatedSlug}")
                            <div class="w-64 flex-shrink-0">
                                <div class="group bg-white border rounded-[20px] overflow-hidden hover:shadow-lg transition-all duration-300 hover:border-blue-500 h-96">
                                    <!-- Room image -->
                                    <a href="{{ $relatedUrl }}" class="relative bg-gray-50 overflow-hidden flex items-center justify-center h-56 w-full">
                                        @if($relatedRoom->getPrimaryImageUrl())
                                            <img src="{{ $relatedRoom->getPrimaryImageUrl() }}" alt="{{ $relatedRoom->name }}" class="object-cover w-full h-full" />
                                            <!-- Price overlay -->
                                            @if($relatedRoom->getFormattedPrice())
                                                <div class="absolute bottom-6 left-3 bg-white bg-opacity-95 rounded-[20px] px-6 py-4 text-center shadow-sm">
                                                    <div class="text-sm font-semibold text-blue-600">From {{ $relatedRoom->getFormattedPrice() }}</div>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400 text-sm">No image</span>
                                        @endif  
                                    </a>

                                    <!-- Room info -->
                                    <div class="flex flex-col justify-between h-[180px] p-5">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900 line-clamp-2">{{ $relatedRoom->name }}</div>
                                            @if($relatedRoom->room_description)
                                                <div class="mt-2 text-xs text-gray-600 line-clamp-3">{{ \Illuminate\Support\Str::limit($relatedRoom->room_description, 150) }}</div>
                                            @endif
                                        </div>

                                        <!-- Actions -->
                                        <div class="mt-4 mb-4">
                                            <a href="{{ $relatedUrl }}" class="inline-flex items-center text-blue-600 text-sm font-medium hover:text-blue-700">
                                                More info →
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-room.page-wrapper>