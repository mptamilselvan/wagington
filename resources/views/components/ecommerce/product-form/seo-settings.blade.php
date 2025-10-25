<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer" @click="open = !open">
        <h3 class="text-lg font-medium text-gray-900">SEO Settings</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
    
    <div x-show="open" class="px-6 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Meta Title -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title*</label>
                <input 
                    type="text" 
                    wire:model="meta_title"
                    placeholder="Enter meta title"
                    maxlength="60"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <div class="text-right text-xs text-gray-500 mt-1">
                    {{ strlen($meta_title) }}/60 characters
                </div>
                @error('meta_title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Meta Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description*</label>
                <textarea 
                    wire:model="meta_description"
                    placeholder="Type here"
                    rows="3"
                    maxlength="160"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                <div class="text-right text-xs text-gray-500 mt-1">
                    {{ strlen($meta_description) }}/160 characters
                </div>
                @error('meta_description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Meta Keywords -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Keywords*</label>
                <textarea 
                    wire:model="meta_keywords"
                    placeholder="Enter meta keywords"
                    rows="3"
                    maxlength="255"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                <div class="text-right text-xs text-gray-500 mt-1">
                    {{ strlen($meta_keywords) }}/255 characters
                </div>
                @error('meta_keywords') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Focus Keywords -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Focus Keywords</label>
                <textarea 
                    wire:model="focus_keywords"
                    placeholder="Enter focus keywords"
                    rows="3"
                    maxlength="255"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                <div class="text-right text-xs text-gray-500 mt-1">
                    {{ strlen($focus_keywords) }}/255 characters
                </div>
                @error('focus_keywords') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>