<div class="min-h-screen bg-gray-50 lg:ml-72">
    @if ($showForm)
        <!-- Add/Edit Product Form - Full Page -->
        <div class="min-h-screen bg-gray-50">
            <!-- Header Container -->
            <div class="px-4 sm:px-6 py-2 mt-3">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-4 py-2 mt-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <button wire:click="closeForm" 
                                        class="mr-3 p-1 rounded-full hover:bg-gray-100 transition-colors duration-200"
                                        title="Back to product list">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <div>
                                    <h1 class="text-xl font-semibold text-gray-900">
                                        {{ $editingProduct ? 'Update Product' : 'Add Product' }}
                                    </h1>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Form Content -->
            <div class="px-4 sm:px-6 pb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-6">
                        {{-- General Information - Always shown --}}
                        @include('components.ecommerce.product-form.general-information')
                        
                        {{-- Variant Table - Only for variant products --}}
                        @if($product_type === 'variant')
                            @include('components.ecommerce.product-form.variant-table')
                        @endif
                        
                        {{-- Stock and Pricing - Only for single and addon products --}}
                        @if($product_type === 'regular' || $product_type === 'addon')
                            @include('components.ecommerce.product-form.stock-pricing')
                        @endif
                        
                        {{-- Shipping and Delivery - Removed for single and add-ons to avoid duplication; all fields are in Stock & Pricing now --}}
                        
                        {{-- Product Add-ons - Only for single and variant products --}}
                        @if($product_type === 'regular' || $product_type === 'variant')
                            @include('components.ecommerce.product-form.product-addons')
                        @endif
                        
                        {{-- SEO Settings - Always shown --}}
                        @include('components.ecommerce.product-form.seo-settings')
                        
                        {{-- Publishing Settings - Always shown --}}
                        @include('components.ecommerce.product-form.publishing-settings')
                        
                        <!-- Session Messages -->
                        @if (session()->has('message'))
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4 text-green-800">
                                {{ session('message') }}
                            </div>
                        @endif
                        @if (session()->has('warning'))
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4 text-yellow-800">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ session('warning') }}
                                </div>
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4 text-red-800">
                                {{ session('error') }}
                            </div>
                        @endif

                        <!-- Validation Errors Summary intentionally removed to show only inline field errors -->

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <button 
                                wire:click="closeForm"
                                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors"
                            >
                                Cancel
                            </button>
                            <button 
                                wire:click="saveProduct"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                            >
                                <span wire:loading.remove wire:target="saveProduct">
                                    {{ $editingProduct ? 'Update' : 'Save' }}
                                </span>
                                <span wire:loading wire:target="saveProduct" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Product List Page -->
        <!-- Page Title Container - Aligned with Sidebar -->
        <div class="px-4 sm:px-6 py-2 mt-3">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-4 py-2">
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-semibold text-gray-900">Product Management</h1>
                        <div class="flex items-center space-x-3">
                            <!-- Search Input -->
                            <div class="relative">
                                <input type="text" wire:model.live="search" 
                                    class="w-80 px-4 py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                    placeholder="Product name, category">
                                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            
                            <!-- List Type Filter -->
                            <div>
                                <select wire:model.live="listType" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="products">Products</option>
                                    <option value="addons">Add-ons</option>
                                    <option value="all">All</option>
                                </select>
                            </div>
                            
                            <!-- Add Button -->
                            <button 
                                wire:click="createProduct"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors text-sm"
                            >
                                Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product List -->
        <div class="px-4 sm:px-6 pb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <!-- Table -->
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Image
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stock Quantity
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stock Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                SKU
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($products as $product)
                            <tr class="hover:bg-gray-50">
                                <!-- Image -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $primaryImage = $product->getPrimaryImage();
                                    @endphp
                                    @if($primaryImage)
                                        <img src="{{ $primaryImage->file_url }}" 
                                             alt="{{ $product->name }}" 
                                             class="w-10 h-10 rounded-full object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </td>

                                <!-- Product Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                </td>

                                <!-- Category path -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $primary = $product->categories->firstWhere('pivot.is_primary', true);
                                        // Try to infer parent if both are attached
                                        $parent = null;
                                        if ($primary) {
                                            $parent = $product->categories->firstWhere('id', $primary->parent_id);
                                        }
                                    @endphp
                                    <div class="text-sm text-gray-700">
                                        @if($primary && $parent)
                                            {{ $parent->name }} > {{ $primary->name }}
                                        @elseif($primary)
                                            {{ $primary->name }}
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </td>

                                <!-- Stock Quantity -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        {{ $product->variants->sum('stock_quantity') ?? 0 }}
                                    </div>
                                </td>

                                <!-- Stock Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        // Compute availability based on reserved stock and per-variant alerts
                                        $totalAvailable = $product->variants->sum(function($v){ return (int) $v->availableStock(); });
                                        $totalAlert = $product->variants->sum(function($v){ return (int) ($v->min_quantity_alert ?? 0); });
                                    @endphp
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                        @if($totalAvailable <= 0) bg-red-100 text-red-800
                                        @elseif($totalAvailable <= $totalAlert) bg-yellow-100 text-yellow-800
                                        @else bg-green-100 text-green-800 @endif">
                                        @if($totalAvailable <= 0)
                                            {{ $product->variants->contains(fn($v) => $v->allow_backorders) ? 'Available on Backorder' : 'Out of Stock' }}
                                        @elseif($totalAvailable <= $totalAlert)
                                            Low Stock
                                        @else
                                            Available
                                        @endif
                                    </span>
                                </td>

                                <!-- SKU -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        {{ $product->variants->first()?->sku ?? 'N/A' }}
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="relative" x-data="{
                                        open: false,
                                        openUp: false,
                                        forceUp: {{ ($loop->last || $loop->remaining == 1) ? 'true' : 'false' }},
                                        toggle(e) {
                                            this.open = !this.open;
                                            if (!this.open) return;
                                            // If this is one of the last two rows, force upward
                                            if (this.forceUp) {
                                                this.openUp = true;
                                                return;
                                            }
                                            // Otherwise decide based on viewport space
                                            this.$nextTick(() => {
                                                const rect = e.currentTarget.getBoundingClientRect();
                                                const dropdownHeight = 160; // approx menu height
                                                const buffer = 16; // extra spacing
                                                const spaceBelow = window.innerHeight - rect.bottom;
                                                this.openUp = spaceBelow < (dropdownHeight + buffer) && rect.top > (dropdownHeight + buffer);
                                            });
                                        }
                                    }">
                                        <button @click="toggle($event)" class="p-2 text-gray-400 hover:text-gray-600">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                            </svg>
                                        </button>
                                        
                                        <div x-show="open" @click.away="open = false" x-cloak
                                             :class="(forceUp || openUp)
                                                ? 'absolute right-0 bottom-full mb-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-20 origin-bottom' 
                                                : 'absolute right-0 top-full mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-20 origin-top'">
                                            <div class="py-1">
                                                <button 
                                                    wire:click="editProduct({{ $product->id }})"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                        Update
                                                    </div>
                                                </button>
                                                
                                                @if($product->product_type === 'variant' && $product->variants->count() > 1)
                                                    <button 
                                                        wire:click="initiateVariantDelete({{ $product->id }})"
                                                        class="block w-full text-left px-4 py-2 text-sm text-orange-600 hover:bg-gray-100"
                                                    >
                                                        <div class="flex items-center">
                                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                            Delete Variant
                                                        </div>
                                                    </button>
                                                @endif
                                                
                                                <button 
                                                    wire:click="initiateDelete({{ $product->id }})"
                                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                                                >
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                        @if($product->product_type === 'variant')
                                                            Delete Product
                                                        @else
                                                            Delete
                                                        @endif
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"></path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No products</h3>
                                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new product.</p>
                                    <div class="mt-6">
                                        <button 
                                            wire:click="createProduct"
                                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                        >
                                            Add Product
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination -->
                @if($products->hasPages())
                    <div class="bg-white px-6 py-3 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <p class="text-sm text-gray-700">
                                    Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} results
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                {{-- Previous Page Link --}}
                                @if ($products->onFirstPage())
                                    <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded-md cursor-not-allowed">Previous</span>
                                @else
                                    <button wire:click="previousPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Previous</button>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                                    @if ($page == $products->currentPage())
                                        <span class="px-3 py-2 text-sm text-white bg-blue-600 rounded-md">{{ $page }}</span>
                                    @else
                                        <button wire:click="gotoPage({{ $page }})" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">{{ $page }}</button>
                                    @endif
                                @endforeach

                                {{-- Next Page Link --}}
                                @if ($products->hasMorePages())
                                    <button wire:click="nextPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Next</button>
                                @else
                                    <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded-md cursor-not-allowed">Next</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Success Modal -->
    @if($showSuccessModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                
                <div class="inline-block align-middle bg-white rounded-lg px-6 py-8 text-center shadow-xl transform transition-all max-w-sm w-full">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900">Success!</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Product has been saved successfully.</p>
                        </div>
                    </div>
                    <div class="mt-5">
                        <button 
                            wire:click="closeSuccessModal"
                            class="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:text-sm"
                        >
                            Continue
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Product Deletion Modal -->
    @include('components.ecommerce.product-deletion-modal')


</div>