<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <button
        type="button"
        class="w-full px-6 py-4 border-b border-gray-200 flex justify-between items-center text-left"
        @click="open = !open"
        :aria-expanded="open.toString()"
        aria-controls="stock-pricing-panel">
        <h3 class="text-lg font-medium text-gray-900">Stock and Pricing</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div x-show="open" id="stock-pricing-panel" class="px-6 py-6">
        <div class="overflow-x-auto">
            <table class="min-w-full table-fixed" style="min-width: 1600px;">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Image</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Barcode</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">SKU</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Qty</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Min Qty (alert)</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Max Qty (per order)</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Cost Price</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Selling Price</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Strike-through price</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Length(cm)</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Width(cm)</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Height(cm)</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Weight(kg)</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Track Inventory</th>
                        <th class="text-left py-3 px-4 text-sm font-medium text-gray-700">Allow backorders</th>
                       
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-3 px-4">
                            @if($product_type === 'addon' || $product_type === 'regular')
                                @php 
                                    // Determine if an image is already present (existing or newly selected)
                                    $firstExisting = isset($existingStockImages[0]) ? $existingStockImages[0] : null;
                                    $hasNewFile = (is_array($stockImages) && count($stockImages) > 0) || ($stockImages instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile);
                                    $hasAddonImage = $firstExisting || $hasNewFile;
                                @endphp

                                @if(!$hasAddonImage)
                                    <!-- Add-on: show uploader with "+" only when no image is selected -->
                                    <div class="relative w-12 h-12">
                                        <input 
                                            type="file" 
                                            accept="image/*"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-50"
                                            id="addon-image-upload-input"
                                            aria-label="Upload image"
                                            x-on:change="$wire.upload('stockImages', $event.target.files[0])"
                                            wire:key="addon-image-upload"
                                        >
                                        <div class="absolute inset-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center hover:bg-blue-200 pointer-events-none" title="Add image">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                @else
                                    <!-- Preview with removable X -->
                                    <div class="relative w-12 h-12">
                                        @if($hasNewFile)
                                            @if(is_array($stockImages) && count($stockImages) > 0)
                                                <img src="{{ $stockImages[0]->temporaryUrl() }}" alt="Image" class="w-12 h-12 object-cover rounded-lg border border-gray-200">
                                            @else
                                                <img src="{{ $stockImages->temporaryUrl() }}" alt="Image" class="w-12 h-12 object-cover rounded-lg border border-gray-200">
                                            @endif
                                            <button type="button" wire:click="clearAddonStockImage" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600" aria-label="Remove image">×</button>
                                        @elseif($firstExisting)
                                            @php $countExisting = is_array($existingStockImages) ? count($existingStockImages) : 0; @endphp

                                            @if($countExisting > 1)
                                                <!-- Show all existing images so hidden ones can be removed -->
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    @foreach($existingStockImages as $idx => $img)
                                                        @php
                                                            // Normalize $img (array|string) to a usable URL string
                                                            $src = is_array($img) ? ($img['url'] ?? $img['file_url'] ?? null) : $img;
                                                            if (is_string($src) && $src !== '' && !\Illuminate\Support\Str::startsWith($src, ['http://','https://','/storage'])) {
                                                                $src = \Illuminate\Support\Str::start($src, '/storage/');
                                                            }
                                                        @endphp
                                                        <div class="relative w-12 h-12">
                                                            <img src="{{ $src }}" alt="Image" class="w-12 h-12 object-cover rounded-lg border border-gray-200">
                                                            <button type="button" wire:click="removeExistingStockImage({{ $idx }})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600" aria-label="Remove image">×</button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                @php
                                                    // Normalize single existing image URL
                                                    // $firstExisting can be array|string; normalize to string URL
                                                    $existingSrc = is_array($firstExisting) ? ($firstExisting['url'] ?? $firstExisting['file_url'] ?? null) : $firstExisting;
                                                    if (is_string($existingSrc) && $existingSrc !== '') {
                                                        if (!\Illuminate\Support\Str::startsWith($existingSrc, ['http://','https://','/storage'])) {
                                                            $existingSrc = \Illuminate\Support\Str::start($existingSrc, '/storage/');
                                                        }
                                                    }
                                                @endphp
                                                <img src="{{ $existingSrc }}" alt="Image" class="w-12 h-12 object-cover rounded-lg border border-gray-200">
                                                <button type="button" wire:click="removeExistingStockImage(0)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600" aria-label="Remove image">×</button>
                                            @endif
                                        @endif
                                    </div>
                                @endif

                                @error('stockImages') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                            @else
                                <!-- Variant Product: No image upload in stock section -->
                                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <span class="text-xs text-gray-500">N/A</span>
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <input 
                                type="text" 
                                wire:model="barcode"
                                placeholder="Enter"
                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            >
                            @error('barcode') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center space-x-2">
                                <input 
                                    type="text" 
                                    wire:model="sku"
                                    placeholder="SKU will be auto-generated"
                                    class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                >


                            </div>
                            @error('sku') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </td>
                        <td class="py-3 px-4">
                            <input 
                                type="number" 
                                wire:model="stock_quantity"
                                placeholder="Enter"
                                min="0"
                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            >
                            @error('stock_quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </td>
                        <td class="py-3 px-4">
                            <input 
                                type="number" 
                                wire:model="min_quantity_alert"
                                placeholder="Enter"
                                min="0"
                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            >
                            @error('min_quantity_alert') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </td>
                        <td class="py-3 px-4">
                            <input 
                                type="number" 
                                wire:model="max_quantity_per_order"
                                placeholder="Enter"
                                min="1"
                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            >
                            @error('max_quantity_per_order') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </td>

                        <td class="py-3 px-4">
                            <input 
                                type="number" 
                                wire:model="cost_price"
                                placeholder="Enter"
                                min="0"
                                step="0.01"
                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            >
                            @error('cost_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </td>
                        <td class="py-3 px-4">
                            <input 
                                type="number" 
                                wire:model="selling_price"
                                placeholder="Enter"
                                min="0"
                                step="0.01"
                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            >
                            @error('selling_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </td>
                        <!-- Compare Price (Strike-through) -->
                        <td class="py-3 px-4">
                            <input 
                                type="number" 
                                step="0.01"
                                wire:model="compare_price"
                                placeholder="0.00"
                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            >
                            @error('compare_price')
                                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </td>
                        <!-- Dimensions -->
                        <td class="py-3 px-4">
                            <input 
                                type="number" 
                                step="0.01" 
                                wire:model="length_cm" 
                                placeholder="Enter" 
                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            >
                        </td>
                        <td class="py-3 px-4">
                            <input 
                                type="number" 
                                step="0.01" 
                                wire:model="width_cm" 
                                placeholder="Enter" 
                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            >
                        </td>
                        <td class="py-3 px-4">
                            <input 
                                type="number" 
                                step="0.01" 
                                wire:model="height_cm" 
                                placeholder="Enter" 
                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            >
                        </td>
                        <td class="py-3 px-4">
                            <input 
                                type="number" 
                                step="0.001" 
                                wire:model="weight_kg" 
                                placeholder="Enter" 
                                class="w-full min-w-[120px] px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            >
                        </td>
                        <!-- Track/Backorders -->
                        <td class="py-3 px-4">
                            <input type="checkbox" wire:model="track_inventory" class="rounded border-gray-300 text-blue-600">
                        </td>
                        <td class="py-3 px-4">
                            <input type="checkbox" wire:model="allow_backorders" class="rounded border-gray-300 text-blue-600">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>


    </div>
</div>