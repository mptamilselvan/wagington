<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer" @click="open = !open">
        <h3 class="text-lg font-medium text-gray-900">Product Add-ons</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
    
    <div x-show="open" class="px-6 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Select Add-ons -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Add-ons</label>
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live="addonSearch"
                        placeholder="Search product name"
                        class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Available Add-ons List -->
                <div class="mt-4 max-h-60 overflow-y-auto border border-gray-200 rounded-lg">
                    @foreach($availableAddons as $addon)
                        @if(empty($addonSearch) || str_contains(strtolower($addon->name), strtolower($addonSearch)))
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                                <div class="flex items-center">
                                    @php
                                        $addonImage = $addon->getPrimaryImage();
                                    @endphp
                                    <div class="w-8 h-8 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center mr-3">
                                        @if($addonImage)
                                            <img src="{{ $addonImage->file_url }}" alt="{{ $addon->name }}" class="w-8 h-8 object-cover">
                                        @else
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $addon->name }}</div>
                                        <div class="text-xs text-gray-500">${{ number_format($addon->variants->first()->selling_price ?? 0, 2) }}</div>
                                    </div>
                                </div>
                                <button 
                                    type="button"
                                    wire:click="addAddon({{ $addon->id }})"
                                    class="text-blue-600 hover:text-blue-800"
                                    @if(in_array($addon->id, $selectedAddons)) disabled @endif
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Selected Add-ons -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Selected Add-ons</label>
                
                <div class="space-y-2" x-data="{ draggingId: null, startDrag(id){ this.draggingId = id }, onDrop(targetId){ if(!this.draggingId) return; if (targetId === this.draggingId) { this.draggingId = null; return; } const rows = Array.from($el.querySelectorAll('[data-addon-id]')); let ids = rows.map(r => Number(r.dataset.addonId)); // build new order: remove dragged id, then insert before target
                    ids = ids.filter(id => id !== this.draggingId);
                    const to = ids.indexOf(targetId);
                    if (to === -1) { ids.push(this.draggingId); } else { ids.splice(to, 0, this.draggingId); }
                    $wire.reorderSelectedAddons(ids);
                    this.draggingId = null; } }">
                    <div class="flex justify-between text-xs font-medium text-gray-500 px-3">
                        <span>Name</span>
                        <span>Price</span>
                        <span>Required</span>
                        <span></span>
                    </div>

                    @if(!empty($selectedAddons))
                        @foreach($selectedAddons as $index => $addonId)
                            @php $addon = $availableAddons->find($addonId) @endphp
                            @if($addon)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg" data-addon-id="{{ $addonId }}" draggable="true" @dragstart="startDrag({{ $addonId }})" @dragover.prevent @drop="onDrop({{ $addonId }})" wire:key="selected-addon-{{ $addonId }}">
                                    <div class="flex items-center">
                                        <span class="mr-2 cursor-move select-none" title="Drag to reorder">⋮⋮</span>
                                        @php
                                            $addonImageSel = $addon->getPrimaryImage();
                                        @endphp
                                        <div class="w-8 h-8 rounded-full overflow-hidden bg-blue-100 flex items-center justify-center mr-3">
                                            @if($addonImageSel)
                                                <img src="{{ $addonImageSel->file_url }}" alt="{{ $addon->name }}" class="w-8 h-8 object-cover">
                                            @else
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $addon->name }}</div>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        ${{ number_format($addon->variants->first()->selling_price ?? 0, 2) }}
                                    </div>
                                    <div>
                                        <input 
                                            type="checkbox" 
                                            wire:model="addonRequired.{{ $addonId }}"
                                            @checked(filter_var($addonRequired[$addonId] ?? false, FILTER_VALIDATE_BOOLEAN))
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                    </div>
                                    <button 
                                        type="button"
                                        wire:click="removeAddon({{ $index }})"
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414L7.586 12l-1.293 1.293a1 1 0 101.414 1.414L9 13.414l2.293 2.293a1 1 0 001.414-1.414L11.414 12l1.293-1.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <p class="mt-2 text-sm">No add-ons selected</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>