<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\MediaAsset;
use App\Models\Category;

use App\Models\ProductTag;
use App\Models\VariantAttributeType;
use App\Models\VariantAttributeValue;
use App\Models\Catalog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Services\SkuGenerator;

class ProductManagement extends Component
{
    use WithFileUploads, WithPagination;

    // UI State
    public $showForm = false;
    public $showList = true;
    public $showModal = false;
    public $editingProduct = null;
    public $search = '';
    public $showDeleteModal = false;
    public $productToDelete = null;
    public $showSuccessModal = false;
    public $successMessage = '';
    
    // Deletion-related properties
    public $deleteType = 'product'; // 'product' or 'variant'
    public $selectedVariantToDelete = null;
    public $deletionDependencies = [];
    public $canDelete = true;
    public $deleteWarnings = [];
    public $isLoadingDependencies = false;

    // List view filter: products | addons | all
    public $listType = 'products';

    // Pagination
    public $perPage = 10;



    // Original values (for update mode comparison)
    public $original_product_type = null;


    public $original_name = null;
    public $original_sku = null;

    // Form Properties - General Information
    public $product_type = 'regular';
    public $name = '';
    public $slug = '';
    public $selectedCategories = [];

    public $selectedTags = [];
    public $short_description = '';
    public $description = '';
    public $images = []; // always treat as array; normalize single file before validate
    public $images_buffer = []; // temporary buffer to capture new selections
    public $existingImages = [];

    // Stock & Pricing specific images (separate from general images)
    public $stockImages = []; // for single products and add-ons in stock section
    public $existingStockImages = [];

    // Form Properties - Stock and Pricing (for regular/addon products)
    public $sku = '';
    public $barcode = '';
    public $cost_price = '';
    public $selling_price = '';
    public $compare_price = '';
    public $stock_quantity = '';
    public $min_quantity_alert = '';
    public $max_quantity_per_order = '';
    public $track_inventory = true;
    public $allow_backorders = false;

    // Form Properties - Shipping and Delivery
    public $weight_kg = '';
    public $length_cm = '';
    public $width_cm = '';
    public $height_cm = '';

    // SKU editing state
    public $skuManuallyEdited = false;
    protected $suppressSkuUpdated = false; // internal guard to avoid marking manual edit during auto updates

    private function setSkuAuto(string $sku): void
    {
        \Log::debug('PM:setSkuAuto', [
            'old_sku' => $this->sku,
            'new_sku' => $sku,
            'product_type' => $this->product_type,
            'skuManuallyEdited' => $this->skuManuallyEdited,
        ]);
        $this->suppressSkuUpdated = true;
        $this->sku = $sku;
        $this->suppressSkuUpdated = false;
    }

    // Form Properties - Product Add-ons
    public $selectedAddons = [];
    public $addonRequired = []; // map of addonId => bool (required)
    public $addonSearch = '';

    // Form Properties - Variant Management
    public $selectedVariantTypes = [];
    public $variantOptions = [];
    public $variantCombinations = [];

    // Form Properties - SEO Settings
    public $meta_title = '';
    public $meta_description = '';
    public $meta_keywords = '';
    public $focus_keywords = '';

    // Form Properties - Publishing Settings
    public $status = 'draft';
    public $featured = false;
    public $shippable = false;

    // Data Collections
    public $categories = [];
    public $species = [];
    // Category path fields replacing species in UI
    public $parent_category_id = '';
    public $subcategory_ids = [];
    public $tags = [];
    public $variantTypes = [];
    public $availableAddons = [];

    protected function rules()
    {
        $rules = [
            'product_type' => 'required|in:regular,variant,addon',
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:products,slug' . ($this->editingProduct ? ',' . $this->editingProduct : '')
            ],
            'parent_category_id' => 'required|exists:categories,id',
            'subcategory_ids' => ['array'],
            'subcategory_ids.*' => ['integer','exists:categories,id', function($attr,$val,$fail){
                $child = $this->categories->firstWhere('id', $val);
                if ($child && $child->parent_id != $this->parent_category_id) {
                    $fail('Selected subcategory does not belong to the chosen parent category.');
                }
            }],
            
            // For add-ons, short and long descriptions are required; optional otherwise
            'short_description' => ($this->product_type === 'addon') ? 'required|string|max:500' : 'nullable|string|max:500',
            'description' => ($this->product_type === 'addon') ? 'required|string' : 'nullable|string',
            // Always validate images as an array of files; count enforced below
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:2048',
            // Stock images validation
            'stockImages' => 'nullable|array',
            'stockImages.*' => 'nullable|image|max:2048',
            // SEO fields (make mandatory for all product types as requested)
            'meta_title' => 'required|string|max:60',
            'meta_description' => 'required|string|max:160',
            'meta_keywords' => 'required|string|max:255',
            'focus_keywords' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,archived',
            'featured' => 'boolean',
            'shippable' => 'boolean',
        ];

