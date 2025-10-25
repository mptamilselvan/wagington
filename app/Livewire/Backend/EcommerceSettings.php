<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Category;
use App\Models\ProductTag;
use App\Models\VariantAttributeType;
use App\Models\VariantAttributeValue;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class EcommerceSettings extends Component
{
    use WithFileUploads, WithPagination;

    // Form properties
    public $name = '';
    public $slug = '';
    public $meta_title = '';
    public $meta_keywords = '';
    public $meta_description = '';
    public $focus_keywords = '';
    public $images = [];
    public $existingImages = [];
    public $parent_id = null; // null for root category
    public $parentCategories = []; // options for dropdown

    // UI state
    public $activeTab = 'category-configuration';
    public $showForm = false;
    public $editingCategory = null;
    public $search = '';

    // Variant Attribute properties
    public $variantTitle = '';
    public $variantOptions = [];
    public $currentOption = '';
    public $variantImage = null;
    public $existingVariantImages = [];
    public $editingVariantType = null;
    public $enableColorPicker = false; // when true, show color pickers and save color_hex
    public $variantOptionColors = []; // per-option color hex values aligned by index

    // Tag Management properties
    public $tagName = '';
    public $tagColor = '#007bff';
    public $tagDescription = '';
    public $editingTag = null;

    // Validation rules
    protected function rules()
    {
        $imageRequired = (empty($this->editingCategory) && empty($this->existingImages)) || (!empty($this->editingCategory) && empty($this->existingImages));
        return [
            'name' => 'required|string|max:100',
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', // Only lowercase letters, numbers, and hyphens
                'unique:categories,slug' . ($this->editingCategory ? ',' . $this->editingCategory : '')
            ],
            'meta_title' => 'required|string|max:60',
            'meta_keywords' => 'required|string|max:255',
            'meta_description' => 'required|string|max:160',
            'focus_keywords' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            // Enforce at least one image overall (either existing or newly uploaded)
            'images' => ($imageRequired ? 'required|array|min:1' : 'nullable|array'),
            'images.*' => 'image|max:2048', // 2MB max per image
        ];
    }

    protected $messages = [
        'name.required' => 'Category name is required.',
        'name.max' => 'Category name cannot exceed 100 characters.',
        'slug.unique' => 'This slug is already taken. Please choose a different one.',
        'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
        'slug.max' => 'Slug cannot exceed 100 characters.',
        'meta_title.required' => 'Meta title is required.',
        'meta_title.max' => 'Meta title cannot exceed 60 characters.',
        'meta_keywords.required' => 'Meta keywords are required.',
        'meta_description.required' => 'Meta description is required.',
        
        'images.*.required' => 'An image is required.',
        'images.*.image' => 'Each file must be an image.',
        'images.*.max' => 'Each image cannot exceed 2MB.',
    ];

    public function mount()
    {
        // Preload only main/parent categories for dropdown (categories with parent_id = null)
        $this->parentCategories = Category::whereNull('parent_id')->orderBy('name')->get(['id','name']);
    }

    public function updatedName()
    {
        // Always auto-generate slug when name changes
        // This will work for both new entries and editing existing ones
        $this->slug = $this->generateUniqueSlug($this->name);
        $this->dispatch('slug-updated');
    }

    public function updatedImages()
    {
        // Normalize to a single image and clear existing preview when a new image is selected
        if (is_array($this->images)) {
            if (count($this->images) > 1) {
                $this->images = [reset($this->images)];
            } elseif (count($this->images) === 1) {
                $this->images = [reset($this->images)];
            }
        } elseif ($this->images) {
            $this->images = [$this->images];
        } else {
            $this->images = [];
        }

        if (!empty($this->images)) {
            $this->existingImages = [];
        }
    }

    /**
     * Generate a unique slug following best practices:
     * 1. Convert to lowercase
     * 2. Replace spaces with hyphens (-)
     * 3. Remove special characters (&, !, ', etc.)
     * 4. Ensure uniqueness (add suffix if needed)
     */
    private function generateUniqueSlug($name)
    {
        if (empty($name)) {
            return '';
        }

        // Step 1-3: Laravel's Str::slug handles lowercase, spaces->hyphens, special chars removal
        $baseSlug = Str::slug($name);
        
        // Step 4: Ensure uniqueness
        $slug = $baseSlug;
        $counter = 1;
        
        // Check if slug exists (excluding current category if editing)
        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Check if slug already exists in database
     */
    private function slugExists($slug)
    {
        $query = Category::where('slug', $slug);
        
        // If editing, exclude current category from check
        if ($this->editingCategory) {
            $query->where('id', '!=', $this->editingCategory);
        }
        
        return $query->exists();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function showAddForm()
    {
        $this->resetFormPrivate();
        $this->showForm = true;
    }

    public function editCategory($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        
        $this->editingCategory = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->meta_title = $category->meta_title;
        $this->meta_keywords = $category->meta_keywords;
        $this->meta_description = $category->meta_description;
        $this->focus_keywords = $category->focus_keywords;
        // Normalize to string for select binding to avoid type mismatch
        $this->parent_id = $category->parent_id ? (string) $category->parent_id : '';
        
        // Handle existing images
        if ($category->image_url) {
            // The model's accessor now handles URL generation
            $this->existingImages = [$category->image_url];
        }
        
        // Refresh dropdown (only main/parent categories, exclude current category to avoid selecting itself)
        $this->parentCategories = Category::whereNull('parent_id')->where('id','!=',$category->id)->orderBy('name')->get(['id','name']);
        
        $this->showForm = true;
    }

    public function saveCategory()
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'slug' => $this->slug ?: Str::slug($this->name),
                'meta_title' => $this->meta_title,
                'meta_keywords' => $this->meta_keywords,
                'meta_description' => $this->meta_description,
                'focus_keywords' => $this->focus_keywords,
                'parent_id' => $this->parent_id !== '' ? $this->parent_id : null,
            ];



            // Handle image upload; if no new image uploaded and existing image present, keep it
            $newImageUploaded = !empty($this->images);
            if ($newImageUploaded) {
                $disk = \App\Services\ImageService::getPreferredDisk();
                $dir = 'e-commerce/settings/category-configuration';
                $imagePath = $this->images[0]->store($dir, $disk);
                if ($disk === 'do_spaces') {
                    try { Storage::disk($disk)->setVisibility($imagePath, 'public'); } catch (\Exception $e) {}
                }
                // Store full URL like ProductManagement does
                $publicUrl = \App\Services\ImageService::getImageUrl($imagePath);
                if (is_array($publicUrl)) { // In case initials-array is returned unexpectedly
                    $publicUrl = null;
                }
                $data['image_url'] = $publicUrl;
            }

            if ($this->editingCategory) {
                $category = Category::findOrFail($this->editingCategory);
                // If replacing image, delete old from storage
                $oldImageUrl = $category->getRawOriginal('image_url');

                // If no new image uploaded and we have an existing image, ensure it's kept
                if (!$newImageUploaded && $oldImageUrl && empty($data['image_url'])) {
                    $data['image_url'] = $oldImageUrl;
                }

                if (!empty($data['image_url']) && $oldImageUrl && $oldImageUrl !== $data['image_url']) {
                    // Extract path from full URL for deletion (ImageService handles this)
                    try { \App\Services\ImageService::deleteImage($oldImageUrl); } catch (\Exception $e) {}
                }
                $category->update($data);
                session()->flash('message', 'Category updated successfully!');
            } else {
                Category::create($data);
                session()->flash('message', 'Category created successfully!');
            }

            $this->resetFormPrivate();
            $this->showForm = false;

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while saving the category. Please try again.');
        }
    }

    public function deleteCategory($categoryId)
    {
        try {
            $category = Category::findOrFail($categoryId);
            
            // Check if category has children
            if ($category->hasChildren()) {
                session()->flash('error', 'Cannot delete category that has subcategories.');
                return;
            }
            
            $category->delete();
            session()->flash('message', 'Category deleted successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while deleting the category.');
        }
    }

    public function toggleStatus($categoryId)
    {
        try {
            $category = Category::findOrFail($categoryId);
            $category->update([
                'status' => $category->status === 'active' ? 'inactive' : 'active'
            ]);
            
            session()->flash('message', 'Category status updated successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while updating the category status.');
        }
    }

    public function cancelForm()
    {
        $this->resetFormPrivate();
        $this->showForm = false;
    }

    public function removeImage($index)
    {
        if (isset($this->images[$index])) {
            unset($this->images[$index]);
            $this->images = array_values($this->images); // Re-index array
        }
    }

    public function removeExistingImage($index)
    {
        if (isset($this->existingImages[$index])) {
            unset($this->existingImages[$index]);
            $this->existingImages = array_values($this->existingImages);
        }
    }

    public function removeExistingVariantImage($index)
    {
        if (isset($this->existingVariantImages[$index])) {
            unset($this->existingVariantImages[$index]);
            $this->existingVariantImages = array_values($this->existingVariantImages);
        }
    }

    public function resetForm()
    {
        $this->name = '';
        $this->slug = '';
        $this->meta_title = '';
        $this->meta_keywords = '';
        $this->meta_description = '';
        $this->focus_keywords = '';
        $this->images = [];
        $this->existingImages = [];
        // Keep select empty string for consistent binding with <option value=""> -- None --
        $this->parent_id = '';
        $this->editingCategory = null;
        $this->parentCategories = Category::whereNull('parent_id')->orderBy('name')->get(['id','name']);
        $this->resetValidation();
    }

    private function resetFormPrivate()
    {
        $this->resetForm();
    }

    // Variant Attribute Methods
    public function addVariantOption()
    {
        if (!empty(trim($this->currentOption))) {
            $option = trim($this->currentOption);
            if (!in_array($option, $this->variantOptions)) {
                $this->variantOptions[] = $option;
                $this->variantOptionColors[] = '';
                $this->currentOption = '';
            }
        }
    }

    public function removeVariantOption($index)
    {
        unset($this->variantOptions[$index]);
        $this->variantOptions = array_values($this->variantOptions);
        // keep colors array aligned
        if (isset($this->variantOptionColors[$index])) {
            unset($this->variantOptionColors[$index]);
            $this->variantOptionColors = array_values($this->variantOptionColors);
        }
    }

    public function editVariantAttribute($id)
    {
        $variantType = VariantAttributeType::with('values')->findOrFail($id);

        $this->editingVariantType = $id;
        $this->variantTitle = $variantType->name;
        $this->variantOptions = $variantType->values->pluck('value')->toArray();
        $this->currentOption = '';

        // Enable color picker automatically if title suggests color or any value has color_hex
        $hasAnyHex = $variantType->values->contains(function ($v) { return !empty($v->color_hex); });
        $this->enableColorPicker = $hasAnyHex || (strtolower($variantType->name) === 'color');
        $this->variantOptionColors = [];
        foreach ($variantType->values as $idx => $val) {
            $this->variantOptionColors[$idx] = $val->color_hex ?: '';
        }

        // Load existing image for editing (following category pattern)
        $this->variantImage = null; // Reset current image
        $this->existingVariantImages = [];
        if ($variantType->image_url) {
            // The model's accessor now handles URL generation
            $this->existingVariantImages = [$variantType->image_url];
        }
    }

    public function deleteVariantAttribute($id)
    {
        $variantType = VariantAttributeType::findOrFail($id);
        $variantType->delete(); // This will cascade delete the values
        
        session()->flash('message', 'Variant attribute deleted successfully!');
    }

    public function saveVariantAttribute()
    {
        // 1) Validate first so Livewire shows field-level errors next to inputs
        // Build rules and validate
        $rules = [
            'variantTitle' => [
                'required',
                'string',
                'max:100',
                Rule::unique('variant_attribute_types', 'name')->ignore($this->editingVariantType)
            ],
            'variantOptions' => 'required|array|min:1',
            'variantImage' => 'nullable|image|max:2048',
            'enableColorPicker' => 'boolean',
        ];
        $this->validate($rules, [
            'variantTitle.unique' => 'This variant title has already been taken.',
        ]);

        try {
            // 2) Handle image upload following the same pattern as category configuration
            $imageUrl = null;
            if ($this->variantImage) {
                $disk = \App\Services\ImageService::getPreferredDisk();
                $dir = 'e-commerce/settings/variant-configuration';
                $imagePath = $this->variantImage->store($dir, $disk);
                if ($disk === 'do_spaces') {
                    try { Storage::disk($disk)->setVisibility($imagePath, 'public'); } catch (\Exception $e) {}
                }
                // Store full URL like ProductManagement does
                $publicUrl = \App\Services\ImageService::getImageUrl($imagePath);
                if (is_array($publicUrl)) { // In case initials-array is returned unexpectedly
                    $publicUrl = null;
                }
                $imageUrl = $publicUrl;
            } elseif (empty($this->existingVariantImages) && $this->editingVariantType) {
                // If no new image uploaded and existing images were removed, clear the image_url
                $imageUrl = null;
            } elseif ($this->editingVariantType) {
                // If editing and no new image, keep existing image_url
                $existingVariantType = VariantAttributeType::find($this->editingVariantType);
                $imageUrl = $existingVariantType ? $existingVariantType->image_url : null;
            }

            // 3) Create or update variant attribute type
            if ($this->editingVariantType) {
                $variantType = VariantAttributeType::findOrFail($this->editingVariantType);
                // If replacing image, or removing it, delete old from storage
                $oldImageUrl = $variantType->getRawOriginal('image_url');
                if ($oldImageUrl && $oldImageUrl !== $imageUrl) {
                    // ImageService handles URL extraction for deletion
                    try { \App\Services\ImageService::deleteImage($oldImageUrl); } catch (\Exception $e) {}
                }
                $variantType->update([
                    'name' => $this->variantTitle,
                    'slug' => Str::slug($this->variantTitle),
                    'image_url' => $imageUrl,
                ]);
            } else {
                $variantType = VariantAttributeType::create([
                    'name' => $this->variantTitle,
                    'slug' => Str::slug($this->variantTitle),
                    'type' => 'text',
                    'image_url' => $imageUrl,
                ]);
            }

            // 4) Delete existing values if editing
            if ($this->editingVariantType) {
                $variantType->values()->delete();
            }

            // 5) Create variant attribute values
            foreach ($this->variantOptions as $index => $option) {
                $option = trim((string)$option);
                if ($option === '') continue;

                $hex = null;
                if ($this->enableColorPicker) {
                    $raw = $this->variantOptionColors[$index] ?? '';
                    $raw = is_string($raw) ? trim($raw) : '';
                    if ($raw !== '' && preg_match('/^#[0-9A-Fa-f]{6}$/', $raw)) {
                        $hex = strtoupper($raw);
                    }
                }

                VariantAttributeValue::create([
                    'attribute_type_id' => $variantType->id,
                    'value' => $option,
                    'color_hex' => $hex,
                    'sort_order' => $index,
                ]);
            }

            // 6) Success feedback and reset form
            $this->resetVariantForm();
            session()->flash('message', $this->editingVariantType ? 'Variant attribute updated successfully!' : 'Variant attribute created successfully!');

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error saving variant attribute: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'variantTitle' => $this->variantTitle,
                'editingVariantType' => $this->editingVariantType
            ]);

            // Attach a general error to a field so the user sees it below an input
            $this->addError('variantTitle', 'An unexpected error occurred while saving. Please try again.');
        }
    }

    public function clearVariantForm()
    {
        $this->resetVariantForm();
    }

    private function resetVariantForm()
    {
        $this->variantTitle = '';
        $this->variantOptions = [];
        $this->variantOptionColors = [];
        $this->currentOption = '';
        $this->variantImage = null;
        $this->existingVariantImages = [];
        $this->editingVariantType = null;
        $this->enableColorPicker = false;
        $this->resetValidation();
    }

    // Tag Management Methods
    public function saveTag()
    {
        $rules = [
            'tagName' => 'required|string|max:100|unique:product_tags,name' . ($this->editingTag ? ',' . $this->editingTag : ''),
            'tagColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'tagDescription' => 'nullable|string|max:500',
        ];

        $this->validate($rules);

        if ($this->editingTag) {
            // Update existing tag
            $tag = ProductTag::findOrFail($this->editingTag);
            $tag->update([
                'name' => $this->tagName,
                'color' => $this->tagColor,
                'description' => $this->tagDescription,
            ]);
            
            session()->flash('message', 'Tag updated successfully!');
        } else {
            // Create new tag
            ProductTag::create([
                'name' => $this->tagName,
                'color' => $this->tagColor,
                'description' => $this->tagDescription,
                'is_active' => true,
            ]);
            
            session()->flash('message', 'Tag created successfully!');
        }

        $this->resetTagForm();
    }

    public function editTag($id)
    {
        $tag = ProductTag::findOrFail($id);
        
        $this->editingTag = $id;
        $this->tagName = $tag->name;
        $this->tagColor = $tag->color;
        $this->tagDescription = $tag->description;
    }

    public function deleteTag($id)
    {
        $tag = ProductTag::findOrFail($id);
        $tag->delete();
        
        session()->flash('message', 'Tag deleted successfully!');
    }

    public function toggleTagStatus($id)
    {
        $tag = ProductTag::findOrFail($id);
        $tag->update(['is_active' => !$tag->is_active]);
        
        session()->flash('message', 'Tag status updated successfully!');
    }

    public function clearTagForm()
    {
        $this->resetTagForm();
    }

    private function resetTagForm()
    {
        $this->tagName = '';
        $this->tagColor = '#007bff';
        $this->tagDescription = '';
        $this->editingTag = null;
        $this->resetValidation();
    }

    // Drag-and-drop ordering: Categories
    public function reorderCategories(array $orderedIds): void
    {
        if (empty($orderedIds)) return;
        foreach ($orderedIds as $index => $id) {
            Category::whereKey($id)->update(['display_order' => $index]);
        }
        $this->resetPage();
    }

    // Drag-and-drop ordering: Variant Attribute Types
    public function reorderVariantTypes(array $orderedIds): void
    {
        if (empty($orderedIds)) return;
        foreach ($orderedIds as $index => $id) {
            VariantAttributeType::whereKey($id)->update(['display_order' => $index]);
        }
    }

    public function render()
    {
        $categories = Category::query()
            ->with('parent')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(10);

        $variantAttributeTypes = VariantAttributeType::with('values')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $tags = ProductTag::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'tagPage');

        return view('livewire.backend.ecommerce-settings', [
            'categories' => $categories,
            'variantAttributeTypes' => $variantAttributeTypes,
            'tags' => $tags,
        ])->layout('layouts.backend.index');
    }
}