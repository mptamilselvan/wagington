<div class="min-h-screen bg-gray-50 lg:ml-72">
    <!-- Main E-commerce Settings Page -->
        <div class="min-h-screen bg-gray-50">
            <!-- Flash Messages -->
            @if (session()->has('message'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-init="setTimeout(() => show = false, 3000)"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform translate-y-2"
                     class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md mx-6">
                    {{ session('message') }}
                </div>
            @endif
            
            @if (session()->has('error'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-init="setTimeout(() => show = false, 4000)"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform translate-y-2"
                     class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md mx-6">
                    {{ session('error') }}
                </div>
            @endif
            
            <!-- Page Title Container - Aligned with Sidebar -->
            <div class="px-4 sm:px-6 py-2 mt-3">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-4 py-2">
                        <div class="flex items-center justify-between">
                            <h1 class="text-2xl font-semibold text-gray-900">E-commerce Settings</h1>
                        </div>
                        <!-- Drag-and-drop for Variant Types is now handled inline via Alpine on the table bodies (mirrors Category implementation). -->
                        <script>
                            // Runtime enhancer: adds DnD to both Variants "Submissions" tables without changing markup
                            (function () {
                                const enhance = () => {
                                    try {
                                        // Broaden selector, then filter by headers
                                        const tables = Array.from(document.querySelectorAll('table'))
                                            .filter(tbl => {
                                                const headers = Array.from(tbl.querySelectorAll('thead th')).map(th => th.textContent.trim().toLowerCase());
                                                return headers.length >= 4 && headers[0] === 'image' && headers[1] === 'title' && headers[2] === 'options' && headers[3] === 'actions';
                                            });

                                        tables.forEach(tbl => {
                                            const tbody = tbl.tBodies[0];
                                            if (!tbody) return;

                                            // 1) Always (re)apply row-level enhancements idempotently
                                            Array.from(tbody.rows).forEach(tr => {
                                                // Ensure data-variant-id exists
                                                if (!tr.dataset.variantId) {
                                                    const editBtn = tr.querySelector('[wire\\:click^="editVariantAttribute("]');
                                                    if (editBtn) {
                                                        const m = editBtn.getAttribute('wire:click').match(/editVariantAttribute\((\d+)\)/);
                                                        if (m) tr.dataset.variantId = m[1];
                                                    }
                                                }

                                                // Inject/restore visible drag handle in Title cell
                                                const titleCell = tr.cells && tr.cells[1];
                                                if (titleCell) {
                                                    const label = titleCell.querySelector('.text-sm.font-medium.text-gray-900') || titleCell;
                                                    if (!titleCell.querySelector('[data-handle]')) {
                                                        const span = document.createElement('span');
                                                        span.setAttribute('data-handle', '');
                                                        span.textContent = '⋮⋮';
                                                        span.className = 'mr-2 cursor-move select-none text-gray-400';
                                                        span.title = 'Drag to reorder';
                                                        label.prepend(span);
                                                    }
                                                }

                                                // Enable native drag events on the row
                                                if (!tr.hasAttribute('draggable')) tr.setAttribute('draggable', 'true');
                                            });

                                            // 2) Bind listeners once per tbody
                                            if (!tbody.__variantsListenersBound) {
                                                let draggingId = null;

                                                tbody.addEventListener('dragstart', (e) => {
                                                    const tr = e.target.closest('tr');
                                                    if (!tr) return;
                                                    draggingId = Number(tr.dataset.variantId || '');
                                                });

                                                tbody.addEventListener('dragover', (e) => {
                                                    e.preventDefault();
                                                });

                                                tbody.addEventListener('drop', (e) => {
                                                    e.preventDefault();
                                                    const targetTr = e.target.closest('tr');
                                                    if (!targetTr || !draggingId) return;
                                                    const targetId = Number(targetTr.dataset.variantId || '');
                                                    if (!targetId) return;

                                                    // Compute new order from current DOM order, inserting draggingId before targetId
                                                    const rows = Array.from(tbody.querySelectorAll('tr[data-variant-id]'));
                                                    let ids = rows.map(r => Number(r.dataset.variantId)).filter(Boolean);
                                                    ids = ids.filter(id => id !== draggingId);
                                                    const to = ids.indexOf(targetId);
                                                    if (to === -1) ids.push(draggingId); else ids.splice(to, 0, draggingId);

                                                    // Call Livewire method directly on the current component (closest wire:id)
                                                    const host = tbl.closest('[wire\\:id]');
                                                    const wireId = host && host.getAttribute('wire:id');
                                                    if (wireId && window.Livewire && Livewire.find) {
                                                        const comp = Livewire.find(wireId);
                                                        if (comp && typeof comp.call === 'function') {
                                                            comp.call('reorderVariantTypes', ids);
                                                        }
                                                    }

                                                    // Re-apply immediately after DOM morph
                                                    setTimeout(enhance, 0);
                                                    draggingId = null;
                                                });

                                                tbody.__variantsListenersBound = true;
                                            }
                                        });
                                    } catch (e) {
                                        // Fail silently to avoid breaking the page
                                        console.error('Variants DnD enhance error', e);
                                    }
                                };

                                // Run on initial load
                                if (document.readyState === 'loading') {
                                    document.addEventListener('DOMContentLoaded', enhance);
                                } else {
                                    enhance();
                                }
                                if (window.Alpine) document.addEventListener('alpine:init', enhance);
                                // Re-run after Livewire updates (support v2/v3 signatures)
                                if (window.Livewire && Livewire.hook) {
                                    try {
                                        Livewire.hook('message.processed', () => enhance());
                                    } catch (_) {
                                        // older signature
                                        Livewire.hook('message.processed', (message, component) => enhance());
                                    }
                                }

                                // MutationObserver fallback to catch DOM morphs
                                const mo = new MutationObserver(() => {
                                    enhance();
                                });
                                mo.observe(document.body, { childList: true, subtree: true });
                            })();
                        </script>
                    </div>
                </div>
            </div>

            <!-- Main Content with Responsive Menu -->
            <div class="px-4 sm:px-6 py-4">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <!-- Mobile Horizontal Menu (visible on small screens) -->
                    <div class="lg:hidden border-b border-gray-200 px-4 pt-4">
                        <nav class="flex space-x-1 overflow-x-auto">
                            <button wire:click="setActiveTab('category-configuration')"
                                    class="flex-shrink-0 px-3 py-2 text-sm font-medium rounded-t-lg transition-colors whitespace-nowrap {{ $activeTab === 'category-configuration' ? 'bg-blue-50 text-blue-600 ring-1 ring-blue-100' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                                Category
                            </button>
                            <button wire:click="setActiveTab('variants-configuration')"
                                    class="flex-shrink-0 px-3 py-2 text-sm font-medium rounded-t-lg transition-colors whitespace-nowrap {{ $activeTab === 'variants-configuration' ? 'bg-blue-50 text-blue-600 ring-1 ring-blue-100' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                                Variants
                            </button>
                            <button wire:click="setActiveTab('tag-management')"
                                    class="flex-shrink-0 px-3 py-2 text-sm font-medium rounded-t-lg transition-colors whitespace-nowrap {{ $activeTab === 'tag-management' ? 'bg-blue-50 text-blue-600 ring-1 ring-blue-100' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                                Tags
                            </button>
                        </nav>
                    </div>

                    <!-- Desktop Layout with Sidebar (hidden on small screens) -->
                    <div class="hidden lg:flex min-h-[600px]">
                        <!-- Left Vertical Menu -->
                        <div class="w-56 bg-gray-50 flex-shrink-0 rounded-l-lg border-r border-gray-200">
                            <div class="p-4">
                                <!-- Navigation Menu -->
                                <nav class="space-y-2">
                                    <button wire:click="setActiveTab('category-configuration')"
                                            class="w-full text-left px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ $activeTab === 'category-configuration' ? 'bg-blue-50 text-blue-600 ring-1 ring-blue-100' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                                        Category Configuration
                                    </button>
                                    <button wire:click="setActiveTab('variants-configuration')"
                                            class="w-full text-left px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ $activeTab === 'variants-configuration' ? 'bg-blue-50 text-blue-600 ring-1 ring-blue-100' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                                        Variants Configuration
                                    </button>
                                    <button wire:click="setActiveTab('tag-management')"
                                            class="w-full text-left px-3 py-2.5 text-sm font-medium rounded-lg transition-colors {{ $activeTab === 'tag-management' ? 'bg-blue-50 text-blue-600 ring-1 ring-blue-100' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                                        Tag Management
                                    </button>
                                </nav>
                            </div>
                        </div>

                        <!-- Desktop Main Content Area -->
                        <div class="flex-1 p-6">
                        @if($activeTab === 'category-configuration')
                            <!-- Category Configuration Content -->
                            <div class="grid grid-cols-1 gap-8">
                                <!-- Left Column - Form -->
                                <div class="grid grid-cols-1 gap-6 bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                                    <form wire:submit.prevent="saveCategory" novalidate>
                                        <!-- Name and Meta Title Row -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Name -->
                                            <x-forms.input 
                                                label="Name*" 
                                                name="name" 
                                                wireModel="name"
                                                wireModelLive="true"
                                                placeholder="Enter name" 
                                                required="true"
                                            />

                                            <!-- Meta Title -->
                                            <x-forms.input 
                                                label="Meta Title*" 
                                                name="meta_title" 
                                                wireModel="meta_title"
                                                placeholder="Enter meta title" 
                                                required="true"
                                            />
                                        </div>

                                        <!-- Slug and Parent Row (2 columns, responsive) -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Slug -->
                                            <x-forms.input 
                                                label="Slug" 
                                                name="slug" 
                                                wireModel="slug"
                                                placeholder="Auto-generated from name" 
                                            />

                                            <!-- Parent Category -->
                                            <div>
                                                <label class="block text-gray-700 mb-2">Parent Category (optional)</label>
                                                <select 
                                                    wire:model.defer="parent_id"
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                                >
                                                    <option value="">-- None (Root Category) --</option>
                                                    @foreach($parentCategories as $pc)
                                                        <option value="{{ $pc->id }}">{{ $pc->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('parent_id') <div class="mt-2 text-sm text-red-600">{{ $message }}</div> @enderror
                                            </div>
                                        </div>

                                        <!-- Focus Keywords, Images (left) and Meta Description (right) -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:items-stretch">
                                            <!-- Left: Focus + Meta Keywords + Images -->
                                            <div class="grid grid-cols-1 gap-6">
                                                <!-- Meta Keywords -->
                                                <x-forms.input 
                                                    label="Meta Keywords*" 
                                                    name="meta_keywords" 
                                                    wireModel="meta_keywords"
                                                    placeholder="Enter meta keywords" 
                                                    required="true"
                                                />

                                                <!-- Focus Keywords -->
                                                <x-forms.input 
                                                    label="Focus Keywords" 
                                                    name="focus_keywords" 
                                                    wireModel="focus_keywords"
                                                    placeholder="Enter focus keywords" 
                                                />

                                                <!-- Images Upload (single image like Variant) -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                                        Images* (300x300)
                                                    </label>

                                                    @if(empty($images) && empty($existingImages))
                                                        <!-- File Upload Area (show only when no image selected or existing) -->
                                                        <div class="border-2 border-dashed border-blue-300 rounded-xl p-6 text-center hover:border-blue-400 transition-colors bg-blue-50/30">
                                                            <input type="file" 
                                                                   wire:model="images" 
                                                                   accept="image/*"
                                                                   class="hidden" 
                                                                   id="image-upload">
                                                            <label for="image-upload" class="cursor-pointer">
                                                                <div class="flex flex-col items-center">
                                                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-2">
                                                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                                        </svg>
                                                                    </div>
                                                                    <span class="text-blue-600 font-medium text-sm">Add File</span>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    @endif

                                                    <!-- Display uploaded image preview -->
                                                    @if(!empty($images))
                                                        <div class="mt-4 flex flex-wrap gap-3">
                                                            @foreach($images as $index => $image)
                                                                <div class="relative">
                                                                    <img src="{{ $image->temporaryUrl() }}" 
                                                                         alt="Preview" 
                                                                         class="w-20 h-20 object-cover rounded-lg border">
                                                                    <button type="button" 
                                                                            wire:click="removeImage({{ $index }})"
                                                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">
                                                                        ×
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    <!-- Display existing image when editing -->
                                                    @if(!empty($existingImages))
                                                        <div class="mt-4 flex flex-wrap gap-3">
                                                            @foreach($existingImages as $index => $imageUrl)
                                                                <div class="relative">
                                                                    <img src="{{ $imageUrl }}" 
                                                                         alt="Existing image" 
                                                                         class="w-20 h-20 object-cover rounded-lg border">
                                                                    <button type="button" 
                                                                            wire:click="removeExistingImage({{ $index }})"
                                                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">
                                                                        ×
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @error('images') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                                    @error('images.*') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            <!-- Right: Meta Description -->
                                            <div class="flex flex-col">
                                                <label for="meta_description" class="block text-gray-700 mb-2 font-medium" style="font-family: 'Rubik', sans-serif;">
                                                    Meta Description<span class="text-red-500">*</span>
                                                </label>
                                                <textarea id="meta_description"
                                                          wire:model="meta_description" 
                                                          rows="6"
                                                          class="flex-1 w-full px-4 py-3 border border-gray-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 text-gray-900 placeholder-gray-400 resize-none min-h-[160px] md:min-h-[200px] overflow-auto"
                                                          style="font-family: 'Rubik', sans-serif;"
                                                          placeholder="Type here"></textarea>
                                                @error('meta_description') <p class="mt-1 text-sm text-red-600" style="font-family: 'Rubik', sans-serif;">{{ $message }}</p> @enderror
                                            </div>
                                        </div>

                                        <!-- Divider to emulate card footer separation in Figma -->
                                        <div class="md:col-span-2 border-t border-gray-200 mt-6"></div>

                                        <!-- Form Actions -->
                                        <div class="flex justify-end space-x-3 pt-6 md:col-span-2">
                                            <x-button-component type="cancelSmall" label="Clear" wireClickFn="resetForm" />
                                            <x-button-component type="submitSmall" label="Save" />
                                        </div>
                                    </form>
                                </div>

                                <!-- Submissions Section (full width under form) -->
                                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                    <div class="px-6 pt-6">
                                        <h3 class="text-lg font-semibold text-gray-900">Submissions</h3>
                                    </div>
                                    
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full">
                                                <thead class="bg-gray-50 border-b border-gray-200">
                                                    <tr>
                                                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                                                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Image</th>
                                                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Name</th>
                                                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Parent</th>
                                                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                                                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-100" x-data="{ draggingId: null, startDrag(id){ this.draggingId = id }, onDrop(targetId){ if(!this.draggingId) return; const rows = Array.from($el.querySelectorAll('tr[data-category-id]')); let ids = rows.map(r => Number(r.dataset.categoryId)); ids = ids.filter(id => id !== this.draggingId); const to = ids.indexOf(targetId); if (to === -1) { ids.push(this.draggingId); } else { ids.splice(to, 0, this.draggingId); } $wire.reorderCategories(ids); this.draggingId = null; } }">
                                                    @forelse($categories as $category)
                                                        <tr class="hover:bg-gray-50/50 transition-colors" data-category-id="{{ $category->id }}" draggable="true" @dragstart="startDrag({{ $category->id }})" @dragover.prevent @drop="onDrop({{ $category->id }})">
                                                            <td class="px-4 py-4 whitespace-nowrap">
                                                                <div class="flex items-center justify-center w-6 h-6 bg-gray-100 rounded text-xs font-medium text-gray-600">
                                                                    {{ $category->id }}
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-4 whitespace-nowrap">
                                                                @if($category->image_url)
                                                                    <img src="{{ $category->image_url }}" 
                                                                         alt="{{ $category->name }}" 
                                                                         class="w-10 h-10 rounded-lg object-cover border border-gray-200">
                                                                @else
                                                                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center border border-gray-200">
                                                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                        </svg>
                                                                    </div>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-4 whitespace-nowrap">
                                                                <div class="text-sm font-medium text-gray-900">
                                                                    <span class="mr-2 cursor-move select-none" title="Drag to reorder">⋮⋮</span>
                                                                    {{ strlen($category->name) > 20 ? substr($category->name, 0, 20) . '...' : $category->name }}
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-4 whitespace-nowrap">
                                                                <div class="text-sm text-gray-600">
                                                                    {{ optional($category->parent)->name ? (strlen($category->parent->name) > 20 ? substr($category->parent->name, 0, 20) . '...' : $category->parent->name) : '-' }}
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-4 whitespace-nowrap">
                                                                <button wire:click="toggleStatus({{ $category->id }})"
                                                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $category->status === 'active' ? 'bg-blue-600' : 'bg-gray-200' }}">
                                                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $category->status === 'active' ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                                                </button>
                                                            </td>
                                                            <td class="px-4 py-4 whitespace-nowrap text-right">
                                                                <x-action-menu>
                                                                    <x-action-menu-item wire:click="editCategory({{ $category->id }})" icon="edit">
                                                                        Edit
                                                                    </x-action-menu-item>
                                                                    
                                                                    <x-action-menu-item 
                                                                        onclick="confirmDeleteCategory({{ $category->id }})"
                                                                        icon="trash"
                                                                        variant="danger">
                                                                        Delete
                                                                    </x-action-menu-item>
                                                                </x-action-menu>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                                                <div class="flex flex-col items-center">
                                                                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                                    </svg>
                                                                    <h3 class="text-sm font-medium text-gray-900 mb-1">No categories found</h3>
                                                                    <p class="text-sm text-gray-500">Get started by creating your first category.</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Pagination -->
                                        @if($categories->hasPages())
                                            <div class="px-6 py-3 border-t border-gray-200 bg-gray-50">
                                                {{ $categories->links() }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif($activeTab === 'variants-configuration')
                            <!-- Variants Configuration Content -->
                            <div class="space-y-6">
                                <!-- Form Section -->
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                    <div class="p-6">
                                        <div class="max-w-full">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <!-- Title Field -->
                                                <div>
                                                    <label for="variantTitle" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Title
                                                    </label>
                                                    <input type="text" 
                                                           id="variantTitle"
                                                           wire:model="variantTitle" 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                           placeholder="Size">
                                                    @error('variantTitle') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Options Field -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                                        Options
                                                    </label>
                                                    <div class="flex items-center justify-between mb-3">
                                                        <input type="text" 
                                                               wire:model="currentOption" 
                                                               wire:keydown.enter="addVariantOption"
                                                               class="flex-1 mr-2 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                               placeholder="Enter options">
                                                        <button type="button" 
                                                                wire:click="addVariantOption"
                                                                class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-md transition-colors">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                            </svg>
                                                        </button>
                                                    </div>

                                                    <!-- Option Tags Display -->
                                                    @if(!empty($variantOptions))
                                                        <div class="flex flex-wrap gap-2 mb-3">
                                                            @foreach($variantOptions as $index => $option)
                                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                                                    {{ $option }}
                                                                    <button type="button" 
                                                                            wire:click="removeVariantOption({{ $index }})"
                                                                            class="ml-2 text-blue-600 hover:text-blue-800">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                        </svg>
                                                                    </button>
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @error('variantOptions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    @error('currentOption') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Enable Color Picker Toggle -->
                                                <div class="md:col-span-2">
                                                    <label class="inline-flex items-center space-x-2">
                                                        <input type="checkbox" wire:model="enableColorPicker" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        <span class="text-sm text-gray-700">Enable color picker</span>
                                                    </label>
                                                </div>

                                                <!-- Color inputs per option -->
                                                @if($enableColorPicker && !empty($variantOptions))
                                                    <div class="md:col-span-2 space-y-2">
                                                        @foreach($variantOptions as $index => $option)
                                                            <div class="flex items-center gap-3">
                                                                <span class="w-32 text-sm text-gray-600 truncate">{{ $option }}</span>
                                                                <input type="color" wire:model.lazy="variantOptionColors.{{ $index }}" class="h-9 w-12 p-0 border rounded">
                                                                <input type="text" wire:model.lazy="variantOptionColors.{{ $index }}" placeholder="#RRGGBB" class="w-32 px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <!-- Images Upload -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                                        Images* (300x300)
                                                    </label>
                                                    
                                                    @if(!$variantImage && empty($existingVariantImages))
                                                        <!-- File Upload Area -->
                                                        <div class="border-2 border-dashed border-blue-300 rounded-xl p-6 text-center hover:border-blue-400 transition-colors bg-blue-50/30">
                                                            <input type="file" 
                                                                   wire:model="variantImage" 
                                                                   accept="image/*"
                                                                   class="hidden" 
                                                                   id="variantImageUpload">
                                                            <label for="variantImageUpload" class="cursor-pointer">
                                                                <div class="flex flex-col items-center">
                                                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-2">
                                                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                                        </svg>
                                                                    </div>
                                                                    <span class="text-blue-600 font-medium text-sm">Add File</span>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    @endif

                                                    <!-- Display uploaded images -->
                                                    @if($variantImage)
                                                        <div class="mt-4 flex flex-wrap gap-3">
                                                            <div class="relative">
                                                                <img src="{{ $variantImage->temporaryUrl() }}" 
                                                                     alt="Preview" 
                                                                     class="w-20 h-20 object-cover rounded-lg border">
                                                                <button type="button" 
                                                                        wire:click="$set('variantImage', null)"
                                                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">
                                                                    ×
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <!-- Display existing images -->
                                                    @if(!empty($existingVariantImages))
                                                        <div class="mt-4 flex flex-wrap gap-3">
                                                            @foreach($existingVariantImages as $index => $imageUrl)
                                                                <div class="relative">
                                                                    <img src="{{ $imageUrl }}" 
                                                                         alt="Existing image" 
                                                                         class="w-20 h-20 object-cover rounded-lg border">
                                                                    <button type="button" 
                                                                            wire:click="removeExistingVariantImage({{ $index }})"
                                                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">
                                                                        ×
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @error('variantImage') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="flex justify-end space-x-3 pt-6 md:col-span-2">
                                                    <button type="button" 
                                                            wire:click="clearVariantForm"
                                                            class="button-secondary rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
                                                        Clear
                                                    </button>
                                                    <button type="button" 
                                                            wire:click="saveVariantAttribute"
                                                            class="button-primary  rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
                                                        <span class="">Save</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submissions Section -->
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                    <div class="px-6 py-4 border-b border-gray-200">
                                        <h3 class="text-lg font-medium text-gray-900">Submissions</h3>
                                    </div>
                                    <div class="p-6">
                                        @if($variantAttributeTypes->count() > 0)
                                            <div class="overflow-hidden">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Options</th>
                                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        @foreach($variantAttributeTypes as $variantType)
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <div class="flex items-center">
                                                                        @if($variantType->image_url)
                                                                        <img class="h-10 w-10 rounded-full object-cover" src="{{ $variantType->image_url }}" alt="{{ $variantType->name }}">
                                                                        @else
                                                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                                </svg>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <div class="text-sm font-medium text-gray-900">{{ $variantType->name }}</div>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <div class="flex flex-wrap gap-1">
                                                                        @foreach($variantType->values as $value)
                                                                            <span class="inline-block px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">{{ $value->value }}</span>
                                                                        @endforeach
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                                    <x-action-menu>
                                                                        <x-action-menu-item wire:click="editVariantAttribute({{ $variantType->id }})" icon="edit">
                                                                            Edit
                                                                        </x-action-menu-item>
                                                                        
                                                                        <x-action-menu-item 
                                                                            onclick="confirmDeleteVariantAttribute({{ $variantType->id }})"
                                                                            icon="trash"
                                                                            variant="danger">
                                                                            Delete
                                                                        </x-action-menu-item>
                                                                    </x-action-menu>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-12">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                </svg>
                                                <h3 class="mt-2 text-sm font-medium text-gray-900">No variant attributes</h3>
                                                <p class="mt-1 text-sm text-gray-500">Get started by creating your first variant attribute.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif($activeTab === 'tag-management')
                            <!-- Tag Management Content -->
                            <div class="space-y-6">
                                <!-- Form Section -->
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                    <div class="p-6">
                                        <div class="max-w-full">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <!-- Name Field -->
                                                <div>
                                                    <label for="tagName" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Name*
                                                    </label>
                                                    <input type="text" 
                                                           id="tagName"
                                                           wire:model="tagName" 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                           placeholder="Enter name">
                                                    @error('tagName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Color Field -->
                                                <div>
                                                    <label for="tagColor" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Color
                                                    </label>
                                                    <div class="flex items-center space-x-3">
                                                        <input type="color" 
                                                               id="tagColor"
                                                               wire:model="tagColor" 
                                                               class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                                                        <input type="text" 
                                                               wire:model="tagColor" 
                                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                               placeholder="#007bff">
                                                    </div>
                                                    @error('tagColor') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Description Field -->
                                                <div>
                                                    <label for="tagDescription" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Description
                                                    </label>
                                                    <textarea id="tagDescription"
                                                              wire:model="tagDescription" 
                                                              rows="3"
                                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                              placeholder="Enter description (optional)"></textarea>
                                                    @error('tagDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="flex justify-end space-x-3 pt-6 md:col-span-2">
                                                    <button type="button" 
                                                            wire:click="clearTagForm"
                                                            class="button-secondary rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
                                                        Clear
                                                    </button>
                                                    <button type="button" 
                                                            wire:click="saveTag"
                                                            class="button-primary rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
                                                        {{ $editingTag ? 'Update' : 'Save' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submissions Section -->
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                    <div class="px-6 py-4 border-b border-gray-200">
                                        <h3 class="text-lg font-medium text-gray-900">Submissions</h3>
                                    </div>
                                    <div class="overflow-hidden">
                                        @if($tags->count() > 0)
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tag Name</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        @foreach($tags as $tag)
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    {{ $tag->id }}
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <div class="flex items-center">
                                                                        <span class="inline-block w-3 h-3 rounded-full mr-2" style="background-color: {{ $tag->color }}"></span>
                                                                        <div class="text-sm font-medium text-gray-900">{{ $tag->name }}</div>
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <div class="flex items-center">
                                                                        <span class="inline-block w-4 h-4 rounded border mr-2" style="background-color: {{ $tag->color }}"></span>
                                                                        <span class="text-sm text-gray-600">{{ $tag->color }}</span>
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <button wire:click="toggleTagStatus({{ $tag->id }})"
                                                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $tag->is_active ? 'bg-blue-600' : 'bg-gray-200' }}">
                                                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $tag->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                                                    </button>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                                    <x-action-menu>
                                                                        <x-action-menu-item wire:click="editTag({{ $tag->id }})" icon="edit">
                                                                            Edit
                                                                        </x-action-menu-item>
                                                                        
                                                                        <x-action-menu-item 
                                                                            onclick="confirmDeleteTag({{ $tag->id }})"
                                                                            icon="trash"
                                                                            variant="danger">
                                                                            Delete
                                                                        </x-action-menu-item>
                                                                    </x-action-menu>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <!-- Pagination -->
                                            <div class="px-6 py-4 border-t border-gray-200">
                                                {{ $tags->links() }}
                                            </div>
                                        @else
                                            <div class="text-center py-12">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                                </svg>
                                                <h3 class="mt-2 text-sm font-medium text-gray-900">No tags found</h3>
                                                <p class="mt-1 text-sm text-gray-500">Get started by creating a new tag.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        </div>
                    </div>

                    <!-- Mobile Content Area (visible on small screens) -->
                    <div class="lg:hidden p-4">
                        @if($activeTab === 'category-configuration')
                            <!-- Category Configuration Form and Table -->
                            <div class="space-y-6">
                                <!-- Form Section -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <form wire:submit.prevent="saveCategory" novalidate>
                                        <!-- Name and Meta Title Row -->
                                        <div class="grid grid-cols-1 gap-4">
                                            <!-- Name -->
                                            <x-forms.input 
                                                label="Name" 
                                                name="name" 
                                                wireModel="name"
                                                wireModelLive="true"
                                                placeholder="Enter name" 
                                                required="true"
                                            />

                                            <!-- Meta Title -->
                                            <x-forms.input 
                                                label="Meta Title*" 
                                                name="meta_title" 
                                                wireModel="meta_title"
                                                placeholder="Enter meta title" 
                                                required="true"
                                            />

                                            <!-- Slug -->
                                            <x-forms.input 
                                                label="Slug" 
                                                name="slug" 
                                                wireModel="slug"
                                                placeholder="Auto-generated from name" 
                                            />

                                            <!-- Meta Keywords -->
                                            <x-forms.input 
                                                label="Meta Keywords*" 
                                                name="meta_keywords" 
                                                wireModel="meta_keywords"
                                                placeholder="Enter meta keywords" 
                                                required="true"
                                            />

                                            <!-- Focus Keywords -->
                                            <x-forms.input 
                                                label="Focus Keywords" 
                                                name="focus_keywords" 
                                                wireModel="focus_keywords"
                                                placeholder="Enter focus keywords" 
                                            />

                                            <!-- Meta Description -->
                                            <div>
                                                <label for="mobile_meta_description" class="block text-gray-700 mb-2 font-medium" style="font-family: 'Rubik', sans-serif;">
                                                    Meta Description<span class="text-red-500">*</span>
                                                </label>
                                                <textarea id="mobile_meta_description"
                                                          wire:model="meta_description" 
                                                          rows="6"
                                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 text-gray-900 placeholder-gray-400 resize-none min-h-[160px] overflow-auto"
                                                          style="font-family: 'Rubik', sans-serif;"
                                                          placeholder="Type here"></textarea>
                                                @error('meta_description') <p class="mt-1 text-sm text-red-600" style="font-family: 'Rubik', sans-serif;">{{ $message }}</p> @enderror
                                            </div>
                                        </div>

                                        <!-- Images Upload -->
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Images<span class="text-gray-400"> (300x300)</span>
                                            </label>
                                            
                                            <!-- File Upload Area -->
                                            <div class="border-2 border-dashed border-blue-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors bg-blue-50/30">
                                                <input type="file" 
                                                       wire:model="images" 
                                                       accept="image/*"
                                                       class="hidden" 
                                                       id="mobile-image-upload">
                                                <label for="mobile-image-upload" class="cursor-pointer">
                                                    <div class="flex flex-col items-center">
                                                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-3">
                                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                            </svg>
                                                        </div>
                                                        <span class="text-blue-600 font-medium text-sm">Add File</span>
                                                    </div>
                                                </label>
                                            </div>

                                            <!-- Display uploaded images -->
                                            @if(!empty($images))
                                                <div class="mt-4 flex flex-wrap gap-3">
                                                    @foreach($images as $index => $image)
                                                        <div class="relative">
                                                            <img src="{{ $image->temporaryUrl() }}" 
                                                                 alt="Preview" 
                                                                 class="w-20 h-20 object-cover rounded-lg border">
                                                            <button type="button" 
                                                                    wire:click="removeImage({{ $index }})"
                                                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">
                                                                ×
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <!-- Display existing images -->
                                            @if(!empty($existingImages))
                                                <div class="mt-4 flex flex-wrap gap-3">
                                                    @foreach($existingImages as $index => $imageUrl)
                                                        <div class="relative">
                                                            <img src="{{ $imageUrl }}" 
                                                                 alt="Existing image" 
                                                                 class="w-20 h-20 object-cover rounded-lg border">
                                                            <button type="button" 
                                                                    wire:click="removeExistingImage({{ $index }})"
                                                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">
                                                                ×
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @error('images.*') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Form Actions -->
                                        <div class="flex justify-end space-x-3 pt-6 md:col-span-2">
                                            <button type="button" 
                                                    wire:click="resetForm"
                                                    class="px-6 py-2.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm font-medium">
                                                Clear
                                            </button>
                                            <button type="submit" 
                                                    class="px-6 py-2.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm font-medium">
                                                Save
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Submissions Table Section -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <h3 class="text-lg font-semibold text-gray-900">Submissions</h3>
                                    
                                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full">
                                                <thead class="bg-gray-50 border-b border-gray-200">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Name</th>
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-100" x-data="{ draggingId: null, startDrag(id){ this.draggingId = id }, onDrop(targetId){ if(!this.draggingId) return; const rows = Array.from($el.querySelectorAll('tr[data-category-id]')); let ids = rows.map(r => Number(r.dataset.categoryId)); ids = ids.filter(id => id !== this.draggingId); const to = ids.indexOf(targetId); if (to === -1) { ids.push(this.draggingId); } else { ids.splice(to, 0, this.draggingId); } $wire.reorderCategories(ids); this.draggingId = null; } }">
                                                    @forelse($categories as $category)
                                                        <tr class="hover:bg-gray-50/50 transition-colors" data-category-id="{{ $category->id }}" draggable="true" @dragstart="startDrag({{ $category->id }})" @dragover.prevent @drop="onDrop({{ $category->id }})">
                                                            <td class="px-3 py-3 whitespace-nowrap">
                                                                <div class="flex items-center justify-center w-6 h-6 bg-gray-100 rounded text-xs font-medium text-gray-600">
                                                                    {{ $category->id }}
                                                                </div>
                                                            </td>
                                                            <td class="px-3 py-3 whitespace-nowrap">
                                                                <div class="text-sm font-medium text-gray-900">
                                                                    <span class="mr-2 cursor-move select-none" title="Drag to reorder">⋮⋮</span>
                                                                    {{ strlen($category->name) > 15 ? substr($category->name, 0, 15) . '...' : $category->name }}
                                                                </div>
                                                            </td>
                                                            <td class="px-3 py-3 whitespace-nowrap">
                                                                <button wire:click="toggleStatus({{ $category->id }})"
                                                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $category->status === 'active' ? 'bg-blue-600' : 'bg-gray-200' }}">
                                                                    <span class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform {{ $category->status === 'active' ? 'translate-x-5' : 'translate-x-1' }}"></span>
                                                                </button>
                                                            </td>
                                                            <td class="px-3 py-3 whitespace-nowrap text-right">
                                                                <x-action-menu width="w-40">
                                                                    <x-action-menu-item wire:click="editCategory({{ $category->id }})" icon="edit">
                                                                        Edit
                                                                    </x-action-menu-item>
                                                                    
                                                                    <x-action-menu-item 
                                                                        onclick="confirmDeleteCategory({{ $category->id }})"
                                                                        icon="trash"
                                                                        variant="danger">
                                                                        Delete
                                                                    </x-action-menu-item>
                                                                </x-action-menu>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="px-3 py-8 text-center text-gray-500">
                                                                <div class="flex flex-col items-center">
                                                                    <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                                    </svg>
                                                                    <p class="text-xs text-gray-500">No categories found</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Pagination -->
                                        @if($categories->hasPages())
                                            <div class="px-3 py-2 border-t border-gray-200 bg-gray-50">
                                                {{ $categories->links() }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif($activeTab === 'variants-configuration')
                            <!-- Variants Configuration Content -->
                            <div class="space-y-6">
                                <!-- Form Section -->
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                    <div class="p-6">
                                        <div class="max-w-full">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <!-- Title Field -->
                                                <div>
                                                    <label for="variantTitle" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Title
                                                    </label>
                                                    <input type="text" 
                                                           id="variantTitle"
                                                           wire:model="variantTitle" 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                           placeholder="Size">
                                                    @error('variantTitle') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Options Field -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                                        Options
                                                    </label>
                                                    <div class="flex items-center justify-between mb-3">
                                                        <input type="text" 
                                                               wire:model="currentOption" 
                                                               wire:keydown.enter="addVariantOption"
                                                               class="flex-1 mr-2 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                               placeholder="Enter options">
                                                        <button type="button" 
                                                                wire:click="addVariantOption"
                                                                class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-md transition-colors">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                            </svg>
                                                        </button>
                                                    </div>

                                                    <!-- Option Tags Display -->
                                                    @if(!empty($variantOptions))
                                                        <div class="flex flex-wrap gap-2 mb-3">
                                                            @foreach($variantOptions as $index => $option)
                                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                                                    {{ $option }}
                                                                    <button type="button" 
                                                                            wire:click="removeVariantOption({{ $index }})"
                                                                            class="ml-2 text-blue-600 hover:text-blue-800">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                        </svg>
                                                                    </button>
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @error('variantOptions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    @error('currentOption') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Enable Color Picker Toggle (Mobile) -->
                                                <div class="md:col-span-2">
                                                    <label class="inline-flex items-center space-x-2">
                                                        <input type="checkbox" wire:model="enableColorPicker" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        <span class="text-sm text-gray-700">Enable color picker</span>
                                                    </label>
                                                </div>

                                                <!-- Color inputs per option (Mobile) -->
                                                @if($enableColorPicker && !empty($variantOptions))
                                                    <div class="md:col-span-2 space-y-2">
                                                        @foreach($variantOptions as $index => $option)
                                                            <div class="flex items-center gap-3">
                                                                <span class="w-32 text-sm text-gray-600 truncate">{{ $option }}</span>
                                                                <input type="color" wire:model.lazy="variantOptionColors.{{ $index }}" class="h-9 w-12 p-0 border rounded">
                                                                <input type="text" wire:model.lazy="variantOptionColors.{{ $index }}" placeholder="#RRGGBB" class="w-32 px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <!-- Images Upload -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                                        Images* (300x300)
                                                    </label>

                                                    @if(!$variantImage && empty($existingVariantImages))
                                                        <!-- File Upload Area -->
                                                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                                                            <input type="file"
                                                                   wire:model="variantImage"
                                                                   accept="image/*"
                                                                   class="hidden"
                                                                   id="mobileVariantImageUpload">
                                                            <label for="mobileVariantImageUpload" class="cursor-pointer">
                                                                <div class="flex flex-col items-center">
                                                                    <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                                    </svg>
                                                                    <span class="text-gray-600 font-medium">Add File</span>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    @endif

                                                    <!-- Display uploaded images -->
                                                    @if($variantImage)
                                                        <div class="mt-4 flex flex-wrap gap-3">
                                                            <div class="relative">
                                                                <img src="{{ $variantImage->temporaryUrl() }}"
                                                                     alt="Preview"
                                                                     class="w-20 h-20 object-cover rounded-lg border">
                                                                <button type="button"
                                                                        wire:click="$set('variantImage', null)"
                                                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">
                                                                    ×
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <!-- Display existing images when editing -->
                                                    @if(!empty($existingVariantImages))
                                                        <div class="mt-4 flex flex-wrap gap-3">
                                                            @foreach($existingVariantImages as $index => $imageUrl)
                                                                <div class="relative">
                                                                    <img src="{{ $imageUrl }}"
                                                                         alt="Existing image"
                                                                         class="w-20 h-20 object-cover rounded-lg border">
                                                                    <button type="button"
                                                                            wire:click="removeExistingVariantImage({{ $index }})"
                                                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600">
                                                                        ×
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @error('variantImage') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="flex justify-end space-x-3 pt-6 md:col-span-2">
                                                    <button type="button" 
                                                            wire:click="clearVariantForm"
                                                            class="button-secondary rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
                                                        Clear
                                                    </button>
                                                    <button type="button" 
                                                            wire:click="saveVariantAttribute"
                                                            class="button-primary rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
                                                        <span class="">Save</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submissions Section -->
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                    <div class="px-6 py-4 border-b border-gray-200">
                                        <h3 class="text-lg font-medium text-gray-900">Submissions</h3>
                                    </div>
                                    <div class="p-6">
                                        @if($variantAttributeTypes->count() > 0)
                                            <div class="overflow-hidden">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Options</th>
                                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        @foreach($variantAttributeTypes as $variantType)
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <div class="flex items-center">
                                                                        @if($variantType->image_url)
                                                                        <img class="h-10 w-10 rounded-full object-cover" src="{{ $variantType->image_url }}" alt="{{ $variantType->name }}">
                                                                        @else
                                                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                                </svg>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <div class="text-sm font-medium text-gray-900">{{ $variantType->name }}</div>
                                                                </td>
                                                                <td class="px-6 py-4">
                                                                    <div class="flex flex-wrap gap-1">
                                                                        @foreach($variantType->values as $value)
                                                                            <span class="inline-block px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">{{ $value->value }}</span>
                                                                        @endforeach
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                                    <x-action-menu>
                                                                        <x-action-menu-item wire:click="editVariantAttribute({{ $variantType->id }})" icon="edit">
                                                                            Edit
                                                                        </x-action-menu-item>
                                                                        
                                                                        <x-action-menu-item 
                                                                            onclick="confirmDeleteVariantAttribute({{ $variantType->id }})"
                                                                            icon="trash"
                                                                            variant="danger">
                                                                            Delete
                                                                        </x-action-menu-item>
                                                                    </x-action-menu>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-12">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                </svg>
                                                <h3 class="mt-2 text-sm font-medium text-gray-900">No variant attributes</h3>
                                                <p class="mt-1 text-sm text-gray-500">Get started by creating your first variant attribute.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif($activeTab === 'tag-management')
                            <!-- Tag Management Content -->
                            <div class="space-y-6">
                                <!-- Form Section -->
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                    <div class="p-6">
                                        <div class="max-w-full">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <!-- Name Field -->
                                                <div>
                                                    <label for="tagName" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Name*
                                                    </label>
                                                    <input type="text" 
                                                           id="tagName"
                                                           wire:model="tagName" 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                           placeholder="Enter name">
                                                    @error('tagName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Color Field -->
                                                <div>
                                                    <label for="tagColor" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Color
                                                    </label>
                                                    <div class="flex items-center space-x-3">
                                                        <input type="color" 
                                                               id="tagColor"
                                                               wire:model="tagColor" 
                                                               class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                                                        <input type="text" 
                                                               wire:model="tagColor" 
                                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                               placeholder="#007bff">
                                                    </div>
                                                    @error('tagColor') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Description Field -->
                                                <div>
                                                    <label for="tagDescription" class="block text-sm font-medium text-gray-700 mb-1">
                                                        Description
                                                    </label>
                                                    <textarea id="tagDescription"
                                                              wire:model="tagDescription" 
                                                              rows="3"
                                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                              placeholder="Enter description (optional)"></textarea>
                                                    @error('tagDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="flex justify-end space-x-3 pt-6 md:col-span-2">
                                                    <button type="button" 
                                                            wire:click="clearTagForm"
                                                            class="button-secondary rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
                                                        Clear
                                                    </button>
                                                    <button type="button" 
                                                            wire:click="saveTag"
                                                            class="button-primary  rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
                                                        {{ $editingTag ? 'Update' : 'Save' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submissions Section -->
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                    <div class="px-6 py-4 border-b border-gray-200">
                                        <h3 class="text-lg font-medium text-gray-900">Submissions</h3>
                                    </div>
                                    <div class="overflow-hidden">
                                        @if($tags->count() > 0)
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tag Name</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        @foreach($tags as $tag)
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    {{ $tag->id }}
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <div class="flex items-center">
                                                                        <span class="inline-block w-3 h-3 rounded-full mr-2" style="background-color: {{ $tag->color }}"></span>
                                                                        <div class="text-sm font-medium text-gray-900">{{ $tag->name }}</div>
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <div class="flex items-center">
                                                                        <span class="inline-block w-4 h-4 rounded border mr-2" style="background-color: {{ $tag->color }}"></span>
                                                                        <span class="text-sm text-gray-600">{{ $tag->color }}</span>
                                                                    </div>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <button wire:click="toggleTagStatus({{ $tag->id }})"
                                                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $tag->is_active ? 'bg-blue-600' : 'bg-gray-200' }}">
                                                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $tag->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                                                    </button>
                                                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                                    <x-action-menu>
                                                                        <x-action-menu-item wire:click="editTag({{ $tag->id }})" icon="edit">
                                                                            Edit
                                                                        </x-action-menu-item>
                                                                        
                                                                        <x-action-menu-item 
                                                                            onclick="confirmDeleteTag({{ $tag->id }})"
                                                                            icon="trash"
                                                                            variant="danger">
                                                                            Delete
                                                                        </x-action-menu-item>
                                                                    </x-action-menu>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <!-- Pagination -->
                                            <div class="px-6 py-4 border-t border-gray-200">
                                                {{ $tags->links() }}
                                            </div>
                                        @else
                                            <div class="text-center py-12">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                                </svg>
                                                <h3 class="mt-2 text-sm font-medium text-gray-900">No tags found</h3>
                                                <p class="mt-1 text-sm text-gray-500">Get started by creating a new tag.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
function confirmDeleteCategory(categoryId) {
    if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        @this.call('deleteCategory', categoryId);
    }
}

function confirmDeleteVariantAttribute(variantAttributeId) {
    if (confirm('Are you sure you want to delete this variant attribute? This action cannot be undone.')) {
        @this.call('deleteVariantAttribute', variantAttributeId);
    }
}

function confirmDeleteTag(tagId) {
    if (confirm('Are you sure you want to delete this tag? This action cannot be undone.')) {
        @this.call('deleteTag', tagId);
    }
}
</script>