        // Add variant-specific rules
        if ($this->product_type === 'variant') {
            $rules = array_merge($rules, [
                'selectedVariantTypes' => 'required|array|min:1',
                'variantCombinations' => 'required|array|min:1',
                'variantCombinations.*.attributes' => 'required|array',
                'variantCombinations.*.variant_display_name' => 'nullable|string|max:255',
                'variantCombinations.*.sku' => 'required|string|max:100',
                'variantCombinations.*.barcode' => 'nullable|string|max:100',
                'variantCombinations.*.stock_quantity' => 'required|integer|min:0',
                'variantCombinations.*.min_quantity_alert' => 'required|integer|min:0',
                'variantCombinations.*.max_quantity_per_order' => ($this->product_type === 'addon') ? 'required|integer|in:10000' : 'required|integer|min:1',
                'variantCombinations.*.cost_price' => 'required|numeric|min:0',
                'variantCombinations.*.selling_price' => 'required|numeric|min:0',
                'variantCombinations.*.compare_price' => [
                    'nullable', 'numeric', 'min:0',
                    function ($attr, $value, $fail) {
                        // Extract the index from the attribute name (e.g., variantCombinations.0.compare_price -> 0)
                        $index = explode('.', $attr)[1];
                        $sellingPrice = $this->variantCombinations[$index]['selling_price'] ?? null;
                        
                        if ($value !== null && $sellingPrice !== null && $value < $sellingPrice) {
                            $fail('Compare price must be greater than or equal to Selling price.');
                        }
                    }
                ],
                'variantCombinations.*.track_inventory' => 'required|boolean',
                'variantCombinations.*.weight_kg' => 'nullable|numeric|min:0',
                'variantCombinations.*.length_cm' => 'nullable|numeric|min:0',
                'variantCombinations.*.width_cm' => 'nullable|numeric|min:0',
                'variantCombinations.*.height_cm' => 'nullable|numeric|min:0',
                'variantCombinations.*.image' => 'nullable|image|max:2048',
            ]);
        } else {
            // Regular/addon product rules
            $rules = array_merge($rules, [
                'sku' => [
                    'required',
                    'string',
                    'max:100',
                    // Use proper Unique rule: add deleted_at filter and ignore current variant when editing
                    Rule::unique('product_variants', 'sku')
                        ->where(fn($q) => $q->whereNull('deleted_at'))
                        ->when($this->editingProduct, function ($rule) {
                            $existingVariantId = optional(
                                Product::find($this->editingProduct)?->variants()->first()
                            )->id;
                            if ($existingVariantId) {
                                // Proper way to exclude current row from uniqueness check
                                $rule->ignore($existingVariantId);
                            }
                        }),
                ],
                'selling_price' => 'required|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'compare_price' => [
                    'nullable', 'numeric', 'min:0',
                    function ($attr, $value, $fail) {
                        if ($value !== null && $this->selling_price !== null && $value < $this->selling_price) {
                            $fail('Compare price must be greater than or equal to Selling price.');
                        }
                    }
                ],
                'stock_quantity' => 'required|integer|min:0',
                'min_quantity_alert' => 'nullable|integer|min:0',
                'max_quantity_per_order' => ($this->product_type === 'addon') ? 'required|integer|in:10000' : 'required|integer|min:1',
                'weight_kg' => 'nullable|numeric|min:0',
                'length_cm' => 'nullable|numeric|min:0',
                'width_cm' => 'nullable|numeric|min:0',
                'height_cm' => 'nullable|numeric|min:0',
                'selectedAddons' => ['array'],
                'selectedAddons.*' => [
                    Rule::exists('products', 'id')->where(fn($q) => $q->where('product_type', 'addon')->where('status', 'published')),
                ],
            ]);
        }

        return $rules;
    }

    protected function validateVariantCombinations()
    {
        if ($this->product_type !== 'variant') {
            return;
        }

        $combinations = [];
        $duplicateIndexes = [];

        foreach ($this->variantCombinations as $index => $combination) {
            $attributes = $combination['attributes'] ?? [];
            
            // Create a signature for this combination
            $signature = [];
            foreach ($this->selectedVariantTypes as $typeId) {
                $type = $this->variantTypes->find($typeId);
                if ($type) {
                    $value = $attributes[$type->name] ?? '';
                    if (empty($value)) {
                        $this->addError("variantCombinations.{$index}.incomplete", "Please select all variant options for this combination.");
                        return false;
                    }
                    $signature[] = $type->name . ':' . $value;
                }
            }
            
            $signatureString = implode('|', $signature);
            
            // Check for duplicates
            if (isset($combinations[$signatureString])) {
                $duplicateIndexes[] = $index;
                $duplicateIndexes[] = $combinations[$signatureString];
                $this->addError("variantCombinations.{$index}.duplicate", "This variant combination is duplicated.");
            } else {
                $combinations[$signatureString] = $index;
            }
        }

        // Mark all duplicate combinations
        foreach ($duplicateIndexes as $index) {
            $this->variantCombinations[$index]['is_duplicate'] = true;
        }

        return empty($duplicateIndexes);
    }

    public function mount()
    {
        $this->loadData();
        
        // Clear any duplicate flags on mount
        if ($this->product_type === 'variant' && !empty($this->variantCombinations)) {
            $this->validateAllCombinationsForDuplicates();
        }


    }

    public function loadData()
    {
        // Load all categories so that previously selected ones (even if inactive now)
        // still appear in the dropdown when editing an existing product.
        $this->categories = Category::orderBy('name')->get();
        // Species removed from UI; keep data available only if needed elsewhere
        // $this->species = Species::where('is_active', true)->orderBy('display_order')->get();
        $this->tags = ProductTag::orderBy('name')->get();
        $this->variantTypes = VariantAttributeType::with('values')->orderBy('name')->get();
        $this->availableAddons = Product::with(['variants.mediaAssets' => function($q) {
                $q->images()->ordered();
            }])
            ->where('product_type', 'addon')
            ->where('status', 'published')
            ->whereNull('deleted_at') // extra safety to exclude soft-deleted
            ->orderBy('name')
            ->get();
    }

    /**
     * Get the primary category ID from selectedCategories array
     * For backward compatibility with SKU generation
     */
    private function getPrimaryCategoryId()
    {
        // Use most specific selected category as primary (child preferred). If multiple children selected, take the first.
        if (is_array($this->subcategory_ids) && count($this->subcategory_ids)) {
            return $this->subcategory_ids[0];
        }
        return $this->parent_category_id ?: null;
    }

    /**
     * Get available subcategories for the selected parent category
     */
    public function getAvailableSubcategoriesProperty()
    {
        if (empty($this->parent_category_id)) {
            return collect();
        }
        
        return $this->categories->where('parent_id', $this->parent_category_id);
    }

    /**
     * Remove a subcategory from the selected subcategories
     */
    public function removeSubcategory($subcategoryId)
    {
        $this->subcategory_ids = array_values(array_filter($this->subcategory_ids, function($id) use ($subcategoryId) {
            return $id != $subcategoryId;
        }));
        
        // Clear any validation errors for subcategories
        $this->resetErrorBag(['subcategory_ids', 'subcategory_ids.*']);
        
        $this->handleSkuDriverChanged();
    }

    private function handleNameChangeCommon()
    {
        if (!$this->editingProduct || empty($this->slug)) {
            $this->slug = $this->generateUniqueSlug($this->name);
        }
        
        // Regenerate variant SKUs when product name changes
        if ($this->product_type === 'variant' && !empty($this->variantCombinations)) {
            foreach ($this->variantCombinations as &$combination) {
                if (isset($combination['attributes'])) {
                    $combination['sku'] = $this->generateVariantSku($combination['attributes']);
                }
            }
            
            // Re-validate after SKU changes
            $this->validateAllCombinationsForDuplicates();
        }
    }

    // When user selects files in the general info uploader, append them to images (up to 5 total)
    public function updatedImagesBuffer($files)
    {
        if (!is_array($this->images)) {
            $this->images = [];
        }
        $files = is_array($files) ? $files : [$files];

        $existingCount = is_array($this->existingImages) ? count($this->existingImages) : 0;
        $currentNew = count($this->images);
        $remaining = max(0, 5 - ($existingCount + $currentNew));
        if ($remaining <= 0) {
            // reset buffer and bail
            $this->images_buffer = [];
            return;
        }

        // Take only the remaining allowed files
        $toAdd = array_slice($files, 0, $remaining);

        // Append to the current images array
        foreach ($toAdd as $file) {
            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                $this->images[] = $file;
            }
        }

        // Clear the buffer so the file input can select again
        $this->images_buffer = [];
    }

    public function updatedProductType()
    {
        // If editing an existing product, warn user about product type change
        if ($this->editingProduct) {
            // For existing products, changing product type is generally not recommended
            // but we'll allow it and clear the form to prevent data corruption
            session()->flash('warning', 'Changing product type will clear all form data. Please re-enter the information appropriate for the new product type.');
        } else {
            // For new products, show a helpful message about the form being cleared
            session()->flash('message', 'Form cleared for ' . ucfirst($this->product_type) . ' product type. Please fill in the appropriate fields.');
        }
        
        // Clear ALL form data when product type changes to prevent contamination
        $this->clearAllFormData();
    }

    private function clearAllFormData()
    {
        // Store the current editing state and product type
        $currentEditingProduct = $this->editingProduct;
        $currentProductType = $this->product_type;
        
        // Use the existing resetForm method which handles most of the clearing
        $this->resetForm();
        
        // Restore the editing state and product type (since we only want to clear data, not exit edit mode)
        $this->editingProduct = $currentEditingProduct;
        $this->product_type = $currentProductType;
        
        // Clear additional fields that might not be in resetForm
        $this->images_buffer = [];
        $this->addonSearch = '';
        $this->suppressSkuUpdated = false;
        
        // Clear original values (used for edit mode comparison)
        $this->original_product_type = null;
        $this->original_species_id = null;

        $this->original_name = null;
        $this->original_sku = null;
        
        // Clear SKU and show button if ready
        $this->handleSkuDriverChanged();
    }

    // Detect manual SKU edits
    public function updatedSku($value)
    {
        // If we set it programmatically, don't mark manual
        if ($this->suppressSkuUpdated) {
            return;
        }
        // If user clears SKU, treat as auto mode again
        if (trim((string)$value) === '') {
            $this->skuManuallyEdited = false;
            // No auto-generate; keep empty until user clicks Generate
            return;
        }
        $this->skuManuallyEdited = true;
    }

    // Auto-generate SKU on key drivers if not manually edited
    public function updatedPrimaryCategoryId($value)
    {
        \Log::debug('PM:updatedPrimaryCategoryId', [
            'value' => $value,
            'product_type' => $this->product_type,
            'skuManuallyEdited' => $this->skuManuallyEdited,
        ]);
        $this->handleSkuDriverChanged();
    }

    public function updatedSpeciesId($value)
    {
        \Log::debug('PM:updatedSpeciesId', [
            'value' => $value,
            'product_type' => $this->product_type,
            'skuManuallyEdited' => $this->skuManuallyEdited,
        ]);
        $this->handleSkuDriverChanged();
    }

    public function updatedName($value)
    {
        \Log::debug('PM:updatedName', [
            'value' => $value,
            'product_type' => $this->product_type,
            'skuManuallyEdited' => $this->skuManuallyEdited,
        ]);
        // Keep original slug/variant-regen behavior
        $this->handleNameChangeCommon();
        $this->handleSkuDriverChanged();
    }

    // Handle parent category change to reset subcategories
    public function updatedParentCategoryId($value)
    {
        // Clear subcategories when parent category changes
        $this->subcategory_ids = [];
        
        // Clear any existing validation errors for subcategories
        $this->resetErrorBag(['subcategory_ids', 'subcategory_ids.*']);
        
        $this->handleSkuDriverChanged();
    }

    // Catch-all updated hook for different wire modifiers
    public function updated($name, $value)
    {
        if (in_array($name, ['product_type', 'parent_category_id', 'subcategory_ids', 'name'])) {
            $this->handleSkuDriverChanged();
        }
    }

    public function updatedSelectedVariantTypes()
    {
        $this->generateVariantCombinations();
        
        // Validate after generating new combinations
        if (!empty($this->variantCombinations)) {
            $this->validateAllCombinationsForDuplicates();
        }
    }

    private function generateUniqueSlug($name)
    {
        if (empty($name)) {
            return '';
        }

        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists($slug)
    {
        $query = Product::where('slug', $slug);
        
        if ($this->editingProduct) {
            $query->where('id', '!=', $this->editingProduct);
        }
        
        return $query->exists();
    }

    public function showAddForm()
    {
        $this->resetForm();
        // Ensure we have the latest categories/tags after any updates on other screens
        $this->loadData();
        $this->showForm = true;
        $this->showList = false;
    }

    public function editProduct($productId)
    {
        // Reload categories/tags so renamed categories appear immediately in dropdowns
        $this->loadData();
        $product = Product::with(['variants.mediaAssets', 'tags', 'addons', 'categories'])->findOrFail($productId);
        
        $this->editingProduct = $product->id;
        $this->product_type = $product->product_type;
        $this->name = $product->name;
        $this->slug = $product->slug;
        // Species removed entirely; using parent/subcategories only
        $this->short_description = $product->short_description;
        $this->description = $product->description;
        $this->meta_title = $product->meta_title;
        $this->meta_description = $product->meta_description;
        $this->meta_keywords = $product->meta_keywords;
        $this->status = $product->status;
        $this->featured = $product->featured;
        $this->shippable = $product->shippable;

        // Load tags
        $this->selectedTags = $product->tags->pluck('id')->toArray();
        
        // Load categories into parent + multiple subcategories
        $primary = $product->categories->firstWhere('pivot.is_primary', true);
        if ($primary && $primary->parent_id) {
            // Primary is a child: parent is the dropdown and child is selected in subcategories
            $this->parent_category_id = $primary->parent_id;
            $this->subcategory_ids = [$primary->id];
        } else {
            // Primary is a top-level parent (no subcategories selected)
            $this->parent_category_id = optional($primary)->id;
            $this->subcategory_ids = [];
        }
        // Include any additional subcategories that share the same parent
        foreach ($product->categories as $cat) {
            if ($cat->parent_id === $this->parent_category_id && $cat->id !== ($primary->id ?? null)) {
                $this->subcategory_ids[] = $cat->id;
            }
        }
        // Ensure uniqueness and keep a stable order
        $this->subcategory_ids = array_values(array_unique($this->subcategory_ids));

        // Load addons
        $this->selectedAddons = $product->addons->pluck('id')->toArray();
        // Load required flags from pivot for selected addons
        $this->addonRequired = [];
        foreach ($product->addons as $a) {
            $this->addonRequired[$a->id] = (bool)($a->pivot->is_required ?? false);
        }
        \Log::info('Loaded addonRequired map', [
            'product_id' => $product->id,
            'selected_addons' => $this->selectedAddons,
            'addon_required' => $this->addonRequired,
        ]);

        if ($product->product_type === 'variant') {
            // Restore chosen variant types from product JSON column
            $this->selectedVariantTypes = is_array($product->variant_attribute_type_ids)
                ? array_values($product->variant_attribute_type_ids)
                : [];

            $this->loadVariantData($product);
        } else {
            $this->loadSingleVariantData($product);
        }

        // Load existing images and separate by section based on product type and scope
        $this->existingImages = [];
        $this->existingStockImages = [];
        
        \Log::info('=== LOADING EXISTING IMAGES DEBUG ===', [
            'product_id' => $product->id,
            'product_type' => $this->product_type,
            'total_variants' => $product->variants->count()
        ]);
        
        // Load general images for variant products (stored at product level)
        if ($this->product_type === 'variant') {
            $generalMedia = $product->generalImages;
            \Log::info('Found general images for variant product', [
                'product_id' => $product->id,
                'general_media_count' => $generalMedia->count()
            ]);
            
            foreach ($generalMedia as $media) {
                $publicUrl = \App\Services\ImageService::getImageUrl($media->file_path ?: $media->file_url);
                $imageData = ['id' => $media->id, 'url' => $publicUrl];
                $this->existingImages[] = $imageData;
            }
        }
        
        // Load variant-specific images for regular/addon products
        $variant = $product->variants()->with('mediaAssets')->first();
        if ($variant && ($this->product_type === 'regular' || $this->product_type === 'addon')) {
            $variantMedia = $variant->mediaAssets()->where('scope', '!=', 'option')->orderBy('display_order')->get();
            \Log::info('Found variant media assets', [
                'variant_id' => $variant->id,
                'media_count' => $variantMedia->count()
            ]);
            
            foreach ($variantMedia as $media) {
                $publicUrl = \App\Services\ImageService::getImageUrl($media->file_path ?: $media->file_url);
                $imageData = ['id' => $media->id, 'url' => $publicUrl];
                
                // Categorize based on product type and image properties
                if ($this->product_type === 'regular') {
                    // For regular products: primary images go to stock section, secondary to general
                    if ($media->is_primary) {
                        $this->existingStockImages[] = $imageData;
                    } else {
                        $this->existingImages[] = $imageData;
                    }
                } elseif ($this->product_type === 'addon') {
                    // For addons: only primary images (stock section)
                    if ($media->is_primary) {
                        $this->existingStockImages[] = $imageData;
                    }
                }
            }
        }
        
        \Log::info('=== FINAL EXISTING IMAGES LOADED ===', [
            'existingImages_count' => count($this->existingImages),
            'existingStockImages_count' => count($this->existingStockImages),
            'existingImages_data' => $this->existingImages
        ]);

        $this->showForm = true;
        $this->showList = false;
    }

    private function loadVariantData($product)
    {
        // Load variant combinations
        $this->variantCombinations = [];
        foreach ($product->variants as $variant) {
            // Get existing options table image for this variant
            $existingImage = null;
            $existingImageId = null;
            $optionsImage = $variant->mediaAssets()
                ->where('scope', 'option')
                ->first();
            
            if ($optionsImage) {
                $existingImageId = $optionsImage->id;
                $existingImage = \App\Services\ImageService::getImageUrl(
                    $optionsImage->file_path ?: $optionsImage->file_url,
                    $optionsImage->file_url  // Use file_url as defaultImage fallback
                );
                
                \Log::info('Options image found for variant', [
                    'variant_id' => $variant->id,
                    'media_id' => $optionsImage->id,
                    'file_path' => $optionsImage->file_path,
                    'file_url' => $optionsImage->file_url,
                    'generated_url' => $existingImage
                ]);
            } else {
                \Log::info('No options image found for variant', ['variant_id' => $variant->id]);
            }

            // Use structured variant attributes only
            $attributes = (array) ($variant->variant_attributes ?? []);

            \Log::info('Loading variant combination', [
                'variant_id' => $variant->id,
                'original_attributes' => $variant->variant_attributes,
            ]);
            
            // Build UI display name from attributes for convenience
            $variantDisplayName = implode(' - ', array_filter(is_array($attributes) ? $attributes : []));

            $combinationData = [
                'id' => $variant->id,
                'variant_display_name' => $variantDisplayName,
                'attributes' => is_array($attributes) ? $attributes : [],
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'cost_price' => $variant->cost_price,
                'selling_price' => $variant->selling_price,
                'compare_price' => $variant->compare_price,
                'stock_quantity' => $variant->stock_quantity,
                'min_quantity_alert' => $variant->min_quantity_alert,
                'max_quantity_per_order' => ($this->product_type === 'addon') ? 10000 : $variant->max_quantity_per_order,
                'track_inventory' => $variant->track_inventory,
                'allow_backorders' => $variant->allow_backorders,
                'weight_kg' => $variant->weight_kg,
                'length_cm' => $variant->length_cm,
                'width_cm' => $variant->width_cm,
                'height_cm' => $variant->height_cm,
                'status' => $variant->status,
                'is_primary' => (bool)$variant->is_primary,
                'existing_image' => $existingImage, // URL
                'existing_image_id' => $existingImageId, // ID for precise delete
            ];
            
            \Log::info('Adding variant combination', [
                'variant_id' => $variant->id,
                'existing_image' => $existingImage,
                'existing_image_id' => $existingImageId
            ]);
            
            $this->variantCombinations[] = $combinationData;
        }
    }

    private function loadSingleVariantData($product)
    {
        $variant = $product->variants->first();
        if ($variant) {
            $this->sku = $variant->sku;
            $this->barcode = $variant->barcode;
            $this->cost_price = $variant->cost_price;
            $this->selling_price = $variant->selling_price;
            $this->compare_price = $variant->compare_price;
            $this->stock_quantity = $variant->stock_quantity;
            $this->min_quantity_alert = $variant->min_quantity_alert;
            $this->max_quantity_per_order = ($this->product_type === 'addon') ? 10000 : $variant->max_quantity_per_order;
            $this->track_inventory = $variant->track_inventory;
            $this->allow_backorders = $variant->allow_backorders;
            $this->weight_kg = $variant->weight_kg;
            $this->length_cm = $variant->length_cm;
            $this->width_cm = $variant->width_cm;
            $this->height_cm = $variant->height_cm;
        }
    }

    public function generateVariantCombinations()
    {
        if (empty($this->selectedVariantTypes)) {
            $this->variantCombinations = [];
            return;
        }

        $combinations = [[]];
        
        foreach ($this->selectedVariantTypes as $typeId) {
            $type = $this->variantTypes->find($typeId);
            if (!$type) continue;

            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($type->values as $value) {
                    $newCombination = $combination;
                    $newCombination[$type->name] = $value->value;
                    $newCombinations[] = $newCombination;
                }
            }
            $combinations = $newCombinations;
        }

        $this->variantCombinations = [];
        foreach ($combinations as $combination) {
            $variantName = implode(' - ', $combination);
            $this->variantCombinations[] = [
                'id' => null,
                'variant_display_name' => $variantName,
                'attributes' => $combination,
                'sku' => $this->generateVariantSku($combination),
                'barcode' => '',
                'cost_price' => '',
                'selling_price' => '',
                'compare_price' => '',
                'stock_quantity' => 0,
                'min_quantity_alert' => 5,
                'max_quantity_per_order' => ($this->product_type === 'addon') ? 10000 : null,
                'track_inventory' => true,
                'allow_backorders' => false,
                'weight_kg' => '',
                'length_cm' => '',
                'width_cm' => '',
                'height_cm' => '',
                'status' => 'active',
            ];
        }
    }

    private function generateVariantSku($attributes)
    {
        // Unified: build from base-with-number + attribute suffix
        // If fields not ready, fallback to old style temporary name-based code to avoid blank
        if ($this->fieldsReadyForSku()) {
            $baseWithNumber = $this->getOrMakeBaseWithNumber();
            return app(SkuGenerator::class)->makeVariantFromBase($baseWithNumber, $attributes);
        }

        // Fallback temporary code (will be overwritten once fields are ready)
        $baseSku = $this->name ? Str::upper(Str::slug($this->name, '')) : 'PRODUCT';
        if (strlen($baseSku) > 10) {
            $baseSku = substr($baseSku, 0, 10);
        }
        $variantPart = '';
        foreach ($attributes as $key => $value) {
            $code = $this->generateVariantCode($key, $value);
            $variantPart .= '-' . $code;
        }
        return $baseSku . $variantPart;
    }

    private function generateVariantCode($attributeName, $attributeValue)
    {
        // Create more meaningful codes based on attribute type
        $value = Str::upper($attributeValue);
        
        switch (Str::lower($attributeName)) {
            case 'color':
                return $this->getColorCode($value);
            case 'size':
                return $this->getSizeCode($value);
            case 'material':
                return substr($value, 0, 3);
            default:
                return substr($value, 0, 3);
        }
    }

    private function getColorCode($color)
    {
        $colorCodes = [
            'BLACK' => 'BLK',
            'WHITE' => 'WHT',
            'RED' => 'RED',
            'BLUE' => 'BLU',
            'GREEN' => 'GRN',
            'YELLOW' => 'YEL',
            'ORANGE' => 'ORG',
            'PURPLE' => 'PUR',
            'PINK' => 'PNK',
            'BROWN' => 'BRN',
            'GRAY' => 'GRY',
            'GREY' => 'GRY',
        ];
        
        return $colorCodes[$color] ?? substr($color, 0, 3);
    }

    // Generate SKU for add-on products following pattern: ADDON-{CATEGORY}-{SPECIES}-{NUMBER}


    private function generateAddonSku()
    {
        // Unified: use service with configured codes and product name prefix
        if (!$this->fieldsReadyForSku()) {
            return '';
        }
        return app(SkuGenerator::class)->makeForProduct('addon', (int)$this->getPrimaryCategoryId(), (string)$this->name);
    }

    // Determine if all required fields are available for SKU generation
    private function fieldsReadyForSku(): bool
    {
        return !empty($this->product_type)
            && !empty($this->name)
            && !empty($this->getPrimaryCategoryId());
    }

    // Compute a base-with-number for variants (e.g., VP-CL-CT-PR-0002)
    private function getOrMakeBaseWithNumber(): string
    {
        return app(SkuGenerator::class)->makeBaseWithNumber(
            (string)$this->product_type,
            (int)$this->getPrimaryCategoryId(),
            (string)$this->name
        );
    }

    // Handle when user changes any driver fields. Clears SKU and shows generate button
    private function handleSkuDriverChanged(): void
    {
        if ($this->product_type === 'variant') {
            // Regenerate combination SKUs using the unified generator when drivers change
            foreach ($this->variantCombinations as $i => $combination) {
                $this->variantCombinations[$i]['sku'] = $this->fieldsReadyForSku()
                    ? $this->generateVariantSku($combination['attributes'] ?? [])
                    : '';
            }
            return;
        }
        // For regular/addon products, auto-generate SKU when drivers are ready (no extra click)
        if (!$this->fieldsReadyForSku()) {
            // Not enough info: don't override manual edits
            if (!$this->skuManuallyEdited) {
                $this->setSkuAuto('');
            }
            return;
        }
        // Fields are ready: if user hasn't manually edited, generate immediately
        if (!$this->skuManuallyEdited) {
            $type = ($this->product_type === 'addon') ? 'addon' : 'regular';
            $sku = app(SkuGenerator::class)->makeForProduct($type, (int)$this->getPrimaryCategoryId(), (string)$this->name);
            $this->setSkuAuto($sku);
        }
    }

    // Explicit handler callable from Blade change/input events
    public function skuDriversChanged(): void
    {
        $this->handleSkuDriverChanged();
    }



    private function getSizeCode($size)
    {
        $sizeCodes = [
            'EXTRA SMALL' => 'XS',
            'SMALL' => 'S',
            'MEDIUM' => 'M',
            'LARGE' => 'L',
            'EXTRA LARGE' => 'XL',
            'XXL' => 'XXL',
            'XXXL' => 'XXXL',
        ];
        
        return $sizeCodes[Str::upper($size)] ?? substr($size, 0, 3);
    }

    public function addVariantCombination()
    {
        $this->variantCombinations[] = [
            'id' => null,
            'variant_display_name' => '',
            'attributes' => [],
            'sku' => '',
            'barcode' => '',
            'cost_price' => '',
            'selling_price' => '',
            'compare_price' => '',
            'stock_quantity' => 0,
            'min_quantity_alert' => 5,
            'max_quantity_per_order' => ($this->product_type === 'addon') ? 10000 : null,
            'track_inventory' => true,
            'allow_backorders' => false,
            'weight_kg' => '',
            'length_cm' => '',
            'width_cm' => '',
            'height_cm' => '',
            'status' => 'active',
        ];
        
        // Validate after adding new combination
        $this->validateAllCombinationsForDuplicates();
    }

    public function updateVariantAttribute($combinationIndex, $attributeName, $attributeValue)
    {
        if (isset($this->variantCombinations[$combinationIndex])) {
            // Update the attribute
            $this->variantCombinations[$combinationIndex]['attributes'][$attributeName] = $attributeValue;
            
            // Regenerate display name (computed only for UI)
            $attributes = $this->variantCombinations[$combinationIndex]['attributes'];
            $this->variantCombinations[$combinationIndex]['variant_display_name'] = implode(' - ', array_filter($attributes));
            
            // Regenerate SKU
            $this->variantCombinations[$combinationIndex]['sku'] = $this->generateVariantSku($attributes);
        }
    }

    // Alternative method for handling updates via wire:model
    public function updatedVariantCombinations($value, $key)
    {
        // Parse the key to get combination index and field
        $keyParts = explode('.', $key);
        
        if (count($keyParts) >= 3 && $keyParts[1] === 'attributes') {
            $combinationIndex = (int)$keyParts[0];
            $attributeName = $keyParts[2];
            
            if (isset($this->variantCombinations[$combinationIndex])) {
                // Regenerate display name (computed only for UI)
                $attributes = $this->variantCombinations[$combinationIndex]['attributes'];
                $this->variantCombinations[$combinationIndex]['variant_display_name'] = implode(' - ', array_filter($attributes));
                
                // Regenerate SKU
                $this->variantCombinations[$combinationIndex]['sku'] = $this->generateVariantSku($attributes);
            }
        }
        
        // Always validate all combinations after any change
        $this->validateAllCombinationsForDuplicates();
    }

    private function checkForDuplicateVariants($currentIndex)
    {
        // First, clear all duplicate flags and errors for a fresh check
        $this->resetErrorBag();
        foreach ($this->variantCombinations as $i => $combination) {
            $this->variantCombinations[$i]['is_duplicate'] = false;
        }
        
        // Now check all combinations for duplicates
        $this->validateAllCombinationsForDuplicates();
    }
    
    private function validateAllCombinationsForDuplicates()
    {
        // First, clear all duplicate flags and errors
        $this->resetErrorBag();
        foreach ($this->variantCombinations as $i => $combination) {
            $this->variantCombinations[$i]['is_duplicate'] = false;
        }
        
        // If no variant types selected, nothing to validate
        if (empty($this->selectedVariantTypes)) {
            return;
        }
        
        $combinations = [];
        $duplicateGroups = [];
        
        foreach ($this->variantCombinations as $index => $combination) {
            $attributes = $combination['attributes'] ?? [];
            
            // Build attribute values for this combination
            $attributeValues = [];
            $hasAllAttributes = true;
            
            foreach ($this->selectedVariantTypes as $typeId) {
                $type = $this->variantTypes->find($typeId);
                if ($type) {
                    $value = trim($attributes[$type->name] ?? '');
                    if (empty($value)) {
                        $hasAllAttributes = false;
                        break;
                    }
                    $attributeValues[$type->name] = $value;
                }
            }
            
            // Skip incomplete combinations
            if (!$hasAllAttributes) {
                continue;
            }
            
            // Create a signature for this combination
            ksort($attributeValues);
            $signatureString = json_encode($attributeValues);
            
            // Check for duplicates
            if (isset($combinations[$signatureString])) {
                // This is a duplicate
                if (!isset($duplicateGroups[$signatureString])) {
                    $duplicateGroups[$signatureString] = [$combinations[$signatureString]];
                }
                $duplicateGroups[$signatureString][] = $index;
            } else {
                $combinations[$signatureString] = $index;
            }
        }
        
        // Mark all duplicates and add errors
        foreach ($duplicateGroups as $signature => $indexes) {
            foreach ($indexes as $index) {
                $this->variantCombinations[$index]['is_duplicate'] = true;
                $this->addError("variantCombinations.{$index}.duplicate", 'This variant combination already exists.');
            }
        }
    }



    public function removeVariantCombination($index)
    {
        unset($this->variantCombinations[$index]);
        $this->variantCombinations = array_values($this->variantCombinations);
        
        // Re-validate all combinations after removal
        $this->validateAllCombinationsForDuplicates();
    }

    public function toggleVariantType($typeId)
    {
        if (in_array($typeId, $this->selectedVariantTypes)) {
            $this->selectedVariantTypes = array_filter($this->selectedVariantTypes, function($id) use ($typeId) {
                return $id != $typeId;
            });
        } else {
            if (count($this->selectedVariantTypes) < 3) {
                $this->selectedVariantTypes[] = $typeId;
            }
        }
        $this->generateVariantCombinations();
    }





    public function removeTag($tagId)
    {
        $this->selectedTags = array_filter($this->selectedTags, function($id) use ($tagId) {
            return $id != $tagId;
        });
    }

    public function removeCategory($categoryId)
    {
        $this->selectedCategories = array_filter($this->selectedCategories, function($id) use ($categoryId) {
            return $id != $categoryId;
        });
    }

    public function removeVariantType($typeId)
    {
        $this->selectedVariantTypes = array_filter($this->selectedVariantTypes, function($id) use ($typeId) {
            return $id != $typeId;
        });
        $this->generateVariantCombinations();
    }

    public function addAddon($addonId)
    {
        if (!in_array($addonId, $this->selectedAddons)) {
            $this->selectedAddons[] = $addonId;
            // default required = false when adding
            $this->addonRequired[$addonId] = $this->addonRequired[$addonId] ?? false;
        }
    }

    public function removeAddon($index)
    {
        if (!isset($this->selectedAddons[$index])) return;
        $removedId = $this->selectedAddons[$index];
        unset($this->selectedAddons[$index]);
        $this->selectedAddons = array_values($this->selectedAddons);
        // also clear the required flag for that addon id
        unset($this->addonRequired[$removedId]);
    }

    public function removeImage($index)
    {
        if (isset($this->images[$index])) {
            unset($this->images[$index]);
            $this->images = array_values($this->images);
        }
    }

    public function removeExistingImage($index)
    {
        if (!isset($this->existingImages[$index])) return;

        $item = $this->existingImages[$index];
        // Support both array-of-url (legacy) and array-of-objects {id,url}
        $mediaId = is_array($item) ? ($item['id'] ?? null) : null;

        if ($mediaId) {
            try {
                $media = \App\Models\MediaAsset::find($mediaId);
                if ($media) { $media->delete(); }
            } catch (\Throwable $e) {
                \Log::warning('Failed to delete existing general image', ['id' => $mediaId, 'error' => $e->getMessage()]);
            }
        }

        unset($this->existingImages[$index]);
        $this->existingImages = array_values($this->existingImages);
    }

    // Clear currently selected (unsaved) add-on image so the "+" button appears again
    public function clearAddonImage()
    {
        // Reset to empty array (we treat images uniformly as array)
        $this->images = [];
    }

    // Clear currently selected (unsaved) single product image so the "+" button appears again
    public function clearSingleImage()
    {
        // Reset to empty array (we treat images uniformly as array)
        $this->images = [];
    }

    // Clear currently selected (unsaved) add-on stock image
    public function clearAddonStockImage()
    {
        $this->stockImages = [];
    }

    // Clear currently selected (unsaved) single product stock image
    public function clearSingleStockImage()
    {
        $this->stockImages = [];
    }

    // Remove existing stock image
    public function removeExistingStockImage($index)
    {
        if (!isset($this->existingStockImages[$index])) return;

        $item = $this->existingStockImages[$index];
        $mediaId = is_array($item) ? ($item['id'] ?? null) : null;

        if ($mediaId) {
            try {
                $media = \App\Models\MediaAsset::find($mediaId);
                if ($media) { $media->delete(); }
            } catch (\Throwable $e) {
                \Log::warning('Failed to delete existing stock image', ['id' => $mediaId, 'error' => $e->getMessage()]);
            }
        }

        unset($this->existingStockImages[$index]);
        $this->existingStockImages = array_values($this->existingStockImages);
    }



    // Clear a temporary image from a variant option row
    public function clearVariantImage(int $index): void
    {
        if (!isset($this->variantCombinations[$index])) {
            return;
        }

        $file = $this->variantCombinations[$index]['image'] ?? null;

        // Best-effort: delete the temp file if present
        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            try { $file->delete(); } catch (\Throwable $e) { /* ignore */ }
        }

        // Set to null to keep the key shape consistent
        $this->variantCombinations[$index]['image'] = null;
    }

    // Drag-and-drop ordering: Selected Add-ons (right panel)
    public function reorderSelectedAddons(array $orderedAddonIds): void
    {
        if (empty($orderedAddonIds)) return;

        // Rebuild selectedAddons in the new order, keeping only known IDs
        $newSelected = [];
        foreach ($orderedAddonIds as $aid) {
            if (in_array($aid, $this->selectedAddons, true)) {
                $newSelected[] = $aid;
            }
        }
        $this->selectedAddons = $newSelected;

        // Persist immediately if editing an existing product
        if ($this->editingProduct) {
            $product = Product::find($this->editingProduct);
            if ($product) {
                $addonData = [];
                foreach ($this->selectedAddons as $index => $addonId) {
                    $addonData[$addonId] = [
                        'is_required' => (bool)($this->addonRequired[$addonId] ?? false),
                        'display_order' => $index,
                    ];
                }
                $product->addons()->sync($addonData);
            }
        }
    }

    public function removeExistingVariantImage(int $index): void
    {
        if (!isset($this->variantCombinations[$index])) { return; }

        $id = $this->variantCombinations[$index]['existing_image_id'] ?? null;
        if ($id) {
            try {
                $media = \App\Models\MediaAsset::find($id);
                if ($media) { $media->delete(); }
            } catch (\Throwable $e) {
                \Log::warning('Failed to delete existing variant options image', ['id' => $id, 'error' => $e->getMessage()]);
            }
        }

        // Remove preview and id so it won’t get restored
        $this->variantCombinations[$index]['existing_image'] = null;
        $this->variantCombinations[$index]['existing_image_id'] = null;
    }

    public function saveProduct()
    {
        // Ensure SKU exists for non-variant types before saving; auto-generate if possible
        if ($this->product_type !== 'variant' && empty($this->sku)) {
            if ($this->fieldsReadyForSku() && !$this->skuManuallyEdited) {
                $type = ($this->product_type === 'addon') ? 'addon' : 'regular';
                $this->setSkuAuto(app(SkuGenerator::class)->makeForProduct($type, (int)$this->getPrimaryCategoryId(), (string)$this->name));
            } else {
                // Do not return here; we still want to display other validation errors (SEO, images, etc.)
                $this->addError('sku', 'Please complete required fields to auto-generate SKU.');
            }
        }

        \Log::debug('PM:saveProduct:start', [
            'product_type' => $this->product_type,
            'name' => $this->name,
            'category_id' => $this->parent_category_id,
            'primary_category_id' => $this->getPrimaryCategoryId(),
            'sku' => $this->sku,
            'editingProduct' => $this->editingProduct,
        ]);

        // Normalize images to an array before validation
        if ($this->images instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $this->images = [$this->images];
        }
        if (!is_array($this->images)) { $this->images = []; }

        // Normalize stock images to an array before validation
        if ($this->stockImages instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $this->stockImages = [$this->stockImages];
        }
        if (!is_array($this->stockImages)) { $this->stockImages = []; }

        // If buffer still has files (edge case), merge respecting limit 5 (only for variant products)
        if ($this->product_type === 'variant' && is_array($this->images_buffer) && count($this->images_buffer)) {
            $existingCount = is_array($this->existingImages) ? count($this->existingImages) : 0;
            $remaining = max(0, 5 - ($existingCount + count($this->images)));
            if ($remaining > 0) {
                $toAdd = array_slice($this->images_buffer, 0, $remaining);
                foreach ($toAdd as $file) {
                    if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                        $this->images[] = $file;
                    }
                }
            }
            $this->images_buffer = [];
        }

        // Pre-check images to attach an error early so it displays together with other field errors
        // Only for regular and variant products (not add-ons)
        if (in_array($this->product_type, ['regular','variant'])) {
            $newCount = is_array($this->images) ? count($this->images) : 0;
            $existingCount = is_array($this->existingImages) ? count($this->existingImages) : 0;
            if (($newCount + $existingCount) < 1) {
                $this->addError('images', 'At least one image is required in General Information.');
            }
        }

        // Now run validation for all other fields (SEO, name, species, etc.)
        $this->validate();

        // Enforce at least one image in General section only for regular and variant products
        if (in_array($this->product_type, ['regular','variant'])) {
            $newCount = is_array($this->images) ? count($this->images) : 0;
            $existingCount = is_array($this->existingImages) ? count($this->existingImages) : 0;
            if (($newCount + $existingCount) < 1) {
                $this->addError('images', 'At least one image is required in General Information.');
                return; // stop save to display error under Images
            }
        }

        // Image validation by product type
        if ($this->product_type === 'addon' || $this->product_type === 'regular') {
            // Add-ons and Single Products: Exactly 1 image in Stock & Pricing (PRIMARY)
            $newStockCount = is_array($this->stockImages) ? count($this->stockImages) : 0;
            $existingStockCount = is_array($this->existingStockImages) ? count($this->existingStockImages) : 0;
            if (($newStockCount + $existingStockCount) !== 1) {
                $productTypeLabel = $this->product_type === 'addon' ? 'Add-on' : 'Single product';
                $this->addError('stockImages', $productTypeLabel . ' requires exactly one image in Stock & Pricing.');
                return;
            }
            if ($newStockCount > 1) {
                $this->stockImages = [$this->stockImages[0]];
            }

            // Single Products: Require 1-5 images in General Information (secondary)
            if ($this->product_type === 'regular') {
                $newGeneralCount = is_array($this->images) ? count($this->images) : 0;
                $existingGeneralCount = is_array($this->existingImages) ? count($this->existingImages) : 0;
                $totalGeneral = $newGeneralCount + $existingGeneralCount;
                if ($totalGeneral < 1) {
                    $this->addError('images', 'Please upload at least one image in General Information.');
                    return;
                }
                if ($totalGeneral > 5) {
                    $this->addError('images', 'You can upload a maximum of 5 images in General Information.');
                    return;
                }
                // If user selected more than allowed new images, trim to fit 5 total
                if ($newGeneralCount > 0 && $totalGeneral > 5) {
                    $allowedNew = max(0, 5 - $existingGeneralCount);
                    $this->images = array_slice($this->images, 0, $allowedNew);
                }
            }
        } else {
            // Variant Products: 1-5 images in General Information (secondary), PRIMARY images in options table
            $newCount = is_array($this->images) ? count($this->images) : 0;
            $existingCount = is_array($this->existingImages) ? count($this->existingImages) : 0;
            $total = $newCount + $existingCount;
            if ($total < 1) {
                $this->addError('images', 'Please upload at least one image in General Information.');
                return;
            }
            if ($total > 5) {
                $this->addError('images', 'You can upload a maximum of 5 images in General Information.');
                return;
            }
            // If user selected more than allowed new images, trim to fit 5 total
            if ($newCount > 0 && $total > 5) {
                $allowedNew = max(0, 5 - $existingCount);
                $this->images = array_slice($this->images, 0, $allowedNew);
            }
        }
        
        // Prevent self-attachment
        if ($this->editingProduct && !empty($this->selectedAddons) && in_array($this->editingProduct, $this->selectedAddons, true)) {
            $this->addError('selectedAddons', 'A product cannot reference itself as an add-on.');
            return;
        }
        
        // Additional validation for variant combinations
        if ($this->product_type === 'variant') {
            if (!$this->validateVariantCombinations()) {
                return; // Stop if validation fails
            }
            // Ensure exactly one primary variant is selected
            $primaryCount = collect($this->variantCombinations)->where('is_primary', true)->count();
            if ($primaryCount !== 1) {
                $this->addError('variantPrimary', 'Please select exactly one primary variant.');
                return;
            }
        }

        try {
            DB::beginTransaction();

            // Get E-commerce catalog ID
            $ecommerceCatalog = Catalog::where('name', 'e-commerce')->first();
            $catalogId = $ecommerceCatalog ? $ecommerceCatalog->id : 1; // Default to 1 if not found

            $productData = [
                'name' => $this->name,
                'slug' => $this->slug ?: $this->generateUniqueSlug($this->name),
                'short_description' => $this->short_description,
                'description' => $this->description,
                'catalog_id' => $catalogId,
                'category_id' => $this->parent_category_id,
                'product_type' => $this->product_type,
                'featured' => $this->featured,
                'shippable' => $this->shippable,
                'status' => $this->status,
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'meta_keywords' => $this->meta_keywords,
            ];

            if ($this->editingProduct) {
                $product = Product::findOrFail($this->editingProduct);
                $product->update($productData);

                // Persist chosen variant type IDs on update
                if ($this->product_type === 'variant') {
                    $product->variant_attribute_type_ids = array_values($this->selectedVariantTypes ?? []);
                    $product->save();
                }

                $message = 'Product updated successfully!';
            } else {
                $product = Product::create($productData);
                $message = 'Product created successfully!';

                // Persist chosen variant type IDs for variant products
                if ($this->product_type === 'variant') {
                    $product->variant_attribute_type_ids = array_values($this->selectedVariantTypes ?? []);
                    $product->save();
                }
            }

            // Save tags
            $product->tags()->sync($this->selectedTags);

            // Save categories using parent/subcategory path
            $categoryData = [];
            $primaryId = $this->getPrimaryCategoryId(); // first child if any, else parent
            if ($primaryId) {
                $categoryData[$primaryId] = [
                    'is_primary' => true,
                    'display_order' => 0,
                ];
            }
            if ($this->parent_category_id) {
                // Attach parent only if it is different from the primary category to avoid overwriting primary flag
                if ($this->parent_category_id != $primaryId) {
                    $categoryData[$this->parent_category_id] = [
                        'is_primary' => false,
                        'display_order' => 1,
                    ];
                }
            }
            // Attach additional subcategories (non-primary), keeping stable order
            if (is_array($this->subcategory_ids)) {
                $order = 2;
                foreach ($this->subcategory_ids as $cid) {
                    // Skip if already primary or same as parent entry to avoid duplicates
                    if ($cid == $primaryId || $cid == $this->parent_category_id) { continue; }
                    $categoryData[$cid] = [
                        'is_primary' => false,
                        'display_order' => $order++,
                    ];
                }
            }
            if (!empty($categoryData)) {
                $product->categories()->sync($categoryData);
            } else {
                $product->categories()->detach();
            }

            // Save addons
            if (!empty($this->selectedAddons)) {
                $addonData = [];
                foreach ($this->selectedAddons as $index => $addonId) {
                    $addonData[$addonId] = [
                        'is_required' => (bool)($this->addonRequired[$addonId] ?? false),
                        'display_order' => $index,
                    ];
                }
                $product->addons()->sync($addonData);
            } else {
                $product->addons()->detach();
            }

            // Save variants
            if ($this->product_type === 'variant') {
                $this->saveVariantCombinations($product);
            } else {
                $this->saveSingleVariant($product);
            }

            // Note: Image cleanup is now handled only through explicit user actions
            // (removeExistingImage, removeExistingStockImage, removeExistingVariantImage)
            // This prevents accidental deletion of images during updates

            // Handle image uploads
            $uploadResult = $this->handleImageUploads($product);
            if (!$uploadResult['success']) {
                DB::rollBack();
                $this->addError('images', $uploadResult['message']);
                return;
            }

            DB::commit();

            // After successful save, update the existing images arrays to include newly uploaded images
            // and clear the temporary upload arrays
            $this->updateExistingImagesAfterSave($product);
            $this->images = [];
            $this->stockImages = [];
            
            $this->showSuccessModal = true;
            $this->successMessage = $message;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Product save failed', [
                'error' => $e->getMessage(),
                'product_type' => $this->product_type,
                'name' => $this->name,
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('general', 'An error occurred while saving the product. Please try again.');
        }
    }

    private function normalizeVariantStatus($status): string
    {
        $allowed = ['active', 'inactive', 'archived'];
        if (is_null($status)) return 'active';
        if (is_bool($status) || is_int($status) || in_array($status, ['1','0','true','false'], true)) {
            return filter_var($status, FILTER_VALIDATE_BOOL) ? 'active' : 'inactive';
        }
        $status = strtolower((string)$status);
        return in_array($status, $allowed, true) ? $status : 'active';
    }

    public function resetPrimaryExcept(int $index): void
    {
        foreach ($this->variantCombinations as $i => &$row) {
            if ($i !== $index) {
                $row['is_primary'] = false;
            }
        }
        unset($row);
        $this->resetErrorBag('variantPrimary');
    }

    private function determineImageSection($media)
    {
        // Determine image section based on file path (or URL fallback) and context
        $path = $media->file_path ?: $media->file_url; // fallback for legacy rows missing file_path

        if (is_string($path) && stripos($path, '/options') !== false) {
            return 'options_table';
        } elseif (!empty($media->is_primary)) {
            return 'stock_pricing';
        } else {
            return 'general_info';
        }
    }

    private function saveVariantCombinations($product)
    {
        // For updates: preserve existing options table images using variant ID as key
        $existingOptionsImages = [];
        $keepVariantIds = [];

        // Helper to build a stable signature from attributes (order-independent)
        $buildSignature = function ($attrs) {
            if (!is_array($attrs)) return null;
            ksort($attrs);
            $parts = [];
            foreach ($attrs as $k => $v) {
                if ($v === null || $v === '') continue;
                $parts[] = $k . ':' . $v;
            }
            return implode('|', $parts);
        };

        // Index existing variants by ID and by attribute signature (when available)
        $existingById = [];
        $existingBySignature = [];
        if ($this->editingProduct) {
            $existing = $product->variants()->get();
            foreach ($existing as $ev) {
                $existingById[$ev->id] = $ev;
                $sig = $buildSignature($ev->variant_attributes ?? null);
                if ($sig) $existingBySignature[$sig] = $ev;
            }

            // Collect existing options table images using variant ID as key
            foreach ($this->variantCombinations as $index => $combination) {
                if (isset($combination['existing_image_id']) && $combination['existing_image_id'] && isset($combination['id'])) {
                    $media = MediaAsset::find($combination['existing_image_id']);
                    if ($media) {
                        $existingOptionsImages[$combination['id']] = $media;
                    }
                }
            }
        }

        foreach ($this->variantCombinations as $rowIndex => $combination) {
            // Normalize status consistently across types
            $status = $this->normalizeVariantStatus($combination['status'] ?? null);

            $variantData = [
                'product_id' => $product->id,
                'variant_attributes' => $combination['attributes'] ?? null,
                'sku' => $combination['sku'],
                'barcode' => $combination['barcode'] ?: null,
                'is_primary' => !empty($combination['is_primary']),
                'cost_price' => $combination['cost_price'] ?: 0,
                'selling_price' => $combination['selling_price'],
                'compare_price' => $combination['compare_price'] ?: null,
                'stock_quantity' => $combination['stock_quantity'] ?: 0,
                'min_quantity_alert' => $combination['min_quantity_alert'] ?: 5,
                'max_quantity_per_order' => ($this->product_type === 'addon') ? 10000 : ($combination['max_quantity_per_order'] ?: 10),
                'track_inventory' => $combination['track_inventory'] ?? true,
                'allow_backorders' => $combination['allow_backorders'] ?? false,
                'weight_kg' => $combination['weight_kg'] ?: null,
                'length_cm' => $combination['length_cm'] ?: null,
                'width_cm' => $combination['width_cm'] ?: null,
                'height_cm' => $combination['height_cm'] ?: null,
                'status' => $status,
            ];

            $variant = null;
            // Prefer explicit id match when editing
            if ($this->editingProduct && !empty($combination['id']) && isset($existingById[$combination['id']])) {
                $variant = $existingById[$combination['id']];
                $variant->update($variantData);
                \Log::info('Updated existing variant (by id)', ['variant_id' => $variant->id]);
            } elseif ($this->editingProduct && !empty($variantData['variant_attributes'])) {
                // Try match by attribute signature to avoid duplicates when IDs are missing
                $sig = $buildSignature($variantData['variant_attributes']);
                if ($sig && isset($existingBySignature[$sig])) {
                    $variant = $existingBySignature[$sig];
                    $variant->update($variantData);
                    \Log::info('Updated existing variant (by signature)', ['variant_id' => $variant->id]);
                }
            }

            if (!$variant) {
                // Create new variant
                $variant = ProductVariant::create($variantData);
                \Log::info('Created new variant', ['variant_id' => $variant->id]);
            }

            $keepVariantIds[] = $variant->id;

            // Handle single image per option (upload to DigitalOcean Spaces via ImageService)
            try {
                $hasNewImage = isset($combination['image']) && $combination['image'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
                $hasExistingImage = $this->editingProduct && isset($combination['id']) && isset($existingOptionsImages[$combination['id']]);
                
                if ($hasNewImage) {
                    // Delete only existing options images for this variant (preserve general images)
                    $deleted = $variant->mediaAssets()
                        ->where('scope', 'option')
                        ->delete();
                    \Log::info('Deleted existing options images before upload', ['variant_id' => $variant->id, 'deleted_count' => $deleted]);
                    
                    // Upload new image
                    $dir = 'e-commerce/products/variant/' . $product->id . '/options';
                    $storedPath = \App\Services\ImageService::uploadImage($combination['image'], $dir);
                    if ($storedPath) {
                        try {
                            if (\Illuminate\Support\Facades\Storage::disk('do_spaces')->exists($storedPath)) {
                                \Illuminate\Support\Facades\Storage::disk('do_spaces')->setVisibility($storedPath, 'public');
                            }
                        } catch (\Exception $e) {}

                        $publicUrl = \App\Services\ImageService::getImageUrl($storedPath);
                        if (is_array($publicUrl)) { $publicUrl = null; }

                        MediaAsset::create([
                            'product_id' => null,
                            'variant_id' => $variant->id,
                            'scope' => 'option',
                            'type' => 'image',
                            'file_path' => $storedPath,
                            'file_url' => $publicUrl,
                            'alt_text' => $product->name . ' option',
                            'display_order' => 0,
                            'is_primary' => (bool)($variantData['is_primary'] ?? false), // Only set as primary if the variant is primary
                        ]);
                    }
                } elseif ($hasExistingImage) {
                    // Delete only existing options images for this variant (preserve general images)
                    $deleted = $variant->mediaAssets()
                        ->where('scope', 'option')
                        ->delete();
                    \Log::info('Deleted existing options images before restore', ['variant_id' => $variant->id, 'deleted_count' => $deleted]);
                    
                    // Restore existing image using the preserved media asset
                    $existingMedia = $existingOptionsImages[$combination['id']];
                    MediaAsset::create([
                        'product_id' => null,
                        'variant_id' => $variant->id,
                        'scope' => 'option',
                        'type' => $existingMedia->type,
                        'file_path' => $existingMedia->file_path,
                        'file_url' => $existingMedia->file_url,
                        'alt_text' => $existingMedia->alt_text,
                        'display_order' => $existingMedia->display_order,
                        'is_primary' => (bool)($variantData['is_primary'] ?? false), // Only set as primary if the variant is primary
                        'file_size' => $existingMedia->file_size,
                        'mime_type' => $existingMedia->mime_type,
                        'width' => $existingMedia->width,
                        'height' => $existingMedia->height,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Variant option image processing failed', ['row' => $rowIndex, 'error' => $e->getMessage()]);
            }
        }

        // Reconciliation: delete any existing variants not present in the submitted combinations
        if ($this->editingProduct) {
            if (!empty($keepVariantIds)) {
                $toDelete = $product->variants()->whereNotIn('id', $keepVariantIds)->get();
                $count = 0;
                foreach ($toDelete as $variant) {
                    try {
                        // Remove related records first
                        $variant->mediaAssets()->delete();
                        if (method_exists($variant, 'inventoryLogs')) {
                            $variant->inventoryLogs()->delete();
                        }
                        $variant->forceDelete(); // hard delete
                        $count++;
                    } catch (\Throwable $e) {
                        \Log::warning('Hard delete failed for variant', ['variant_id' => $variant->id, 'error' => $e->getMessage()]);
                    }
                }
                \Log::info('Reconciled variants after save (hard delete)', [
                    'kept' => $keepVariantIds,
                    'deleted_count' => $count,
                ]);
            } else {
                // If no combinations were provided, remove all variants (hard delete)
                $toDelete = $product->variants()->get();
                $count = 0;
                foreach ($toDelete as $variant) {
                    try {
                        $variant->mediaAssets()->delete();
                        if (method_exists($variant, 'inventoryLogs')) {
                            $variant->inventoryLogs()->delete();
                        }
                        $variant->forceDelete();
                        $count++;
                    } catch (\Throwable $e) {
                        \Log::warning('Hard delete failed for variant', ['variant_id' => $variant->id, 'error' => $e->getMessage()]);
                    }
                }
                \Log::info('Reconciled variants after save - removed all (hard delete)', [
                    'deleted_count' => $count,
                ]);
            }
        }
    }

    private function saveSingleVariant($product)
    {
        $variantData = [
            'product_id' => $product->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode ?: null,
            'is_primary' => true,
            'cost_price' => $this->cost_price ?: 0,
            'selling_price' => $this->selling_price,
            'compare_price' => $this->compare_price ?: null,
            'stock_quantity' => $this->stock_quantity,
            'min_quantity_alert' => $this->min_quantity_alert ?: 5,
            'max_quantity_per_order' => ($this->product_type === 'addon') ? 10000 : ($this->max_quantity_per_order ?: 10),
            'track_inventory' => $this->track_inventory,
            'allow_backorders' => $this->allow_backorders,
            'weight_kg' => $this->weight_kg ?: null,
            'length_cm' => $this->length_cm ?: null,
            'width_cm' => $this->width_cm ?: null,
            'height_cm' => $this->height_cm ?: null,
            'status' => $this->normalizeVariantStatus($this->status ?? null),
        ];

        // Filter to existing columns to avoid SQL errors across DBs
        $existing = Schema::getColumnListing('product_variants');
        $variantData = array_intersect_key($variantData, array_flip($existing));

        if ($this->editingProduct) {
            $variant = $product->variants()->first();
            \Log::info('saveSingleVariant debug', [
                'product_id' => $product->id,
                'existing_variant_id' => $variant ? $variant->id : null,
                'editing_product' => $this->editingProduct
            ]);
            
            if ($variant) {
                $variant->update($variantData);
                \Log::info('Updated existing variant', ['variant_id' => $variant->id]);
            } else {
                $newVariant = ProductVariant::create($variantData);
                \Log::info('Created new variant (no existing found)', ['variant_id' => $newVariant->id]);
            }
        } else {
            $newVariant = ProductVariant::create($variantData);
            \Log::info('Created new variant (new product)', ['variant_id' => $newVariant->id]);
        }
    }

    private function handleImageUploads($product)
    {
        $variant = $product->variants()->first();
        if (!$variant) return ['success' => false, 'message' => 'No variant found for product'];
        


        // Handle stock images (PRIMARY) for add-ons and single products
        if (($product->product_type === 'addon' || $product->product_type === 'regular') && !empty($this->stockImages)) {
            // Always limit to a single image
            if (is_array($this->stockImages) && count($this->stockImages) > 1) {
                $this->stockImages = [$this->stockImages[0]];
            }

            foreach ($this->stockImages as $index => $image) {
                if (!$image instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    continue; // skip invalid entries
                }
                // Determine subfolder by product type
                $typeFolder = $product->product_type === 'addon' ? 'add-ons' : 'single';
                $dir = 'e-commerce/products/' . $typeFolder . '/' . $product->id;

                // Upload via ImageService with built-in fallback (Spaces -> public)
                $storedPath = \App\Services\ImageService::uploadImage($image, $dir);
                if (!$storedPath) { continue; }

                // Best-effort: ensure public visibility if stored on Spaces
                try {
                    if (\Illuminate\Support\Facades\Storage::disk('do_spaces')->exists($storedPath)) {
                        \Illuminate\Support\Facades\Storage::disk('do_spaces')->setVisibility($storedPath, 'public');
                    }
                } catch (\Exception $e) {}

                // Get a resolvable public URL (falls back to local /storage when needed)
                $publicUrl = \App\Services\ImageService::getImageUrl($storedPath);
                if (is_array($publicUrl)) { // In case initials-array is returned unexpectedly
                    $publicUrl = null;
                }

                MediaAsset::create([
                    'product_id' => null,
                    'variant_id' => $variant->id,
                    'scope' => 'variant',
                    'type' => 'image',
                    'file_path' => $storedPath,
                    'file_url' => $publicUrl,
                    'alt_text' => $product->name,
                    'display_order' => 0, // PRIMARY image always first
                    'is_primary' => true,
                    'file_size' => $image->getSize(),
                    'mime_type' => $image->getMimeType(),
                ]);
                // For add-ons and single products, stop after first image
                break;
            }
        }
        
        // Handle general images (SECONDARY) for single products and variant products
        \Log::info('=== GENERAL IMAGES DEBUG START ===', [
            'product_type' => $product->product_type,
            'new_images_count' => count($this->images ?? []),
            'existing_images_count' => count($this->existingImages ?? []),
            'existing_images_data' => $this->existingImages ?? []
        ]);
        
        // Process general images if this is a regular/variant product AND we have either new images OR existing images
        if (($product->product_type === 'regular' || $product->product_type === 'variant') && 
            (!empty($this->images) || !empty($this->existingImages))) {
            
            \Log::info('Processing general images section', [
                'has_new_images' => !empty($this->images),
                'has_existing_images' => !empty($this->existingImages)
            ]);
            // DON'T delete existing images - they should persist unless user explicitly removed them
            // The existing images are preserved in $this->existingImages and will be maintained
            
            // Get the highest existing display_order to append new images properly
            if ($product->product_type === 'variant') {
                // For variant products, check general images at product level
                $maxDisplayOrder = $product->generalImages()->max('display_order') ?? 0;
            } else {
                // For regular products, check variant-level general images
                $maxDisplayOrder = $variant->mediaAssets()
                    ->where('scope', 'general')
                    ->where('is_primary', false)
                    ->max('display_order') ?? 0;
            }
                
            \Log::info('Processing new general images', [
                'max_display_order' => $maxDisplayOrder,
                'new_images_to_process' => count($this->images ?? [])
            ]);
            
            // Process NEW images if any
            if (!empty($this->images)) {
                foreach ($this->images as $index => $image) {
                if (!$image instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    continue; // skip invalid entries
                }
                // Determine subfolder by product type
                $typeFolder = $product->product_type === 'variant' ? 'variant' : 'single';
                $dir = 'e-commerce/products/' . $typeFolder . '/' . $product->id;

                // Upload via ImageService with built-in fallback (Spaces -> public)
                $storedPath = \App\Services\ImageService::uploadImage($image, $dir);
                if (!$storedPath) { continue; }

                // Best-effort: ensure public visibility if stored on Spaces
                try {
                    if (\Illuminate\Support\Facades\Storage::disk('do_spaces')->exists($storedPath)) {
                        \Illuminate\Support\Facades\Storage::disk('do_spaces')->setVisibility($storedPath, 'public');
                    }
                } catch (\Exception $e) {}

                // Get a resolvable public URL (falls back to local /storage when needed)
                $publicUrl = \App\Services\ImageService::getImageUrl($storedPath);
                if (is_array($publicUrl)) { // In case initials-array is returned unexpectedly
                    $publicUrl = null;
                }

                // For variant products: general images should NOT be primary
                // Primary images for variants come from the options table (variant-specific images)
                // For regular/addon products: general images are always secondary (is_primary = false)
                $isPrimary = false;

                // For variant products, store general images at PRODUCT level to avoid redundancy
                if ($product->product_type === 'variant') {
                    \Log::info('Creating general image at product level', [
                        'image_index' => $index,
                        'stored_path' => $storedPath,
                        'product_id' => $product->id
                    ]);
                    $created = MediaAsset::create([
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'scope' => 'general',
                        'type' => 'image',
                        'file_path' => $storedPath,
                        'file_url' => $publicUrl,
                        'alt_text' => $product->name,
                        'display_order' => $maxDisplayOrder + $index + 1,
                        'is_primary' => $isPrimary,
                        'file_size' => $image->getSize(),
                        'mime_type' => $image->getMimeType(),
                    ]);
                    \Log::info('Created general MediaAsset for variant product', [
                        'media_id' => $created->id,
                        'product_id' => $product->id,
                        'scope' => 'general'
                    ]);
                } else {
                    // For regular products, store on the single variant (legacy behavior)
                    $created = MediaAsset::create([
                        'product_id' => null,
                        'variant_id' => $variant->id,
                        'scope' => 'general',
                        'type' => 'image',
                        'file_path' => $storedPath,
                        'file_url' => $publicUrl,
                        'alt_text' => $product->name,
                        'display_order' => $maxDisplayOrder + $index + 1,
                        'is_primary' => $isPrimary,
                        'file_size' => $image->getSize(),
                        'mime_type' => $image->getMimeType(),
                    ]);
                    \Log::info('Created MediaAsset for regular product', [
                        'media_id' => $created->id,
                        'variant_id' => $variant->id,
                        'scope' => 'general'
                    ]);
                }
                }
            }
            
            \Log::info('=== GENERAL IMAGES DEBUG END ===', [
                'existing_images_preserved' => !empty($this->existingImages),
                'new_images_added' => !empty($this->images)
            ]);
        }
        
        return ['success' => true, 'message' => 'Images uploaded successfully'];
    }



    public function closeSuccessModal()
    {
        $this->showSuccessModal = false;
        $this->showForm = false;
        $this->showList = true;
        $this->resetForm();
    }
    
    private function updateExistingImagesAfterSave($product)
    {
        // Reload the product with fresh media assets to get the newly uploaded images
        $product->load(['variants.mediaAssets', 'generalImages']);
        
        // Rebuild the existing images arrays to include all current images
        $this->existingImages = [];
        $this->existingStockImages = [];
        
        \Log::info('updateExistingImagesAfterSave debug', [
            'product_id' => $product->id,
            'product_type' => $this->product_type
        ]);
        
        // Load general images for variant products (stored at product level)
        if ($this->product_type === 'variant') {
            $generalMedia = $product->generalImages;
            foreach ($generalMedia as $media) {
                $publicUrl = \App\Services\ImageService::getImageUrl($media->file_path ?: $media->file_url);
                $imageData = ['id' => $media->id, 'url' => $publicUrl];
                $this->existingImages[] = $imageData;
            }
        }
        
        // Load variant-specific images for regular/addon products
        $variant = $product->variants()->first();
        if ($variant && ($this->product_type === 'regular' || $this->product_type === 'addon')) {
            $variantMedia = $variant->mediaAssets()->where('scope', '!=', 'option')->orderBy('display_order')->get();
            
            foreach ($variantMedia as $media) {
                $publicUrl = \App\Services\ImageService::getImageUrl($media->file_path ?: $media->file_url);
                $imageData = ['id' => $media->id, 'url' => $publicUrl];
                
                // Categorize based on product type and image properties
                if ($this->product_type === 'regular') {
                    // For regular products: primary images go to stock section, secondary to general
                    if ($media->is_primary) {
                        $this->existingStockImages[] = $imageData;
                    } else {
                        $this->existingImages[] = $imageData;
                    }
                } elseif ($this->product_type === 'addon') {
                    // For addons: only primary images (stock section)
                    if ($media->is_primary) {
                        $this->existingStockImages[] = $imageData;
                    }
                }
            }
        }
        
        \Log::info('Final image arrays', [
            'existingImages_count' => count($this->existingImages),
            'existingStockImages_count' => count($this->existingStockImages)
        ]);
        
        // Update variant combinations with fresh option images for variant products
        if ($this->product_type === 'variant' && !empty($this->variantCombinations)) {
            foreach ($this->variantCombinations as $index => &$combination) {
                if (isset($combination['id'])) {
                    $variant = $product->variants()->find($combination['id']);
                    if ($variant) {
                        $optionsImage = $variant->mediaAssets()
                            ->where('scope', 'option')
                            ->first();
                        
                        if ($optionsImage) {
                            $combination['existing_image_id'] = $optionsImage->id;
                            $combination['existing_image'] = \App\Services\ImageService::getImageUrl($optionsImage->file_path ?: $optionsImage->file_url);
                        }
                    }
                }
            }
        }
    }

    public function createProduct()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->showList = false;
    }



    public function closeForm()
    {
        $this->showForm = false;
        $this->showList = true;
        $this->resetForm();
    }

    private function loadProductData($productId)
    {
        $product = Product::with(['variants', 'tags', 'addons', 'categories'])->findOrFail($productId);
        
        $this->name = $product->name;
        $this->slug = $product->slug;
        // Species removed from UI; keep for backward compatibility
        $this->species_id = null;

        $this->selectedTags = $product->tags->pluck('id')->toArray();
        $this->selectedCategories = $product->categories->sortBy('pivot.display_order')->pluck('id')->toArray();
        $this->short_description = $product->short_description;
        $this->description = $product->description;
        $this->product_type = $product->product_type;
        $this->featured = $product->featured;
        $this->shippable = $product->shippable;
        $this->status = $product->status;
        $this->meta_title = $product->meta_title;
        $this->meta_description = $product->meta_description;
        $this->meta_keywords = $product->meta_keywords;
        $this->focus_keywords = $product->focus_keywords;
        
        // Load variant data
        $primaryVariant = $product->variants()->where('is_primary', true)->first();
        if ($primaryVariant) {
            $this->sku = $primaryVariant->sku;
            $this->barcode = $primaryVariant->barcode;
            $this->cost_price = $primaryVariant->cost_price;
            $this->selling_price = $primaryVariant->selling_price;
            $this->compare_price = $primaryVariant->compare_price;
            $this->stock_quantity = $primaryVariant->stock_quantity;
            $this->min_quantity_alert = $primaryVariant->min_quantity_alert;
            $this->max_quantity_per_order = $primaryVariant->max_quantity_per_order;
            $this->track_inventory = $primaryVariant->track_inventory;
            $this->allow_backorders = $primaryVariant->allow_backorders;
            $this->weight_kg = $primaryVariant->weight_kg;
            $this->length_cm = $primaryVariant->length_cm;
            $this->width_cm = $primaryVariant->width_cm;
            $this->height_cm = $primaryVariant->height_cm;
        }
        
        // Load addons
        $this->selectedAddons = $product->addons->pluck('id')->toArray();
        // Load required flags from pivot
        $this->addonRequired = [];
        foreach ($product->addons as $a) {
            $this->addonRequired[$a->id] = (bool)($a->pivot->is_required ?? false);
        }
        \Log::info('Loaded addonRequired map', [
            'product_id' => $product->id,
            'selected_addons' => $this->selectedAddons,
            'addon_required' => $this->addonRequired,
        ]);
        
        // Load existing images based on product type and section
        $variant = $product->variants()->with('mediaAssets')->first();
        
        if ($variant) {
            // Separate images by section using file path patterns and is_primary flag
            $stockImages = [];
            $generalImages = [];
            $optionsImages = [];

            foreach ($variant->mediaAssets()->orderBy('display_order')->get() as $media) {
                $path = $media->file_path ?: $media->file_url;
                $publicUrl = \App\Services\ImageService::getImageUrl($media->file_path ?: $media->file_url);
                
                // Create image data with ID for precise deletion
                $imageData = ['id' => $media->id, 'url' => $publicUrl];
                
                if (is_string($path) && stripos($path, '/options') !== false) {
                    // Options table images - skip for now, handled separately in variant combinations
                    continue;
                } elseif ($media->is_primary) {
                    // Stock & Pricing images (primary, non-options)
                    $stockImages[] = $imageData;
                } else {
                    // General Info images (secondary, non-options)
                    $generalImages[] = $imageData;
                }
            }

            // Assign images based on product type
            if ($product->product_type === 'addon') {
                // Add-ons: Only stock images (primary)
                $this->existingStockImages = $stockImages;
                $this->existingImages = [];
            } elseif ($product->product_type === 'regular') {
                // Single products: Stock (primary) and general (secondary) images
                $this->existingStockImages = $stockImages;
                $this->existingImages = $generalImages;
            } else {
                // Variant products: General images only (no stock images in this section)
                $this->existingImages = $generalImages;
                $this->existingStockImages = [];
            }
        } else {
            $this->existingImages = [];
            $this->existingStockImages = [];
        }
    }

    public function cancelForm()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->showList = true;
    }

    public function resetForm()
    {
        $this->editingProduct = null;
        $this->product_type = 'regular';
        $this->name = '';
        $this->slug = '';
        $this->species_id = '';
        $this->selectedCategories = [];
        $this->selectedTags = [];
        $this->short_description = '';
        $this->description = '';
        $this->images = [];
        $this->existingImages = [];
        $this->stockImages = [];
        $this->existingStockImages = [];
        $this->images_buffer = [];
        
        $this->resetPricingData();
        $this->resetVariantData();
        
        $this->selectedAddons = [];
        $this->addonRequired = [];
        $this->meta_title = '';
        $this->meta_description = '';
        $this->meta_keywords = '';
        $this->focus_keywords = '';
        $this->status = 'draft';
        $this->featured = false;
        $this->shippable = false;
        
        $this->resetValidation();
    }

    private function resetPricingData()
    {
        $this->sku = '';
        $this->barcode = '';
        $this->cost_price = '';
        $this->selling_price = '';
        $this->compare_price = '';
        $this->stock_quantity = '';
        $this->min_quantity_alert = '';
        $this->max_quantity_per_order = ($this->product_type === 'addon') ? 10000 : '';
        $this->track_inventory = true;
        $this->allow_backorders = false;
        $this->weight_kg = '';
        $this->length_cm = '';
        $this->width_cm = '';
        $this->height_cm = '';
        $this->skuManuallyEdited = false;
        
        // For add-ons, immediately suggest a SKU so the admin can edit
        if ($this->product_type === 'addon') {
            $this->setSkuAuto($this->generateAddonSku());
        }
    }

    private function resetVariantData()
    {
        $this->selectedVariantTypes = [];
        $this->variantOptions = [];
        $this->variantCombinations = [];
    }

    // ==================== DELETION METHODS ====================
    
    public function initiateDelete($productId)
    {
        $this->productToDelete = Product::with(['variants', 'categories', 'tags'])->find($productId);
        $this->deleteType = 'product';
        $this->selectedVariantToDelete = null;
        $this->resetDeletionState();
        $this->showDeleteModal = true;
        $this->loadDeletionDependencies();
    }
    
    public function initiateVariantDelete($productId, $variantId = null)
    {
        $this->productToDelete = Product::with(['variants', 'categories', 'tags'])->find($productId);
        $this->deleteType = 'variant';
        $this->selectedVariantToDelete = $variantId;
        $this->resetDeletionState();
        $this->showDeleteModal = true;
        $this->loadDeletionDependencies();
    }
    
    public function setDeleteType($type)
    {
        $this->deleteType = $type;
        $this->selectedVariantToDelete = null;
        $this->loadDeletionDependencies();
    }
    
    public function setVariantToDelete($variantId)
    {
        $this->selectedVariantToDelete = $variantId;
        $this->loadDeletionDependencies();
    }
    
    private function resetDeletionState()
    {
        $this->deletionDependencies = [];
        $this->canDelete = true;
        $this->deleteWarnings = [];
        $this->isLoadingDependencies = false;
    }
    
    public function loadDeletionDependencies()
    {
        $this->isLoadingDependencies = true;
        $this->resetDeletionState();
        $this->isLoadingDependencies = true;
        
        if (!$this->productToDelete) {
            $this->isLoadingDependencies = false;
            return;
        }
        
        try {
            $dependencies = [];
            $warnings = [];
            $canDelete = true;
            
            if ($this->productToDelete->product_type === 'regular') {
                $result = $this->checkRegularProductDependencies();
            } elseif ($this->productToDelete->product_type === 'variant') {
                $result = $this->checkVariantProductDependencies();
            } elseif ($this->productToDelete->product_type === 'addon') {
                $result = $this->checkAddonProductDependencies();
            }
            
            $this->deletionDependencies = $result['dependencies'] ?? [];
            $this->deleteWarnings = $result['warnings'] ?? [];
            $this->canDelete = $result['canDelete'] ?? true;
            
        } catch (\Exception $e) {
            Log::error('Error loading deletion dependencies', [
                'product_id' => $this->productToDelete->id,
                'error' => $e->getMessage()
            ]);
            $this->canDelete = false;
            $this->deleteWarnings = ['An error occurred while checking dependencies. Please try again.'];
        }
        
        $this->isLoadingDependencies = false;
    }
    
    private function checkRegularProductDependencies()
    {
        $product = $this->productToDelete;
        $dependencies = [];
        $warnings = [];
        $canDelete = true;
        
        // Check active orders
        $activeOrders = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $product->id)
            ->whereIn('orders.status', ['pending', 'processing', 'shipped'])
            ->count();
            
        if ($activeOrders > 0) {
            $dependencies['active_orders'] = $activeOrders;
            $canDelete = false;
        }
        
        // Check active carts
        $activeCarts = DB::table('cart_items')
            ->where('product_id', $product->id)
            ->count();
            
        if ($activeCarts > 0) {
            $dependencies['active_carts'] = $activeCarts;
        }
        
        // Check attached addons
        $attachedAddons = DB::table('product_addons')
            ->join('products', 'product_addons.addon_id', '=', 'products.id')
            ->where('product_addons.product_id', $product->id)
            ->where('products.deleted_at', null)
            ->select('products.name', 'product_addons.is_required')
            ->get();
            
        if ($attachedAddons->count() > 0) {
            $dependencies['attached_addons'] = $attachedAddons->toArray();
            $requiredAddons = $attachedAddons->where('is_required', true);
            if ($requiredAddons->count() > 0) {
                $warnings[] = "This product has {$requiredAddons->count()} required add-ons attached.";
            }
        }
        
        return [
            'dependencies' => $dependencies,
            'warnings' => $warnings,
            'canDelete' => $canDelete
        ];
    }
    
    private function checkVariantProductDependencies()
    {
        $product = $this->productToDelete;
        $dependencies = [];
        $warnings = [];
        $canDelete = true;
        
        if ($this->deleteType === 'variant' && $this->selectedVariantToDelete) {
            // Check single variant deletion
            $variant = $product->variants->find($this->selectedVariantToDelete);
            if (!$variant) {
                return [
                    'dependencies' => [],
                    'warnings' => ['Selected variant not found.'],
                    'canDelete' => false
                ];
            }
            
            // Check if this is the last variant
            if ($product->variants->count() === 1) {
                $warnings[] = 'This is the last variant. Deleting it will delete the entire product.';
            }
            
            // Check if this is the primary variant
            $primaryVariant = $product->variants->where('is_primary', true)->first();
            if ($primaryVariant && $primaryVariant->id === $variant->id && $product->variants->count() > 1) {
                $warnings[] = 'This is the primary variant. Another variant will be automatically set as primary.';
            }
            
            // Check active orders for this variant
            $activeOrders = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.variant_id', $variant->id)
                ->whereIn('orders.status', ['pending', 'processing', 'shipped'])
                ->count();
                
            if ($activeOrders > 0) {
                $dependencies['active_orders'] = $activeOrders;
                $canDelete = false;
            }
            
            // Check active carts for this variant
            $activeCarts = DB::table('cart_items')
                ->where('variant_id', $variant->id)
                ->count();
                
            if ($activeCarts > 0) {
                $dependencies['active_carts'] = $activeCarts;
            }
            
        } else {
            // Check entire product deletion
            $variantIds = $product->variants->pluck('id');
            
            // Check active orders for all variants
            $activeOrders = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereIn('order_items.variant_id', $variantIds)
                ->whereIn('orders.status', ['pending', 'processing', 'shipped'])
                ->count();
                
            if ($activeOrders > 0) {
                $dependencies['active_orders'] = $activeOrders;
                $canDelete = false;
            }
            
            // Check active carts for all variants
            $activeCarts = DB::table('cart_items')
                ->whereIn('variant_id', $variantIds)
                ->count();
                
            if ($activeCarts > 0) {
                $dependencies['active_carts'] = $activeCarts;
            }
            
            // Check attached addons
            $attachedAddons = DB::table('product_addons')
                ->join('products', 'product_addons.addon_id', '=', 'products.id')
                ->where('product_addons.product_id', $product->id)
                ->where('products.deleted_at', null)
                ->select('products.name', 'product_addons.is_required')
                ->get();
                
            if ($attachedAddons->count() > 0) {
                $dependencies['attached_addons'] = $attachedAddons->toArray();
                $requiredAddons = $attachedAddons->where('is_required', true);
                if ($requiredAddons->count() > 0) {
                    $warnings[] = "This product has {$requiredAddons->count()} required add-ons attached.";
                }
            }
        }
        
        return [
            'dependencies' => $dependencies,
            'warnings' => $warnings,
            'canDelete' => $canDelete
        ];
    }
    
    private function checkAddonProductDependencies()
    {
        $product = $this->productToDelete;
        $dependencies = [];
        $warnings = [];
        $canDelete = true;
        
        // Check active orders for this addon
        $activeOrders = DB::table('order_addons')
            ->join('order_items', 'order_addons.order_item_id', '=', 'order_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_addons.addon_product_id', $product->id)
            ->whereIn('orders.status', ['pending', 'processing', 'shipped'])
            ->count();
            
        if ($activeOrders > 0) {
            $dependencies['active_orders'] = $activeOrders;
            $canDelete = false;
        }
        
        // Check active carts for this addon
        $activeCarts = DB::table('cart_addons')
            ->where('addon_product_id', $product->id)
            ->count();
            
        if ($activeCarts > 0) {
            $dependencies['active_carts'] = $activeCarts;
        }
        
        // Check if this addon is attached to other products
        $attachedToProducts = DB::table('product_addons')
            ->join('products', 'product_addons.product_id', '=', 'products.id')
            ->where('product_addons.addon_id', $product->id)
            ->where('products.deleted_at', null)
            ->select('products.name', 'product_addons.is_required')
            ->get();
            
        if ($attachedToProducts->count() > 0) {
            $dependencies['attached_to_products'] = $attachedToProducts->toArray();
            
            // Check if it's required for any active products
            $requiredForProducts = $attachedToProducts->where('is_required', true);
            if ($requiredForProducts->count() > 0) {
                $canDelete = false;
                $warnings[] = "This add-on is required for {$requiredForProducts->count()} active products and cannot be deleted.";
            }
        }
        
        return [
            'dependencies' => $dependencies,
            'warnings' => $warnings,
            'canDelete' => $canDelete
        ];
    }
    
    public function confirmDelete()
    {
        if (!$this->canDelete || !$this->productToDelete) {
            return;
        }
        
        try {
            DB::beginTransaction();
            
            if ($this->productToDelete->product_type === 'regular') {
                $this->deleteRegularProduct();
            } elseif ($this->productToDelete->product_type === 'variant') {
                if ($this->deleteType === 'variant' && $this->selectedVariantToDelete) {
                    $this->deleteSingleVariant();
                } else {
                    $this->deleteVariantProduct();
                }
            } elseif ($this->productToDelete->product_type === 'addon') {
                $this->deleteAddonProduct();
            }
            
            DB::commit();
            
            $this->showDeleteModal = false;
            $this->successMessage = 'Product deleted successfully!';
            $this->showSuccessModal = true;
            $this->resetDeletionState();
            
            // Refresh pagination so the next render re-queries the dataset
            $this->resetPage();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product deletion failed', [
                'product_id' => $this->productToDelete->id,
                'error' => $e->getMessage()
            ]);
            
            session()->flash('error', 'Failed to delete product. Please try again.');
        }
    }
    
    private function deleteRegularProduct()
    {
        $product = $this->productToDelete;
        
        // Soft delete related records
        $product->categories()->detach();
        $product->tags()->detach();
        
        // Soft delete product addons
        DB::table('product_addons')->where('product_id', $product->id)->delete();
        
        // Soft delete media assets
        $product->variants->each(function($variant) {
            $variant->mediaAssets()->delete();
        });
        
        // Soft delete variants
        $product->variants()->delete();
        
        // Soft delete the product
        $product->delete();
    }
    
    private function deleteSingleVariant()
    {
        $product = $this->productToDelete;
        $variant = $product->variants->find($this->selectedVariantToDelete);
        
        if (!$variant) {
            throw new \Exception('Variant not found');
        }
        
        // If this is the last variant, delete the entire product
        if ($product->variants->count() === 1) {
            $this->deleteVariantProduct();
            return;
        }
        
        // If this is the primary variant, set another as primary
        if ($variant->is_primary) {
            $newPrimary = $product->variants->where('id', '!=', $variant->id)->first();
            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }
        
        // Soft delete media assets
        $variant->mediaAssets()->delete();
        
        // Soft delete the variant
        $variant->delete();
    }
    
    private function deleteVariantProduct()
    {
        $product = $this->productToDelete;
        
        // Soft delete related records
        $product->categories()->detach();
        $product->tags()->detach();
        
        // Soft delete product addons
        DB::table('product_addons')->where('product_id', $product->id)->delete();
        
        // Soft delete media assets for all variants
        $product->variants->each(function($variant) {
            $variant->mediaAssets()->delete();
        });
        
        // Soft delete all variants
        $product->variants()->delete();
        
        // Soft delete the product
        $product->delete();
    }
    
    private function deleteAddonProduct()
    {
        $product = $this->productToDelete;
        
        // Soft delete related records
        $product->categories()->detach();
        $product->tags()->detach();
        
        // Soft delete product addon relationships
        DB::table('product_addons')->where('addon_id', $product->id)->delete();
        
        // Soft delete media assets
        $product->variants->each(function($variant) {
            $variant->mediaAssets()->delete();
        });
        
        // Soft delete variants
        $product->variants()->delete();
        
        // Soft delete the product
        $product->delete();
    }
    
    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->resetDeletionState();
        $this->productToDelete = null;
    }

    public function render()
    {
        $productsQuery = Product::with(['categories', 'variants.mediaAssets']);

        if ($this->listType === 'products') {
            $productsQuery->whereIn('product_type', ['regular', 'variant']);
        } elseif ($this->listType === 'addons') {
            $productsQuery->where('product_type', 'addon');
        } // 'all' shows all types

        $products = $productsQuery
            ->when($this->search, function ($query) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhereHas('categories', function ($categoryQuery) use ($search) {
                          $categoryQuery->where('name', 'like', '%' . $search . '%');
                      });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        // Load dropdown data for the form
        $species = collect(); // species removed
        $categories = Category::orderBy('name')->get();
        $tags = ProductTag::orderBy('name')->get();
        $variantTypes = VariantAttributeType::with('values')->orderBy('name')->get();
        $addons = Product::where('product_type', 'addon')->orderBy('name')->get();

        return view('livewire.backend.product-management', [
            'products' => $products,
            // 'species' => $species, // removed
            'categories' => $categories,
            'tags' => $tags,
            'variantTypes' => $variantTypes,
            'addons' => $addons,
        ])->layout('layouts.backend.index');
    }


}