<div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm" x-data="{ open: true }">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 cursor-pointer" @click="open = !open">
        <h3 class="text-lg font-medium text-gray-900">General Information</h3>
        <svg class="w-5 h-5 text-gray-500 transition-transform transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
    
    <div x-show="open" class="px-6 py-6">
        <!-- Product Type - Full Width (spans both columns) -->
        <div class="mb-6">
            <label class="block mb-2 text-sm font-normal text-gray-700">Product Type</label>
            <select wire:model.live="product_type" wire:change="skuDriversChanged" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="regular">Single Product</option>
                <option value="variant">Variant Product</option>
                <option value="addon">Add-on Product</option>
            </select>
            @error('product_type') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
        </div>

        <!-- Product Name and Slug - Same Row -->
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <!-- Product Name -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Product Name*</label>
                <input 
                    type="text" 
                    wire:model.live="name"
                    wire:input.debounce.300ms="skuDriversChanged"
                    placeholder="Enter product name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                @error('name') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Slug -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Slug</label>
                <input 
                    type="text" 
                    wire:model="slug"
                    placeholder="Auto-generated from product name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50"
                    readonly
                >
                @error('slug') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- LEFT COLUMN (Section 1) -->

            <!-- Category (Parent) -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Category*</label>
                <select wire:model.live="parent_category_id" wire:change="skuDriversChanged" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select</option>
                    @foreach($categories->where('parent_id', null) as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                    @endforeach
                </select>
                @error('parent_category_id') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Subcategories (multi-select) -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Subcategories</label>
                @if($parent_category_id && $this->availableSubcategories->count() > 0)
                    <select wire:model.live="subcategory_ids" multiple size="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @foreach($this->availableSubcategories as $child)
                            <option value="{{ $child->id }}">{{ $child->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple subcategories</p>
                @elseif($parent_category_id)
                    <div class="w-full px-3 py-2 text-sm text-gray-500 border border-gray-300 rounded-lg bg-gray-50">
                        Don't have subcategory
                    </div>
                    <p class="mt-1 text-xs text-gray-500">This category doesn't have any subcategories</p>
                @else
                    <div class="w-full px-3 py-2 text-sm text-gray-500 border border-gray-300 rounded-lg bg-gray-50">
                        Please select a category first
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Select a category to view available subcategories</p>
                @endif
                @error('subcategory_ids') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                
                <!-- Selected Subcategories Display (like tags) -->
                @if(!empty($subcategory_ids))
                    <div class="flex flex-wrap gap-2 mt-2">
                        @php
                            $selectedSubcategories = collect($subcategory_ids)->map(fn($id) => $categories->firstWhere('id', $id))->filter();
                            $primarySubcategoryId = $selectedSubcategories->pluck('id')->first();
                        @endphp
                        @foreach($selectedSubcategories as $subcategory)
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-800 bg-blue-100 rounded-full">
                                {{ $subcategory->name }}
                                @if($subcategory->id === $primarySubcategoryId)
                                    <span class="ml-1 text-xs text-blue-600">(Primary)</span>
                                @endif
                                <button type="button" wire:click="removeSubcategory({{ $subcategory->id }})" class="ml-1 text-blue-600 hover:text-blue-800">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Tags -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Tags</label>
                
                <!-- Tags Dropdown -->
                <div class="relative">
                    <select wire:model.live="selectedTags" multiple class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" size="4">
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" @if(in_array($tag->id, $selectedTags)) selected @endif>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple tags</p>
                </div>
                
                <!-- Selected Tags Display -->
                @if(!empty($selectedTags))
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach($selectedTags as $tagId)
                            @php $tag = $tags->find($tagId) @endphp
                            @if($tag)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                    {{ $tag->name }}
                                    <button type="button" wire:click="removeTag({{ $tagId }})" class="ml-1 text-green-600 hover:text-green-800">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Short Description -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Short Description (160 characters)</label>
                <textarea 
                    wire:model="short_description"
                    placeholder="Type here"
                    rows="3"
                    maxlength="160"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                <div class="mt-1 text-xs text-right text-gray-500">
                    {{ strlen($short_description) }}/160 characters
                </div>
                @error('short_description') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Long Description -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Long Description (750 characters)</label>
                <textarea 
                    wire:model="description"
                    placeholder="Type here"
                    rows="6"
                    maxlength="750"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                <div class="mt-1 text-xs text-right text-gray-500">
                    {{ strlen($description) }}/750 characters
                </div>
                @error('description') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Images - General images uploader (exclude for add-on products) -->
            @if($product_type !== 'addon')
            <div id="general-images">
                @include('components.ecommerce.image-uploader', [
                    'model' => 'images',
                    'existing' => $existingImages,
                    'label' => 'Images*',
                    'multiple' => true,
                    'limit' => 'upto 5 images'
                ])
            </div>
            @endif
        </div>


    </div>
</div>