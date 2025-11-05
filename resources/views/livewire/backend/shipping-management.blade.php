<div class="min-h-screen bg-gray-50 lg:ml-72">
    <!-- Header -->
    <div class="px-4 sm:px-6 py-2 mt-3">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-4 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Shipping Management</h1>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    @if($showList)
                        <!-- Search -->
                        <div class="relative">
                            <input type="text" wire:model.live="search" class="w-full sm:w-80 px-4 py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" placeholder="Search by region">
                            <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <!-- Add Button -->
                        <button wire:click="showAddForm" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-medium">
                            Add Shipping Rate
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($showList)
        <!-- Shipping Rates Table -->
        <div class="px-4 sm:px-6 pb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
                <!-- Mobile view -->
                <div class="block sm:hidden">
                    @forelse($shippingRates as $rate)
                        <div class="border-b border-gray-200 p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $rate->region }}</p>
                                    <p class="text-sm text-gray-500">${{ number_format($rate->cost, 2) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Weight: {{ $rate->weight_min ?? '0' }} - {{ $rate->weight_max ?? '∞' }} kg</p>
                                    <p class="text-sm text-gray-500">Volume: {{ $rate->volume_min ?? '0' }} - {{ $rate->volume_max ?? '∞' }} cm³</p>
                                </div>
                            </div>
                            <div class="flex justify-end pt-2">
                                <div class="relative inline-block">
                                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                        <button id="menu-button-{{ $rate->id }}" @click="open = !open" type="button" aria-haspopup="true" x-bind:aria-expanded="open" aria-controls="menu-{{ $rate->id }}" class="flex items-center text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                            </svg>
                                        </button>

                                <div x-show="open" 
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="transform opacity-0 scale-95"
                                             x-transition:enter-end="transform opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="transform opacity-100 scale-100"
                                             x-transition:leave-end="transform opacity-0 scale-95"
                                             class="absolute right-0 z-50 mt-2 text-left origin-top-right bg-white rounded-md shadow-lg w-36 ring-1 ring-black ring-opacity-5 focus:outline-none"
                                    role="menu" 
                                    aria-orientation="vertical" 
                                    id="menu-{{ $rate->id }}"
                                    aria-labelledby="menu-button-{{ $rate->id }}"
                                             x-cloak
                                        >
                                            <div class="py-1" role="none">
                                                <button type="button" 
                                                        wire:click="showEditForm({{ $rate->id }})" 
                                                        @click="open = false"
                                                        class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                                        role="menuitem">Edit</button>
                                                <button type="button" 
                                                        wire:click="confirmDelete({{ $rate->id }})"
                                                        @click="open = false"
                                                        class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                                        role="menuitem">Delete</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-500">No shipping rates found.</div>
                    @endforelse
                </div>
                
                <!-- Desktop view -->
                <table class="hidden sm:table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Region</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weight Range (kg)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Volume Range (cm³)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($shippingRates as $rate)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $rate->region }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $rate->weight_min ?? '0' }} - {{ $rate->weight_max ?? '∞' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $rate->volume_min ?? '0' }} - {{ $rate->volume_max ?? '∞' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${{ number_format($rate->cost, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium relative">
                                    <div class="relative inline-block">
                                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                            <button id="menu-button-{{ $rate->id }}" @click="open = !open" type="button" aria-haspopup="true" x-bind:aria-expanded="open" aria-controls="menu-{{ $rate->id }}" class="flex items-center text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                </svg>
                                            </button>

                                    <div x-show="open" 
                                                 x-transition:enter="transition ease-out duration-100"
                                                 x-transition:enter-start="transform opacity-0 scale-95"
                                                 x-transition:enter-end="transform opacity-100 scale-100"
                                                 x-transition:leave="transition ease-in duration-75"
                                                 x-transition:leave-start="transform opacity-100 scale-100"
                                                 x-transition:leave-end="transform opacity-0 scale-95"
                                                 class="absolute right-0 z-50 mt-2 text-left origin-top-right bg-white rounded-md shadow-lg w-36 ring-1 ring-black ring-opacity-5 focus:outline-none"
                                        role="menu" 
                                        aria-orientation="vertical" 
                                        id="menu-{{ $rate->id }}"
                                        aria-labelledby="menu-button-{{ $rate->id }}"
                                                 x-cloak
                                            >
                                                <div class="py-1" role="none">
                                                    <button type="button" 
                                                            wire:click="showEditForm({{ $rate->id }})" 
                                                            @click="open = false"
                                                            class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                                            role="menuitem">Edit</button>
                                                    <button type="button" 
                                                            wire:click="confirmDelete({{ $rate->id }})"
                                                            @click="open = false"
                                                            class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 hover:text-gray-900" 
                                                            role="menuitem">Delete</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No shipping rates found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                <!-- Pagination -->
                @if($shippingRates->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $shippingRates->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($showForm)
        <!-- Add/Edit Form -->
        <div class="px-4 sm:px-6 pb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ $editingShippingRate ? 'Edit Shipping Rate' : 'Add New Shipping Rate' }}
                        </h2>
                        <button wire:click="showList" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <form wire:submit="save" class="p-6 space-y-6">
                    <!-- Region -->
                    <div>
                        <label for="region" class="block text-sm font-medium text-gray-700 mb-2">Region *</label>
                        <input type="text" id="region" wire:model="region" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="e.g., SG, US, EU, etc." required>
                        @error('region') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <!-- Weight Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="weight_min" class="block text-sm font-medium text-gray-700 mb-2">Weight Min (kg)</label>
                            <input type="number" id="weight_min" wire:model="weight_min" step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="0.00">
                            @error('weight_min') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="weight_max" class="block text-sm font-medium text-gray-700 mb-2">Weight Max (kg)</label>
                            <input type="number" id="weight_max" wire:model="weight_max" step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Leave empty for unlimited">
                            @error('weight_max') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Volume Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="volume_min" class="block text-sm font-medium text-gray-700 mb-2">Volume Min (cm³)</label>
                            <input type="number" id="volume_min" wire:model="volume_min" step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="0.00">
                            @error('volume_min') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="volume_max" class="block text-sm font-medium text-gray-700 mb-2">Volume Max (cm³)</label>
                            <input type="number" id="volume_max" wire:model="volume_max" step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Leave empty for unlimited">
                            @error('volume_max') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Cost -->
                    <div>
                        <label for="cost" class="block text-sm font-medium text-gray-700 mb-2">Shipping Cost *</label>
                        <input type="number" id="cost" wire:model="cost" step="0.01" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="0.00" required>
                        @error('cost') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button type="button" wire:click="showList" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            {{ $editingShippingRate ? 'Update' : 'Create' }} Shipping Rate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            wire:key="delete-modal"
            wire:click.self="cancelDelete"
            wire:keydown.escape="cancelDelete"
            tabindex="-1"
            x-data
            x-init="() => {
                // Focus first actionable element and trap focus inside modal
                const dialog = $el.querySelector('[role=dialog]');
                if (!dialog) return;
                const focusable = dialog.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                const nodes = Array.from(focusable);
                if (nodes.length) nodes[0].focus();
                dialog.addEventListener('keydown', (e) => {
                    if (e.key !== 'Tab') return;
                    const idx = nodes.indexOf(document.activeElement);
                    if (e.shiftKey) {
                        if (idx <= 0) { e.preventDefault(); nodes[nodes.length - 1].focus(); }
                    } else {
                        if (idx === nodes.length - 1) { e.preventDefault(); nodes[0].focus(); }
                    }
                });
            }"
        >
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title" tabindex="-1">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <h3 id="delete-modal-title" class="text-lg font-medium text-gray-900 mt-4">Delete Shipping Rate</h3>
                    <p class="text-sm text-gray-500 mt-2">Are you sure you want to delete this shipping rate? This action cannot be undone.</p>
                    <div class="mt-6 flex justify-center space-x-3">
                        <button wire:click="cancelDelete" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">Cancel</button>
                        <button wire:click="delete" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Success Modal -->
    @if($showSuccessModal)
        <div
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            wire:key="success-modal"
            wire:click.self="closeModal"
            wire:keydown.escape="closeModal"
            tabindex="-1"
            x-data
            x-init="() => {
                const dialog = $el.querySelector('[role=dialog]');
                if (!dialog) return;
                const focusable = dialog.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                const nodes = Array.from(focusable);
                if (nodes.length) nodes[0].focus();
                dialog.addEventListener('keydown', (e) => {
                    if (e.key !== 'Tab') return;
                    const idx = nodes.indexOf(document.activeElement);
                    if (e.shiftKey) {
                        if (idx <= 0) { e.preventDefault(); nodes[nodes.length - 1].focus(); }
                    } else {
                        if (idx === nodes.length - 1) { e.preventDefault(); nodes[0].focus(); }
                    }
                });
            }"
        >
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" role="dialog" aria-modal="true" aria-labelledby="success-modal-title" tabindex="-1">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 id="success-modal-title" class="text-lg font-medium text-gray-900 mt-4">Success!</h3>
                    <p class="text-sm text-gray-500 mt-2">{{ $successMessage }}</p>
                    <div class="mt-6">
                        <button wire:click="closeModal" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">OK</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>