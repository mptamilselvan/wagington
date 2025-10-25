<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer" @click="open = !open">
        <h3 class="text-lg font-medium text-gray-900">Shipping and Delivery</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
    
    <div x-show="open" class="px-6 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Length -->
            <div>
                <!-- Length -->
                <div>
                    <label for="length_cm" class="block text-sm font-medium text-gray-700 mb-2">Length (cm)</label>
                    <input
                        id="length_cm"
                        type="number"
                        wire:model="length_cm"
                        placeholder="Enter"
                        min="0"
                        step="0.01"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('length_cm') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Width -->
                <div>
                    <label for="width_cm" class="block text-sm font-medium text-gray-700 mb-2">Width (cm)</label>
                    <input
                        id="width_cm"
                        type="number"
                        wire:model="width_cm"
                        placeholder="Enter"
                        min="0"
                        step="0.01"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('width_cm') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Height -->
                <div>
                    <label for="height_cm" class="block text-sm font-medium text-gray-700 mb-2">Height (cm)</label>
                    <input
                        id="height_cm"
                        type="number"
                        wire:model="height_cm"
                        placeholder="Enter"
                        min="0"
                        step="0.01"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('height_cm') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Weight -->
                <div>
                    <label for="weight_kg" class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                    <input
                        id="weight_kg"
                        type="number"
                        wire:model="weight_kg"
                        placeholder="Enter"
                        min="0"
                        step="0.001"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('weight_kg') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>
</div>