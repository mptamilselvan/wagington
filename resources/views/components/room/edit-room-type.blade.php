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
                <label class="block mb-2 text-sm font-normal text-gray-700">Room Type Name*</label>
                <input 
                    type="text" 
                    wire:model.live="name"
                    placeholder="Enter room type name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('name') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Slug -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Slug*</label>
                <input 
                    type="text" 
                    wire:model.live="slug"
                    placeholder="Enter slug"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed"
                    readonly
                >
                @error('slug') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <!-- Species -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Species*</label>
                <select wire:model.live="species_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select</option>
                    @foreach($species as $specie)
                        <option value="{{ $specie->id }}">{{ $specie->name }}</option>
                    @endforeach
                </select>
                @error('species_id') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

       
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <!-- Room Attributes -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Room Attributes*</label>
                <div class="space-y-3">
                    <!-- Input for adding new attributes -->
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            wire:model="newAttribute"
                            wire:keydown.enter.prevent="addAttribute"
                            placeholder="Enter room attribute and press Enter"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        <button 
                            type="button"
                            wire:click="addAttribute"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Add
                        </button>
                    </div>
                    
                    <!-- Display existing attributes as tags -->
                    @if(!empty($room_attributes) && is_array($room_attributes))
                        <div class="flex flex-wrap gap-2">
                            @foreach($room_attributes as $index => $attribute)
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 text-blue-800 rounded-lg border border-blue-200" wire:key="attribute-{{ $index }}">
                                    <span class="text-sm font-medium">{{ $attribute }}</span>
                                    <button 
                                        type="button"
                                        wire:click="removeAttribute({{ $index }})"
                                        class="text-blue-600 hover:text-blue-800 focus:outline-none"
                                        title="Remove attribute"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                @error('room_attributes') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Room Amenities -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Room Amenities</label>
                <div class="space-y-3">
                    <!-- Input for adding new amenities -->
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            wire:model="newAmenity"
                            wire:keydown.enter.prevent="addAmenity"
                            placeholder="Enter room amenity and press Enter"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        <button 
                            type="button"
                            wire:click="addAmenity"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Add
                        </button>
                    </div>
                    
                    <!-- Display existing amenities as tags -->
                    @if(!empty($room_amenities) && is_array($room_amenities))
                        <div class="flex flex-wrap gap-2">
                            @foreach($room_amenities as $index => $amenity)
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-100 text-green-800 rounded-lg border border-green-200" wire:key="amenity-{{ $index }}">
                                    <span class="text-sm font-medium">{{ $amenity }}</span>
                                    <button 
                                        type="button"
                                        wire:click="removeAmenity({{ $index }})"
                                        class="text-green-600 hover:text-green-800 focus:outline-none"
                                        title="Remove amenity"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                @error('room_amenities') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <!-- Description -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Description*</label>
                <textarea 
                    wire:model="room_description"
                    placeholder="Enter room description"
                    class="w-full px-3 py-2 h-40 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                @error('room_description') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Overview -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Overview*</label>
                <textarea 
                    wire:model="room_overview"
                    placeholder="Enter room overview"
                    class="w-full px-3 py-2 h-40 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                @error('room_overview') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <!-- Highlights -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Highlights</label>
                <textarea 
                    wire:model="room_highlights"
                    placeholder="Enter room highlights"
                    class="w-full px-3 py-2 h-40 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                @error('room_highlights') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Terms and Conditions -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Terms and Conditions</label>
                <textarea 
                    wire:model="room_terms_and_conditions"
                    placeholder="Enter room terms and conditions"
                    class="w-full px-3 py-2 h-40 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                @error('room_terms_and_conditions') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <!-- Images -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Images (upto 4 images) *</label>
                <div class="relative">
                    <input 
                        type="file" 
                        wire:model="images"
                        multiple
                        accept="image/*"
                        id="image-upload"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    >
                    <label for="image-upload" class="flex items-center justify-between w-full h-11 px-4 border-2 border-dashed border-blue-300 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-400 transition-colors">
                        <span class="text-sm font-medium text-blue-600">Add File</span>
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </label>
                </div>
                @error('images') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                
                <!-- Existing Images -->
                @if(isset($existingImages) && is_array($existingImages) && count($existingImages) > 0)
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Current Images:</h4>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach($existingImages as $index => $image)
                                @php
                                    if(!empty($image) && is_array($image) && isset($image['url'])) {
                                        $imageUrl = $image['url'];
                                    } else {
                                        $imageUrl = null;
                                        continue;
                                    }
                                @endphp
                                <div class="relative group" wire:key="existing-image-{{ $index }}">
                                    <img 
                                        src="{{ $imageUrl }}" 
                                        alt="Existing Image {{ $index + 1 }}"
                                        class="w-full h-32 object-cover rounded-lg border border-gray-200"
                                    >
                                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 rounded-b-lg">
                                        Existing Image {{ $index + 1 }}
                                    </div>
                                    <!-- Primary Checkbox for Existing Images -->
                                    <div class="absolute top-2 left-2">
                                        <label class="flex items-center space-x-1 bg-white bg-opacity-90 rounded px-2 py-1">
                                            <input 
                                                type="checkbox" 
                                                wire:model="existingImagePrimary.{{ $index }}"
                                                class="w-3 h-3 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                            >
                                            <span class="text-xs text-gray-700 font-medium">Primary</span>
                                        </label>
                                    </div>
                                    
                                    <!-- Close/Remove Button for Existing Images -->
                                    <button 
                                        type="button"
                                        wire:click="removeExistingImage({{ $index }})"
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors"
                                        title="Remove existing image"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <!-- New Image Previews -->
                @if(isset($images) && is_array($images) && count($images) > 0)
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">New Images:</h4>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach($images as $index => $image)
                                <div class="relative group" wire:key="new-image-{{ $index }}">
                                    <img 
                                        src="{{ $image->temporaryUrl() }}" 
                                        alt="New Image {{ $index + 1 }}"
                                        class="w-full h-32 object-cover rounded-lg border border-gray-200"
                                    >
                                    <button 
                                        type="button"
                                        wire:click="removeImage({{ $index }})"
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors"
                                        title="Remove image"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 rounded-b-lg">
                                        New Image {{ $index + 1 }}
                                    </div>
                                    <!-- Primary Checkbox for New Images -->
                                    <div class="absolute top-2 left-2">
                                        <label class="flex items-center space-x-1 bg-white bg-opacity-90 rounded px-2 py-1">
                                            <input 
                                                type="checkbox" 
                                                wire:model="newImagePrimary.{{ $index }}"
                                                class="w-3 h-3 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                            >
                                            <span class="text-xs text-gray-700 font-medium">Primary</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Service Addons</label>
                <select wire:model.live="service_addons" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select</option>
                    <option value="1">Addon1</option>
                    @foreach($serviceAddons as $serviceAddon)
                        <option value="{{ $serviceAddon->id }}">{{ $serviceAddon->name }}</option>
                    @endforeach
                </select>
                @error('service_addons') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Agreed Terms Section -->
        <div class="flex items-center justify-between px-2 py-4">
            <h3 class="text-lg font-medium text-gray-900">Agreed Terms</h3>
            <button 
                type="button" 
                wire:click="addAgreedTerm"
                class="px-4 py-2 text-blue-600 hover:text-blue-800 border border-blue-300 rounded-lg hover:bg-blue-50 flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add more
            </button>
        </div>
        
        @foreach($aggreed_terms as $index => $term)
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2" wire:key="agreed-term-{{ $index }}">
            <!-- Content -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Content</label>
                <input 
                    type="text" 
                    wire:model.live="aggreed_terms.{{ $index }}.content"
                    placeholder="Enter content"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('aggreed_terms.'.$index.'.content') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Add document (pdf)</label>
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <input 
                            type="file" 
                            wire:model="aggreed_terms.{{ $index }}.document"
                            accept="application/pdf"
                            id="document-upload-{{ $index }}"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        >
                        <label for="document-upload-{{ $index }}" class="flex items-center justify-between w-full h-11 px-4 border-2 border-dashed border-blue-300 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-400 transition-colors">
                            <span class="text-sm font-medium text-blue-600">Add File</span>
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </label>
                    </div>
                    @if(isset($aggreed_terms) && is_array($aggreed_terms) && count($aggreed_terms) > 1)
                        <button 
                            type="button" 
                            wire:click="removeAgreedTerm({{ $index }})"
                            class="px-3 py-2 text-red-600 hover:text-red-800 border border-red-300 rounded-lg hover:bg-red-50"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    @endif
                </div>
                
                <!-- Document Preview -->
                @if(isset($term['document_url']) && $term['document_url'])
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg border">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm text-gray-700">Existing PDF Document</span>
                            </div>
                            <button 
                                type="button"
                                wire:click="removeDocument({{ $index }})"
                                class="text-red-500 hover:text-red-700 p-1"
                                title="Remove document"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mt-2">
                            <a 
                                href="{{ $term['document_url'] }}" 
                                target="_blank"
                                class="text-blue-600 hover:text-blue-800 text-sm underline"
                            >
                                View Existing Document
                            </a>
                        </div>
                        <!-- Hidden input to pass document_url to validation -->
                        <input type="hidden" wire:model="aggreed_terms.{{ $index }}.document_url" value="{{ $term['document_url'] }}">
                    </div>
                @endif
                
                <!-- New Document Preview -->
                @if(isset($documentPreviews[$index]) && $documentPreviews[$index])
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg border">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm text-gray-700">PDF Document</span>
                            </div>
                            <button 
                                type="button"
                                wire:click="removeDocument({{ $index }})"
                                class="text-red-500 hover:text-red-700 p-1"
                                title="Remove document"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mt-2">
                            <a 
                                href="{{ $documentPreviews[$index] }}" 
                                target="_blank"
                                class="text-blue-600 hover:text-blue-800 text-sm underline"
                            >
                                View New Document
                            </a>
                        </div>
                    </div>
                @endif
                
                @error('aggreed_terms.'.$index.'.document') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>
        @endforeach

        <!-- Price Options Section -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between px-2 py-4 border-b border-gray-200 mb-6">
                <h3 class="text-lg font-medium text-gray-900">Price Options</h3>
                <button 
                    type="button" 
                    wire:click="addPriceOption"
                    class="px-4 py-2 text-blue-600 hover:text-blue-800 border border-blue-300 rounded-lg hover:bg-blue-50 flex items-center gap-2 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add more
                </button>
            </div>
            
            @foreach($price_options as $index => $option)
            <div class="grid grid-cols-4 gap-6 mb-6 md:grid-cols-4" wire:key="price-option-{{ $index }}">
                <!-- Label -->
                <div>
                    <label class="block mb-2 text-sm font-normal text-gray-700">Label*</label>
                    <input 
                        type="text" 
                        wire:model.live="price_options.{{ $index }}.label"
                        placeholder="Enter label"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('price_options.'.$index.'.label') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <!-- No. of days -->
                <div>
                    <label class="block mb-2 text-sm font-normal text-gray-700">No. of days*</label>
                    <input 
                        type="text" 
                        wire:model.live="price_options.{{ $index }}.no_of_days"
                        placeholder="Enter no. of days"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('price_options.'.$index.'.no_of_days') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <!-- Price -->
                <div>
                    <label class="block mb-2 text-sm font-normal text-gray-700">Price (SGD)*</label>
                    <input 
                        type="text" 
                        wire:model.live="price_options.{{ $index }}.price"
                        placeholder="Enter price"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    @error('price_options.'.$index.'.price') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <!-- Remove button -->
                <div>
                    <label class="block mb-2 text-sm font-normal text-gray-700">&nbsp;</label>
                    @if(isset($price_options) && is_array($price_options) && count($price_options) > 1)
                        <button 
                            type="button" 
                            wire:click="removePriceOption({{ $index }})"
                            class="px-3 py-2 text-red-600 hover:text-red-800 border border-red-300 rounded-lg hover:bg-red-50 transition-colors"
                            title="Remove this price option"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

         <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">default_clean_minutes</label>
                <input 
                    type="text" 
                    wire:model="default_clean_minutes"
                    placeholder="Enter default clean minutes"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('default_clean_minutes') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Room Amenities -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">turnover_buffer_min</label>
                <input 
                    type="text" 
                    wire:model="turnover_buffer_min"
                    placeholder="Enter turnover buffer minutes"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('turnover_buffer_min') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

         <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <!-- Highlights -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">SEO Title</label>
                <textarea 
                    wire:model="seo_title"
                    placeholder="Enter SEO title"
                    class="w-full px-3 py-2 h-40 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                @error('seo_title') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Terms and Conditions -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">SEO Description</label>
                <textarea 
                    wire:model="seo_description"
                    placeholder="Enter SEO description"
                    class="w-full px-3 py-2 h-40 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                @error('seo_description') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Evaluation Required -->
        <div class="grid grid-cols-2 gap-6 mb-6 md:grid-cols-2">

            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">SEO Keywords</label>
                <textarea 
                    wire:model="seo_keywords"
                    placeholder="Enter SEO keywords"
                    class="w-full px-3 py-2 h-40 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                @error('seo_keywords') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Evaluation Required</label>
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        wire:model="evaluation_required"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    >
                    <span class="ml-2 text-sm text-gray-700">Does this service require temperament evaluation of the pet?</span>
                </label>
                @error('evaluation_required') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
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
                wire:click="updateRoomType"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
                Update Room Type
            </button>
        </div>
    </div>
</div>
