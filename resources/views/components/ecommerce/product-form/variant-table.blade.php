<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center cursor-pointer" @click="open = !open">
        <h3 class="text-lg font-medium text-gray-900">Variant Table</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
    
    <div x-show="open" class="px-6 py-6">
        <!-- Choose Variants -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Choose Variants (upto 3 variants)</label>
            <div class="flex flex-wrap gap-2 mb-2">
                @foreach($variantTypes as $type)
                    <button 
                        wire:click="toggleVariantType({{ $type->id }})"
                        class="px-3 py-1 rounded-full text-sm font-medium border transition-colors
                            @if(in_array($type->id, $selectedVariantTypes)) 
                                bg-blue-100 text-blue-800 border-blue-300
                            @else 
                                bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200
                            @endif"
                    >
                        {{ $type->name }}
                    </button>
                @endforeach
            </div>
            @error('selectedVariantTypes')
                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
            @enderror
            @error('variantCombinations')
                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Options Table -->
        @if(!empty($variantCombinations))
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-medium text-gray-900">Options Table</h4>
                    <button 
                        wire:click="addVariantCombination"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                    >
                        Add Option
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed" style="min-width: 1800px;">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barcode</th>
                                
                                <!-- Dynamic Variant Type Headers -->
                                @foreach($selectedVariantTypes as $typeId)
                                    @php
                                        $variantType = $variantTypes->find($typeId);
                                    @endphp
                                    @if($variantType)
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $variantType->name }}</th>
                                    @endif
                                @endforeach
                                
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min Qty (alert)</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Qty (per order)</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selling Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Strike-through price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Length(cm)</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Width(cm)</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Height(cm)</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight(kg)</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Track Inventory</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Allow backorders</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Primary</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($variantCombinations as $index => $combination)
                                <tr class="{{ isset($combination['is_duplicate']) && $combination['is_duplicate'] ? 'bg-red-50 border-red-200' : '' }}">
                                    <!-- Image -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="relative w-10 h-10">
                                            @php $tempImg = $combination['image'] ?? null; @endphp
                                            @if($tempImg instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                                <img src="{{ $tempImg->temporaryUrl() }}" alt="" class="w-10 h-10 object-cover rounded border">
                                                <button type="button" wire:click="clearVariantImage({{ $index }})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-[10px]">×</button>
                                            @elseif(!empty($combination['existing_image']))
                                                <img src="{{ $combination['existing_image'] }}" alt="" class="w-10 h-10 object-cover rounded border">
                                                <button type="button" wire:click="removeExistingVariantImage({{ $index }})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-[10px]">×</button>
                                            @else
                                                <label class="absolute inset-0 cursor-pointer">
                                                    <input type="file" accept="image/*" class="hidden" x-on:change="$wire.upload('variantCombinations.{{ $index }}.image', $event.target.files[0])">
                                                    <div class="w-10 h-10 bg-blue-100 rounded flex items-center justify-center hover:bg-blue-200" title="Add image">
                                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                        </svg>
                                                    </div>
                                                </label>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Barcode -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input 
                                            type="text" 
                                            wire:model="variantCombinations.{{ $index }}.barcode"
                                            placeholder="Enter"
                                            class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        >
                                    </td>

                                    <!-- Dynamic Variant Attributes -->
                                    @foreach($selectedVariantTypes as $typeId)
                                        @php
                                            $variantType = $variantTypes->find($typeId);
                                        @endphp
                                        @if($variantType)
                                            <td class="px-4 py-4 whitespace-nowrap align-top">
                                                <div class="relative">
                                                    <select 
                                                        wire:model.live="variantCombinations.{{ $index }}.attributes.{{ $variantType->name }}"
                                                        class="w-full min-w-[120px] px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm {{ isset($combination['is_duplicate']) && $combination['is_duplicate'] ? 'border-red-300 bg-red-50' : 'border-gray-300' }}"
                                                    >
                                                        <option value="">Select</option>
                                                        @foreach($variantType->values as $value)
                                                            <option value="{{ $value->value }}">
                                                                {{ $value->value }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if(isset($combination['is_duplicate']) && $combination['is_duplicate'])
                                                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full flex items-center justify-center">
                                                            <span class="text-white text-xs font-bold">!</span>
                                                        </div>
                                                    @endif
                                                </div>
                                                @if($loop->first)
                                                    <div class="mt-1 text-xs text-red-600 space-y-0.5">
                                                        @error("variantCombinations.$index.attributes") <div>{{ $message }}</div> @enderror
                                                        @error("variantCombinations.$index.incomplete") <div>{{ $message }}</div> @enderror
                                                        @error("variantCombinations.$index.duplicate") <div>{{ $message }}</div> @enderror
                                                    </div>
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach

                                    <!-- SKU -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                wire:model="variantCombinations.{{ $index }}.sku"
                                                placeholder="Auto-generate with button"
                                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm {{ empty($combination['sku']) ? 'bg-gray-50' : 'bg-white' }}"
                                            >
                                            @php
                                                $ready = true;
                                                // Determine readiness by checking required attributes for this row
                                            @endphp
                                            @if(empty($combination['sku']))
                                                <button type="button" class="ml-2 px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700" 
                                                    wire:click="updatedVariantCombinations('', '{{ $index }}.attributes')">
                                                    Generate
                                                </button>
                                            @else
                                                <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-400 rounded-full" title="SKU generated"></div>
                                            @endif
                                        </div>
                                        @error("variantCombinations.{$index}.sku")
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Qty -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input 
                                            type="number" 
                                            wire:model="variantCombinations.{{ $index }}.stock_quantity"
                                            placeholder="0"
                                            class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        >
                                        @error("variantCombinations.{$index}.stock_quantity")
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Min Qty -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input 
                                            type="number" 
                                            wire:model="variantCombinations.{{ $index }}.min_quantity_alert"
                                            placeholder="0"
                                            class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        >
                                        @error("variantCombinations.{$index}.min_quantity_alert")
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Max Qty -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input 
                                            type="number" 
                                            wire:model="variantCombinations.{{ $index }}.max_quantity_per_order"
                                            placeholder="10"
                                            min="1"
                                            class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        >
                                        @error("variantCombinations.{$index}.max_quantity_per_order")
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Cost Price -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            wire:model="variantCombinations.{{ $index }}.cost_price"
                                            placeholder="0.00"
                                            class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        >
                                        @error("variantCombinations.{$index}.cost_price")
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Selling Price -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            wire:model="variantCombinations.{{ $index }}.selling_price"
                                            placeholder="$0.00"
                                            class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        >
                                        @error("variantCombinations.{$index}.selling_price")
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Strike-through price -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            wire:model="variantCombinations.{{ $index }}.compare_price"
                                            placeholder="0.00"
                                            class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        >
                                        @error("variantCombinations.{$index}.compare_price")
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Length(cm) -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            wire:model="variantCombinations.{{ $index }}.length_cm"
                                            placeholder="Enter"
                                            class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        >
                                        @error("variantCombinations.{$index}.length_cm")
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Width(cm) -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            wire:model="variantCombinations.{{ $index }}.width_cm"
                                            placeholder="Enter"
                                            class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        >
                                        @error("variantCombinations.{$index}.width_cm")
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Height(cm) -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input 
                                            type="number" 
                                            step="0.01"
                                            wire:model="variantCombinations.{{ $index }}.height_cm"
                                            placeholder="Enter"
                                            class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        >
                                        @error("variantCombinations.{$index}.height_cm")
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Weight(kg) -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <input 
                                            type="number" 
                                            step="0.001"
                                            wire:model="variantCombinations.{{ $index }}.weight_kg"
                                            placeholder="Enter"
                                            class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                        >
                                        @error("variantCombinations.{$index}.weight_kg")
                                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <!-- Track Inventory -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <input 
                                            type="checkbox" 
                                            wire:model="variantCombinations.{{ $index }}.track_inventory"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                    </td>

                                    <!-- Allow backorders -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <input 
                                            type="checkbox" 
                                            wire:model="variantCombinations.{{ $index }}.allow_backorders"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                    </td>

                                    <!-- Primary -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center align-top">
                                        <input type="radio" name="primaryVariant" value="{{ $index }}" 
                                               wire:click="$set('variantCombinations.{{ $index }}.is_primary', true); $wire.resetPrimaryExcept({{ $index }});"
                                               {{ !empty($combination['is_primary']) ? 'checked' : '' }}
                                               class="text-blue-600 focus:ring-blue-500">
                                        @error('variantPrimary') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <input 
                                            type="checkbox" 
                                            wire:model="variantCombinations.{{ $index }}.status"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <button 
                                            wire:click="removeVariantCombination({{ $index }})"
                                            class="text-red-600 hover:text-red-800"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Error Messages for Duplicates -->
                @php
                    $hasDuplicates = collect($variantCombinations)->contains('is_duplicate', true);
                    $duplicateCount = collect($variantCombinations)->where('is_duplicate', true)->count();
                @endphp

                @if($hasDuplicates)
                    <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <h4 class="text-red-800 font-medium">Duplicate Variant Combinations Detected</h4>
                        </div>
                        <p class="text-red-700 text-sm mb-3">
                            {{ $duplicateCount }} variant combination(s) have duplicate attribute selections. Each variant must have a unique combination of attributes.
                        </p>
                        <p class="text-red-600 text-xs">
                            <strong>Fix:</strong> Change the dropdown selections in the highlighted rows to create unique combinations, or remove duplicate rows.
                        </p>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mt-4">
                        @foreach($variantCombinations as $index => $combination)
                            @error("variantCombinations.{$index}.duplicate")
                                <div class="flex items-center p-3 mb-2 text-sm text-red-800 bg-red-100 rounded-lg">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span><strong>Row {{ $index + 1 }}:</strong> {{ $message }}</span>
                                </div>
                            @enderror
                            @error("variantCombinations.{$index}.incomplete")
                                <div class="flex items-center p-3 mb-2 text-sm text-yellow-800 bg-yellow-100 rounded-lg">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span><strong>Row {{ $index + 1 }}:</strong> {{ $message }}</span>
                                </div>
                            @enderror
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>