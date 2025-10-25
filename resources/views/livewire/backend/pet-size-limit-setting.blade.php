<main class="py-10 lg:pl-72 bg-gray-50">

    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="h-20 md:flex md:items-center md:justify-between">
            <span class="text-xl font-medium text-gray-900 sm:truncate sm:tracking-tight">
                {{ $title }}</span>
        </div>
    </div>

    @if(session()->has('success'))
       <!-- <x-success-modal :title="'Successfully updated!'" :message="session('success')" :duration="3000" /> -->
    @endif

    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">

        <div class="w-1/5 p-4 bg-white">
            @component('components.subMenu', [
                'active' => 'pet-size-limit-settings',
                'subMenuType' => 'roomSettings',
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <!-- Room Type Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Room Type</label>
                <select wire:model.live="room_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Choose a room type...</option>
                    @foreach($roomTypes as $roomType)
                        <option value="{{ $roomType->id }}">{{ $roomType->name }} ({{ $roomType->species->name ?? 'No Species' }})</option>
                    @endforeach
                </select>
            </div>

            @if($room_type_id && count($sizes) > 0)
                <!-- Pet Size Limits Management Section -->
                <div class="space-y-6">
                    <!-- Species and Size Information -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Room Type: {{ $roomTypes->where('id', $room_type_id)->first()->name ?? 'Unknown' }}</h3>
                        <p class="text-sm text-gray-600">Species: {{ $species[0]->name ?? 'Unknown' }}</p>
                        <p class="text-sm text-gray-600">Available Sizes: {{ count($sizes) }}</p>
                    </div>

                    <!-- Pet Size Limits Interface -->
                    <div class="bg-white border border-gray-200 rounded-lg">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Set Pet Size Limits</h3>
                            <p class="text-sm text-gray-600 mt-1">Enter the maximum number of pets allowed for each size in this room type.</p>
                        </div>
                        
                        <div class="p-4">
                            <div class="space-y-4">
                                @foreach($sizes as $size)
                                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <div class="flex items-center space-x-4">
                                            @if($size->color)
                                                <div class="w-6 h-6 rounded-full border border-gray-300" 
                                                     style="background-color: {{ $size->color }}"></div>
                                            @endif
                                            <div>
                                                <span class="font-medium text-gray-900 text-lg">{{ $size->size }}</span>
                                                @if($size->description)
                                                    <p class="text-sm text-gray-500">{{ $size->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <label class="text-sm font-medium text-gray-700">Limit:</label>
                                            <input type="number" 
                                                   wire:model="sizeLimits.{{ $size->id }}" 
                                                   min="0" 
                                                   max="999"
                                                   placeholder="0"
                                                   class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center">
                                            <span class="text-sm text-gray-500">pets</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Save Button -->
                            <div class="mt-6 flex justify-end">
                                @component('components.button-component', [
                                    'label' => 'Save',
                                    'id' => 'save_pet_size_limits',
                                    'type' => 'buttonSmall',
                                    'wireClickFn' => 'savePetSizeLimits',
                                ])
                                @endcomponent
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($room_type_id && count($sizes) == 0)
                <div class="text-center py-12 text-gray-500">
                    <p class="text-lg">No sizes found for this species.</p>
                    <p class="text-sm mt-2">Please add sizes for the species in the Species Size Settings page first.</p>
                </div>
            @else
                <div class="text-center py-12 text-gray-500">
                    <p class="text-lg">Please select a room type to manage pet size limits.</p>
                </div>
            @endif
        </div>
    </div>
</main>