<div class="min-h-screen bg-gray-50 lg:ml-72">
    <!-- Header -->
    <div class="px-4 sm:px-6 py-2 mt-3">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-4 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Inventory Restock</h1>
                    <p class="mt-1 text-sm text-gray-600">Add stock to variants. Backorders will be automatically processed when stock is available.</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Search -->
                    <div class="relative">
                        <input type="text" wire:model.live="search" class="w-full sm:w-80 px-4 py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" placeholder="Product name, SKU">
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if (session()->has('success'))
        <div class="px-4 sm:px-6 py-2">
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center justify-between">
                <span class="text-sm">{{ session('success') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Global Error Messages -->
    @if ($errors->has('selectedVariant'))
        <div class="px-4 sm:px-6 py-2">
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium">Error:</span>
                </div>
                <p class="mt-1 text-sm">{{ $errors->first('selectedVariant') }}</p>
            </div>
        </div>
    @endif

    <!-- Variants Table -->
    <div class="px-4 sm:px-6 pb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <!-- Mobile view -->
            <div class="block sm:hidden">
                @forelse($variants as $variant)
                    <div class="border-b border-gray-200 p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="font-medium text-gray-900">{{ $variant->product->name }}</p>
                                <p class="text-sm text-gray-500">SKU: {{ $variant->sku }}</p>
                                @if(!empty($variant->variant_attributes))
                                    <p class="text-xs text-gray-500 mt-1">
                                        @foreach($variant->variant_attributes as $key => $value)
                                            {{ $key }}: {{ $value }}@if(!$loop->last), @endif
                                        @endforeach
                                    </p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">Stock: {{ $variant->stock_quantity ?? 0 }}</p>
                                <p class="text-xs text-gray-500">Reserved: {{ $variant->reserved_stock ?? 0 }}</p>
                                <p class="text-xs font-medium text-blue-600">Available: {{ max(0, ($variant->stock_quantity ?? 0) - ($variant->reserved_stock ?? 0)) }}</p>
                                @php
                                    $awaitingTotal = ($variant->awaiting_stock_quantity_items ?? 0) + ($variant->awaiting_stock_quantity_addons ?? 0);
                                @endphp
                                @if($awaitingTotal > 0)
                                    <p class="text-xs font-medium text-orange-600 mt-1">Awaiting: {{ $awaitingTotal }}</p>
                                @endif
                                @if(($variant->sold_stock ?? 0) > 0)
                                    <p class="text-xs text-gray-600 mt-1">Sold: {{ $variant->sold_stock }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex justify-end mt-2">
                            <button wire:click="openRestockModal({{ $variant->id }})" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="openRestockModal">Add Stock</span>
                                <span wire:loading wire:target="openRestockModal">Loading...</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-500">No variants found.</div>
                @endforelse
            </div>
            
            <!-- Desktop view -->
            <table class="hidden sm:table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attributes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reserved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Awaiting Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Sold Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Available</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($variants as $variant)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $variant->product->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $variant->sku }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                @if(!empty($variant->variant_attributes))
                                    @foreach($variant->variant_attributes as $key => $value)
                                        <span class="inline-block px-2 py-1 text-xs bg-gray-100 rounded mr-1 mb-1">{{ $key }}: {{ $value }}</span>
                                    @endforeach
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $variant->stock_quantity ?? 0 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $variant->reserved_stock ?? 0 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @php
                                    $awaitingTotal = ($variant->awaiting_stock_quantity_items ?? 0) + ($variant->awaiting_stock_quantity_addons ?? 0);
                                @endphp
                                @if($awaitingTotal > 0)
                                    <span class="font-medium text-orange-600">{{ $awaitingTotal }}</span>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                                {{ $variant->sold_stock ?? 0 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                {{ max(0, ($variant->stock_quantity ?? 0) - ($variant->reserved_stock ?? 0)) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <button wire:click="openRestockModal({{ $variant->id }})" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="openRestockModal">Add Stock</span>
                                    <span wire:loading wire:target="openRestockModal">Loading...</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">No variants found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $variants->links() }}
            </div>
        </div>
    </div>

    <!-- Restock Modal -->
    @if($showRestockModal && $selectedVariant)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeRestockModal"></div>

                <!-- Center modal -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Add Stock
                            </h3>
                            <button type="button" wire:click="closeRestockModal" class="text-gray-400 hover:text-gray-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Error Message -->
                        @if ($errors->has('restock'))
                            <div class="rounded-md bg-red-50 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Error</h3>
                                        <div class="mt-2 text-sm text-red-700">
                                            <p>{{ $errors->first('restock') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Product Info -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <p class="text-sm font-medium text-gray-900">{{ $selectedVariant->product->name }}</p>
                            <p class="text-xs text-gray-600 mt-1">SKU: {{ $selectedVariant->sku }}</p>
                            @if(!empty($selectedVariant->variant_attributes))
                                <p class="text-xs text-gray-600 mt-1">
                                    @foreach($selectedVariant->variant_attributes as $key => $value)
                                        {{ $key }}: {{ $value }}@if(!$loop->last), @endif
                                    @endforeach
                                </p>
                            @endif
                            <div class="grid grid-cols-3 gap-2 mt-3 text-xs">
                                <div>
                                    <p class="text-gray-500">Current Stock</p>
                                    <p class="font-semibold text-gray-900">{{ $selectedVariant->stock_quantity ?? 0 }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Reserved</p>
                                    <p class="font-semibold text-gray-900">{{ $selectedVariant->reserved_stock ?? 0 }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Available</p>
                                    <p class="font-semibold text-blue-600">{{ max(0, ($selectedVariant->stock_quantity ?? 0) - ($selectedVariant->reserved_stock ?? 0)) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Form -->
                        <form wire:submit.prevent="restock">
                            <div class="space-y-4">
                                <!-- Quantity Input -->
                                <div>
                                    <label for="restockQuantity" class="block text-sm font-medium text-gray-700">Quantity to Add <span class="text-red-500">*</span></label>
                                    <input type="number" id="restockQuantity" wire:model="restockQuantity" min="1" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Enter quantity">
                                    @error('restockQuantity') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Reason Input -->
                                <div>
                                    <label for="restockReason" class="block text-sm font-medium text-gray-700">Reason (Optional)</label>
                                    <textarea id="restockReason" wire:model="restockReason" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Enter reason for restocking"></textarea>
                                    @error('restockReason') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm" wire:loading.attr="disabled">
                                    <span wire:loading.remove>Add Stock</span>
                                    <span wire:loading>Processing...</span>
                                </button>
                                <button type="button" wire:click="closeRestockModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>