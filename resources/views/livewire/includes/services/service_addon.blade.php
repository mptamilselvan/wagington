<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <div class="px-6 py-4 border-b bg-gray-200 border-gray-200 flex justify-between items-center cursor-pointer" @click="open = !open">
        <h3 class="text-lg font-medium text-gray-900">Service Add-ons</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
    
    <div x-show="open" class="px-6 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Left Column - Available Add-ons --}}
            <div>
                <label class="font-semibold">Select Add-ons</label>

                <div class="w-full">
                    @component('components.search', [
                        'placeholder' => 'Search Service name',
                        'wireModel' => 'search',
                        'id' => 'search',
                        'debounce' => true,
                        'class' => 'w-full'
                    ])
                    @endcomponent
                </div>

                {{-- <input
                    type="text"
                    wire:model.debounce.300ms="search"
                    placeholder="Search Service name"
                    class="mt-2 w-full border rounded p-2"
                > --}}

                <div class="mt-3 border rounded-lg max-h-72 overflow-y-auto">
                    @foreach($availableServices as $service)
                        <div
                            class="flex items-center justify-between px-4 py-2 border-b hover:bg-blue-50 cursor-pointer"
                            wire:click="addAddon({{ $service['id'] }})"
                        >
                            <div class="flex items-center space-x-3">
                                @if($service['image'])
                                    <img src="{{ $service['image'] }}" class="w-10 h-10 rounded-full object-cover">
                                @endif
                                <span>{{ $service['title'] }}</span>
                            </div>
                            <span class="text-blue-500 font-medium">${{ number_format($service['price'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Right Column - Selected Add-ons --}}
            <div>
                <label class="font-semibold">Selected Add-ons</label>

                <div
                    x-data="{ draggingIndex: null }"
                    class="mt-3 border rounded-lg overflow-hidden divide-y"
                >
                    @foreach($selectedAddons as $index => $addon)
                        <div
                            x-on:dragstart="draggingIndex = {{ $index }}"
                            x-on:dragover.prevent
                            x-on:drop="if (draggingIndex !== null && draggingIndex !== {{ $index }}) {
                                $wire.call('reorder', draggingIndex, {{ $index }});
                                draggingIndex = null;
                            }"
                            draggable="true"
                            class="flex items-center justify-between px-4 py-3 bg-white hover:bg-gray-50"
                        >
                            <div class="flex items-center space-x-3">
                                <i class="fa-solid fa-grip-vertical text-gray-400"></i>
                                @if($addon['image'])
                                    <img src="{{ $addon['image'] }}" class="w-10 h-10 rounded-full object-cover">
                                @endif
                                <span>{{ $addon['title'] }}</span>
                            </div>

                            <div class="flex items-center space-x-4">
                                <span class="text-blue-500 font-medium">${{ number_format($addon['price'], 2) }}</span>

                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                        wire:click="toggleRequired({{ $index }})"
                                        {{ $addon['required'] ? 'checked' : '' }}
                                        class="sr-only peer">
                                    <div class="relative w-10 h-5 bg-gray-200 rounded-full peer peer-checked:bg-blue-500">
                                        <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                                    </div>
                                </label>

                                <button type="button" wire:click="removeAddon({{ $index }})" class="text-red-500 hover:text-red-700">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>