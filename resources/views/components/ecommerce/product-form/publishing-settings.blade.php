<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <button
        type="button"
        class="w-full px-6 py-4 border-b border-gray-200 flex justify-between items-center text-left cursor-pointer"
        @click="open = !open"
        :aria-expanded="open.toString()"
        aria-controls="publishing-settings-content"
    >
        <h3 class="text-lg font-medium text-gray-900">Publishing Settings</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    
    <div x-show="open" id="publishing-settings-content" class="px-6 py-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" wire:model="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
                @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Featured -->
            <div class="flex items-center">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        wire:model="featured"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    >
                    <span class="ml-2 text-sm text-gray-700">Featured Product</span>
                </label>
            </div>

            <!-- Shippable -->
            <div class="flex items-center">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        wire:model="shippable"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    >
                    <span class="ml-2 text-sm text-gray-700">Shippable Product</span>
                </label>
            </div>
        </div>
    </div>
</div>