<x-room.page-wrapper heroImage="/images/hero-room.png">
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
             <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Rooms</h1>
             
            @section('meta_tags')
            @endsection

            <select 
                     wire:model.live="selectedSpecies" 
                     x-on:change="$wire.$refresh()"
                     class="rounded-xl border border-gray-200 bg-gray-50 focus:bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm min-w-[200px]"
                 >
                     <option value="">All Species</option>
                     @foreach($species as $id => $name)
                         <option value="{{ $id }}">{{ $name }}</option>
                     @endforeach
                 </select>

            @foreach($roomTypes as $roomType)
                <div class="w-56 lg:w-auto flex-shrink-0">
                    <x-room.room-card
                        :url="$roomType->url"
                        :image="$roomType->image"
                        :name="$roomType->name"
                        :price="$roomType->price"
                        :description="$roomType->description"
                    />
                </div>
            @endforeach

             <button 
                     wire:click="testFilter" 
                     class="px-4 py-2 bg-green-500 text-white rounded-xl text-sm"
                 >
                     Test
                 </button>
                 <div class="text-xs text-gray-500 mb-2">
                    Debug: Selected Species: {{ $selectedSpecies ?? 'null' }}, 
                    Manual Count: {{ count($roomTypes ?? []) }},
                    Force Update: {{ $forceUpdate }}
                </div>
</div>
</x-room.page-wrapper>