<x-room.page-wrapper heroImage="/images/hero-room.png">
    <x-slot name="header">
        @section('meta_tags')
    @php
        $metaTitle = 'Rooms - ' . config('app.name');
        $metaDescription = 'Discover our amazing rooms in our online store. Book the perfect room for your pet.';
        $canonicalUrl = route('room.home');
    @endphp
    
    <x-seo.meta-tags 
        :title="$metaTitle"
        :description="$metaDescription"
        :canonicalUrl="$canonicalUrl"
        :type="'website'"
    />
@endsection
<!-- Only title in floating header with updated styling to match Figma -->        
 <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
             <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Rooms</h1>
             
             <!-- Species Filter Dropdown -->
             <div class="mt-4 lg:mt-0 flex gap-2">
                 <select 
                     wire:model.live="selectedSpecies" 
                     x-on:change="$wire.$refresh()"
                     class="rounded-xl border border-gray-200 bg-gray-50 focus:bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm min-w-[200px]"
                 >
                     <option value="">All Species</option>
                     @foreach($species as $id => $name)
                         <option value="{{ $id }}">For {{ $name }}</option>
                     @endforeach
                 </select>
                 
                 
             </div>
        </div>
    </x-slot>

    <!-- Standalone search just above the first section, aligned to the same container paddings -->
    <!--<div class="max-w-lg ml-auto  sm:mr-6 lg:mr-8 mt-10 sm:mt-14 mb-6 sm:mb-8">
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

     <!-- Room row: horizontal scroll on small screens, grid on lg -->
            <div class="overflow-x-auto lg:overflow-visible" x-ref="r">
                              
                <div class="flex gap-4 lg:grid lg:grid-cols-4 lg:gap-4 min-w-max lg:min-w-0 items-stretch" wire:key="room-types-{{ $selectedSpecies ?? 'all' }}-{{ $forceUpdate }}">
                    @if($roomTypes->isEmpty())
                        <div class="col-span-4 w-full">
                            <div class="flex items-center justify-center py-16 bg-white border border-dashed border-gray-300 rounded-xl text-gray-600">
                                No rooms available for this species.
                            </div>
                        </div>
                    @else
                        @foreach($roomTypes as $roomType)
                            @php($slug = $roomType->slug ?? '#')
                            @php($url="/rooms/{$slug}")
                            <div class="w-56 lg:w-auto flex-shrink-0 h-[420px]">
                                <x-room.room-card
                                    :url="$url"
                                    :image="$roomType->getPrimaryImageUrl()"
                                    :name="$roomType->name ?? ''"
                                    :price="$roomType->getFormattedPrice()"
                                    :description="$roomType->room_description ?? null"
                                />
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
</x-room.page-wrapper>