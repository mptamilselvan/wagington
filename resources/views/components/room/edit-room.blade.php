<div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm" x-data="{ open: true }">
    <div x-show="open" class="px-6 py-6">
        <!-- Error Messages -->
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-red-800 mb-2">Please fix the following errors:</h3>
                        <ul class="list-disc list-inside text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        
        @error('update')
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-red-800 font-medium">{{ $message }}</span>
                </div>
            </div>
        @enderror
        <!-- Product Name and Slug - Same Row -->
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <!-- Product Name -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Room Name*</label>
                <input 
                    type="text" 
                    wire:model.live="name"
                    placeholder="Enter room name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('name') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Species -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Room Type*</label>
                <select wire:model.live="room_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select</option>
                    @foreach($roomTypes as $roomType)
                        <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
                    @endforeach
                </select>
                @error('room_type_id') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

               
        
        
        

       

         <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">CCTV Stream*</label>
                <input 
                    type="text" 
                    wire:model="cctv_stream"
                    placeholder="Enter cctv stream"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('cctv_stream') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Status -->
        <div class="mb-6">
            <label class="block mb-3 text-sm font-normal text-gray-700">Status*</label>
            <div class="flex gap-3 max-w-2xl">
                @foreach(config('room.status_options') as $value => $label)
                    <label class="relative flex items-center px-5 py-2 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 transition-colors {{ $status === $value ? 'border-blue-500 bg-blue-50' : '' }}">
                        <input 
                            type="radio" 
                            wire:model.live="status" 
                            value="{{ $value }}" 
                            class="sr-only"
                        >
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-4 h-4 border-2 border-gray-300 rounded-full flex items-center justify-center {{ $status === $value ? 'border-blue-500' : '' }}">
                                    @if($status === $value)
                                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    @endif
                                </div>
                            </div>
                            <div class="ml-2">
                                <div class="text-sm font-medium text-gray-900">{{ $label }}</div>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('status') <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <!-- Save Button -->
        <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
            <button 
                type="button" 
                wire:click="closeForm"
                class="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
                Cancel
            </button>
            <button 
                type="button" 
                wire:click="updateRoom"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
                Update Room
            </button>
        </div>
    </div>
</div>
