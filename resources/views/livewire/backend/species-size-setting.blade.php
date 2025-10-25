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
                'active' => 'species-size-settings',
                'subMenuType' => 'roomSettings',
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <!-- Species Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Species</label>
                <select wire:model.live="selectedSpeciesId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Choose a species...</option>
                    @foreach($species as $specie)
                        <option value="{{ $specie->id }}">{{ $specie->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($selectedSpeciesId)
                <!-- Size Management Section -->
                <div class="space-y-6">
                    <!-- Add New Size Form -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Size</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Size Name</label>
                                <input type="text" wire:model="newSize" placeholder="e.g., Small, Medium, Large" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @error('newSize') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                                <input type="color" wire:model="newColor" 
                                       class="w-20 h-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @error('newColor') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="mt-4">
                            @component('components.button-component', [
                                'label' => 'Add Size',
                                'id' => 'add_size',
                                'type' => 'buttonSmall',
                                'wireClickFn' => 'addSize',
                            ])
                            @endcomponent
                        </div>
                    </div>

                    <!-- Existing Sizes List -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Existing Sizes</h3>
                        @if($sizes->count() > 0)
                            <div class="space-y-3">
                                @foreach($sizes as $size)
                                    <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg">
                                        @if($editingSizeId == $size->id)
                                            <!-- Edit Mode -->
                                            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <input type="text" wire:model="newSize" 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                </div>
                                                <div>
                                                    <input type="color" wire:model="newColor" 
                                                           class="w-20 h-10 border border-gray-300 rounded-lg">
                                                </div>
                                            </div>
                                            <div class="ml-4 flex space-x-2">
                                                @component('components.button-component', [
                                                    'label' => 'Save',
                                                    'id' => 'save_size_' . $size->id,
                                                    'type' => 'buttonSmall',
                                                    'wireClickFn' => 'updateSize(' . $size->id . ')',
                                                ])
                                                @endcomponent
                                                @component('components.button-component', [
                                                    'label' => 'Cancel',
                                                    'id' => 'cancel_edit_' . $size->id,
                                                    'type' => 'cancelSmall',
                                                    'wireClickFn' => 'cancelEdit',
                                                ])
                                                @endcomponent
                                            </div>
                                        @else
                                            <!-- Display Mode -->
                                            <div class="flex-1 flex items-center space-x-4">
                                                <div class="flex items-center space-x-2">
                                                    @if($size->color)
                                                        <div class="w-4 h-4 rounded-full border border-gray-300" 
                                                             style="background-color: {{ $size->color }}"></div>
                                                    @endif
                                                    <span class="font-medium text-gray-900">{{ $size->size }}</span>
                                                </div>
                                            </div>
                                            <div class="flex space-x-2">
                                                @component('components.button-component', [
                                                    'label' => 'Edit',
                                                    'id' => 'edit_size_' . $size->id,
                                                    'type' => 'buttonSmall',
                                                    'wireClickFn' => 'editSize(' . $size->id . ')',
                                                ])
                                                @endcomponent
                                                <button wire:click="deleteSize({{ $size->id }})" 
                                                        onclick="return confirm('Are you sure you want to delete this size?')"
                                                        class="button text-white bg-[#D93D2E] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
                                                    Delete
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                No sizes found for this species. Add some sizes above.
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-12 text-gray-500">
                    <p class="text-lg">Please select a species to manage its sizes.</p>
                </div>
            @endif
        </div>
    </div>
</main>