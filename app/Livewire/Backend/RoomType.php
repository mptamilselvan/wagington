<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
//use App\Models\Suite;
use App\Models\Species;
//use App\Models\SuiteRoomType;
use App\Models\Product;
use App\Models\RoomTypeModel;
use App\Models\RoomPriceOptionModel;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RoomType extends Component
{
    use WithFileUploads, WithPagination;

    // UI State
    public $showForm = false;
    public $showList = true;
    public $showModal = false;
    public $editingRoomType = null;
    public $search = '';
    public $filterBy = '';
    public $showFilterDropdown = false;
    public $filterSpecies = '';
    public $filterRoomType = '';
    public $showDeleteModal = false;
    public $roomTypeToDelete = null;
    public $showSuccessPopup = false;
    public $successMessage = '';
    public $serviceCategories = [];

     // Form Properties 
    public $room_type_id = null;
    public $name = '';
    public $slug = '';
    public $species_id = null;
    public $room_types = [];
    public $room_attributes = [];
    public $room_amenities = [];
    public $newAttribute = '';
    public $newAmenity = '';
    public $room_description = null;
    public $room_overview = null;
    public $room_highlights = null;
    public $room_terms_and_conditions = null;
    public $images = [];
    public $imagePrimary = []; // For new images primary selection
    public $existingImagePrimary = []; // For existing images primary selection
    public $newImagePrimary = []; // For new images in edit mode
    public $service_addons = [];
    public $selected_addon_id = null; // For dropdown selection
    public $evaluation_required = false;
    public $default_clean_minutes = null;
    public $turnover_buffer_min = null;
    public $seo_title = null;
    public $seo_description = null;
    public $seo_keywords = null;
    // Dynamic arrays for form sections
    public $aggreed_terms = [
        ['content' => '', 'document' => null]
    ];

    public $price_options = [
        ['label' => '', 'no_of_days' => '', 'price' => '']
    ];
  
    // Image preview properties
    public $imagePreviews = [];
    public $existingImages = []; // For existing images in edit mode
    
    // Document preview properties for agreed terms
    public $documentPreviews = [];
    
    // Deletion-related properties
    public $deleteType = 'room_type';
    public $selectedRoomTypeToDelete = null;
    public $deletionDependencies = [];
    public $canDelete = true;
    public $deleteWarnings = [];
    public $isLoadingDependencies = false;

    // List view filter: products | addons | all
    public $listType = 'room_types';

    // Pagination
    public $perPage = 4;

    // Original values (for update mode comparison)
    public $original_room_type_id = null;
    public $original_name = null;
    public $original_species_id = null;
   
    /**
     * Mount the component
     * @return void
     */
    public function mount()
    {
        $this->loadData();

    }

    /**
     * Load the data for the component
     * @return void
     */
    public function loadData()
    {
        // Load all categories so that previously selected ones (even if inactive now)
        // still appear in the dropdown when editing an existing product.
       // $this->serviceCategories = ServiceCategory::orderBy('name')->get();
       
       // $this->roomTypes = RoomTypeModel::orderBy('name')->get();
     
    }

    /**
     * Reset the form for the component
     * @return void
     */
    public function resetForm()
    {
        $this->editingRoomType = null;
        $this->name = '';
        $this->slug = '';
        $this->species_id = '';
        $this->room_type_id = null;
        $this->room_attributes = [];
        $this->room_amenities = [];
        $this->newAttribute = '';
        $this->newAmenity = '';
        $this->room_description = '';
        $this->room_overview = '';
        $this->room_highlights = '';
        $this->room_terms_and_conditions = '';
        $this->default_clean_minutes = null;
        $this->turnover_buffer_min = null;
        $this->images = [];
        $this->imagePreviews = [];
        $this->existingImages = [];
        $this->documentPreviews = [];
        $this->imagePrimary = [];
        $this->existingImagePrimary = [];
        $this->newImagePrimary = [];
        $this->service_addons = [];
        $this->selected_addon_id = null;
        $this->aggreed_terms = [];
        $this->evaluation_required = false;
        $this->aggreed_terms = [['content' => '', 'document' => null]];
        $this->price_options = [['label' => '', 'no_of_days' => '', 'price' => '']];
        $this->seo_title = null;
        $this->seo_description = null;
        $this->seo_keywords = null;
        $this->resetValidation();
    }

    /**
     * Set the default values for the component
     * @return void
     */
    public function setDefaltValues(){
        $this->name = '';
        $this->slug = '';
        $this->species_id = 1;
        $this->room_type_id = 1;
        $this->room_attributes = [];
        $this->room_amenities = [];
        $this->room_description = "";
        $this->room_overview = "";
        $this->room_highlights = null;
        $this->room_terms_and_conditions = "";
        $this->images = [];
        $this->service_addons = [];
        $this->aggreed_terms = [
            ['content' => '', 'document' => null]
        ];
        $this->evaluation_required = false;
        $this->default_clean_minutes = null;
        $this->turnover_buffer_min = null;
        $this->seo_title = null;
        $this->seo_description = null;
        $this->seo_keywords = null;
        $this->price_options = [['label' => '', 'no_of_days' => '', 'price' => '']];
    }

    /**
     * Create a new room type
     * @return void
     */
     public function createRoomType()
    {
        $this->resetForm();
        $this->setDefaltValues();
        $this->showForm = true;
        $this->showList = false;
    }
    
    /**
     * Edit a room type
     * @param int $roomTypeId
     * @return void
     */
    public function editRoomType($roomTypeId)
    {
        $this->resetForm();
        $roomType = RoomTypeModel::findOrFail($roomTypeId);
        $this->editingRoomType = $roomType;
        
        // Load room type data into form
        $this->room_type_id = $roomType->id;
        $this->name = $roomType->name;
        $this->slug = $roomType->slug;
        $this->species_id = $roomType->species_id;
        // Handle JSON data for room_attributes and room_amenities
        $this->room_attributes = $this->parseJsonField($roomType->room_attributes);
        $this->room_amenities = $this->parseJsonField($roomType->room_amenities);
        $this->newAttribute = '';
        $this->newAmenity = '';
        $this->room_description = $roomType->room_description;
        $this->room_overview = $roomType->room_overview;
        $this->room_highlights = $roomType->room_highlights;
        $this->room_terms_and_conditions = $roomType->room_terms_and_conditions;
        $this->evaluation_required = $roomType->evaluation_required;
        $this->seo_title = $roomType->seo_title;
        $this->seo_description = $roomType->seo_description;
        $this->seo_keywords = $roomType->seo_keywords;
        // Load existing images - ensure it's an array and extract URLs for previews
        $existingImages = [];
        if (is_array($roomType->images)) {
            $existingImages = $roomType->images;
        } elseif (is_string($roomType->images)) {
            $existingImages = json_decode($roomType->images, true) ?: [];
        }
        
        // Store existing images separately
        $this->existingImages = $existingImages;
        
        // Clear new images array for fresh uploads
        $this->images = [];
        $this->imagePreviews = [];
        
        // Initialize primary arrays for existing images
        $this->existingImagePrimary = [];
        $this->newImagePrimary = [];
        if (!empty($existingImages)) {
            foreach ($existingImages as $index => $image) {
                if (is_array($image) && isset($image['primary'])) {
                    $this->existingImagePrimary[$index] = $image['primary'];
                } else {
                    $this->existingImagePrimary[$index] = false;
                }
            }
        }
        
        // Load agreed terms - ensure it's an array
        if (is_array($roomType->aggreed_terms)) {
            $this->aggreed_terms = $roomType->aggreed_terms;
        } elseif (is_string($roomType->aggreed_terms)) {
            $this->aggreed_terms = json_decode($roomType->aggreed_terms, true) ?: [['content' => '', 'document' => null]];
        } else {
            $this->aggreed_terms = [['content' => '', 'document' => null]];
        }
        
        // Load price options from room_price_options table
        $priceOptions = RoomPriceOptionModel::where('room_type_id', $roomType->id)
            ->where('status', 'active')
            ->get();
        
        if ($priceOptions->count() > 0) {
            $this->price_options = $priceOptions->map(function($option) {
                return [
                    'label' => $option->label,
                    'no_of_days' => $option->no_of_days,
                    'price' => $option->price
                ];
            })->toArray();
        } else {
            $this->price_options = [['label' => '', 'no_of_days' => '', 'price' => '']];
        }
        
        // Load service addons - ensure it's an array
        if (is_array($roomType->service_addons)) {
            $this->service_addons = $roomType->service_addons;
        } elseif (is_string($roomType->service_addons)) {
            $this->service_addons = json_decode($roomType->service_addons, true) ?: [];
        } else {
            $this->service_addons = [];
        }
        
        $this->showForm = true;
        $this->showList = false;
    }

    /**
     * Close the form for the component
     * @return void
     */
    public function closeForm()
    {
        $this->showForm = false;
        $this->showList = true;
        $this->resetForm();
    }
    
    /**
     * Close the success popup for the component
     * @return void
     */
    public function closeSuccessPopup()
    {
        $this->showSuccessPopup = false;
        $this->successMessage = '';
        $this->resetForm();
    }
    
    /**
     * Initiate the delete for the component
     * @param int $roomTypeId
     * @return void
     */
      public function initiateDelete($roomTypeId)
    {
        $this->roomTypeToDelete = RoomTypeModel::find($roomTypeId);
        $this->checkDeletionDependencies($roomTypeId);
        $this->showDeleteModal = true;
    }

    /**
     * Check the deletion dependencies for the component
     * @param int $roomTypeId
     * @return void
     */
    public function checkDeletionDependencies($roomTypeId)
    {
        $this->deletionDependencies = [];
        $this->deleteWarnings = [];
        $this->canDelete = true;
        $this->isLoadingDependencies = true;
        // Check if this room type is being used by any rooms
        $roomsUsingThisType = \App\Models\RoomModel::where('room_type_id', $roomTypeId)->count();
        
        if ($roomsUsingThisType > 0) {
            $this->canDelete = false;
            $this->deleteWarnings = "Cannot delete room type '{$this->roomTypeToDelete->name}' because it is being used by {$roomsUsingThisType} room(s). Please remove or reassign the rooms first.";
            $rooms = \App\Models\RoomModel::where('room_type_id', $roomTypeId)->get();
            $roomsArray = [];
            foreach ($rooms as $room) {
                $roomsArray[] = [
                    "name" => $room->name,
                    "room_type" => $room->roomType->name
                ];
            }
            $this->deletionDependencies['rooms'] = $roomsArray;
        }
        //$this->deleteWarnings = $warnings;
        $this->isLoadingDependencies = false;
    }
    // Delete functionality
   /* public function initiateDelete($roomTypeId)
    {
        $this->roomTypeToDelete = null;
    }*/

    /**
     * Reset the deletion state for the component
     * @return void
     */
    private function resetDeletionState()
    {
        $this->deletionDependencies = [];
        $this->canDelete = true;
        $this->deleteWarnings = [];
        $this->isLoadingDependencies = false;
    }
    /**
     * Cancel the delete for the component
     * @return void
     */
    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->resetDeletionState();
        $this->roomTypeToDelete = null;
    }

    /**
     * Confirm the delete for the component
     * @return void
     */
    public function confirmDelete()
    {
       // if ($this->roomTypeToDelete) {
            try {
                
                $roomType = RoomTypeModel::findOrFail($this->roomTypeToDelete->id);
                
                $roomTypeName = $roomType->name;
                /*
                // Check if this room type is being used by any rooms
                $roomsUsingThisType = \App\Models\RoomModel::where('room_type_id', $this->roomTypeToDelete)->count();
                
                if ($roomsUsingThisType > 0) {
                    $this->roomTypeToDelete = null;
                    session()->flash('error', "Cannot delete room type '{$roomTypeName}' because it is being used by {$roomsUsingThisType} room(s). Please remove or reassign the rooms first.");
                    //session()->flash('message_type', 'error');
                    return;
                }*/
                
                $roomType->delete();
               
                $this->cancelDelete();
            // Show success popup
                $this->showSuccessPopup = true;
                $this->successMessage = "Room Type '{$roomTypeName}' has been deleted successfully!";
                session()->flash('success', $this->successMessage);
            } catch (\Exception $e) {
                $this->cancelDelete();
                $this->showDeleteModal = false;
                session()->flash('error', 'Error deleting room type: ' . $e->getMessage());
            }
        //}
    }
    
    // Dynamic form management methods
    /**
     * Add a new agreed term to the component
     * @return void
     */
    public function addAgreedTerm()
    {
        $this->aggreed_terms[] = ['content' => '', 'document' => null];
    }
    
    /**
     * Remove an agreed term from the component
     * @param int $index
     * @return void
     */
    public function removeAgreedTerm($index)
    {
        if (count($this->aggreed_terms) > 1) {
            unset($this->aggreed_terms[$index]);
            $this->aggreed_terms = array_values($this->aggreed_terms);
        }
    }
    
    /**
     * Add a new price option to the component
     * @return void
     */
    public function addPriceOption()
    {
        $this->price_options[] = ['label' => '', 'no_of_days' => '', 'price' => ''];
    }
    
    /**
     * Remove a price option from the component
     * @param int $index
     * @return void
     */
    public function removePriceOption($index)
    {
        if (count($this->price_options) > 1) {
            unset($this->price_options[$index]);
            $this->price_options = array_values($this->price_options);
        }
    }
    
    // Image management methods
    /**
     * Update the images for the component
     * @return void
     */
    public function updatedImages()
    {
        // Only update previews for new images, don't touch existing images
        $this->imagePreviews = [];
        if ($this->images) {
            foreach ($this->images as $image) {
                $this->imagePreviews[] = $image->temporaryUrl();
            }
        }
        
        // Ensure existingImagePrimary array is preserved when new images are uploaded
        if (!empty($this->existingImages)) {
            $existingCount = count($this->existingImages);
            for ($i = 0; $i < $existingCount; $i++) {
                if (!isset($this->existingImagePrimary[$i])) {
                    $this->existingImagePrimary[$i] = false;
                }
            }
        }
        
        // Auto-set primary flag for new images
        $this->updateNewImagePrimaryFlags();
    }
    
    /**
     * Update primary flags for new images based on business rules
     * @return void
     */
    private function updateNewImagePrimaryFlags()
    {
        $newImageCount = count($this->images);
        $existingImageCount = count($this->existingImages);
        
        // If there's only one new image and no existing images, set it as primary
        if ($newImageCount == 1 && $existingImageCount == 0) {
            $this->newImagePrimary = [true]; // Set first (and only) new image as primary
        } elseif ($newImageCount > 1 && $existingImageCount == 0) {
            // If multiple new images and no existing images, set first one as primary
            $this->newImagePrimary = array_fill(0, $newImageCount, false);
            $this->newImagePrimary[0] = true; // Set first image as primary
        } elseif ($newImageCount > 0 && $existingImageCount > 0) {
            // If there are existing images, don't auto-set primary for new images
            $this->newImagePrimary = array_fill(0, $newImageCount, false);
        }
    }
    
    /**
     * Remove an image from the component
     * @param int $index
     * @return void
     */
    public function removeImage($index)
    {
        if (isset($this->images[$index])) {
            unset($this->images[$index]);
            $this->images = array_values($this->images);
            
            // Update previews
            $this->imagePreviews = [];
            if ($this->images) {
                foreach ($this->images as $image) {
                    $this->imagePreviews[] = $image->temporaryUrl();
                }
            }
            
            // Remove corresponding primary flag
            if (isset($this->newImagePrimary[$index])) {
                unset($this->newImagePrimary[$index]);
                $this->newImagePrimary = array_values($this->newImagePrimary);
            }
            
            // Update primary flags after removal
            $this->updateNewImagePrimaryFlags();
        }
    }
    
    /**
     * Remove an existing image from the component
     * @param int $index
     * @return void
     */
    public function removeExistingImage($index)
    {
        if (isset($this->existingImages[$index])) {
            // Remove from existing images array
            unset($this->existingImages[$index]);
            $this->existingImages = array_values($this->existingImages);
            
            // Remove corresponding primary flag
            if (isset($this->existingImagePrimary[$index])) {
                unset($this->existingImagePrimary[$index]);
                $this->existingImagePrimary = array_values($this->existingImagePrimary);
            }
            
            // Update primary flags after removal - if no existing images left, auto-set primary for new images
            $this->updateNewImagePrimaryFlags();
        }
    }
    
    // Document management methods for agreed terms
    /**
     * Update the agreed terms for the component
     * @param mixed $value
     * @param string $key
     * @return void
     */
    /**
     * Handle individual document updates for agreed terms
     * @param mixed $value
     * @param string $key
     * @return void
     */
    public function updatedAggreedTerms($value = null, $key = null)
    {
        // If key is provided, handle individual field updates
        if ($key !== null) {
            // Only handle document updates, not content updates
            if (str_contains($key, '.document')) {
                // Extract the index from the key (e.g., "0.document" -> 0)
                $index = explode('.', $key)[0];
                
                if ($value) {
                    // Generate preview URL for the uploaded document
                    $this->documentPreviews[$index] = $value->temporaryUrl();
                } else {
                    // Clear preview if document is removed
                    unset($this->documentPreviews[$index]);
                }
            }
        } else {
            // Handle array updates (legacy behavior)
            $this->documentPreviews = [];
            foreach ($this->aggreed_terms as $index => $term) {
                if (isset($term['document']) && $term['document']) {
                    $this->documentPreviews[$index] = $term['document']->temporaryUrl();
                } else {
                    $this->documentPreviews[$index] = null;
                }
            }
        }
    }

    /**
     * Alternative approach: Handle document updates via updated hook
     * @param mixed $value
     * @param string $key
     * @return void
     */
    public function updated($property, $value)
    {
        // Handle document updates
        if (str_contains($property, 'aggreed_terms') && str_contains($property, 'document')) {
            $index = explode('.', $property)[1]; // Get index from property name
            
            if ($value) {
                $this->documentPreviews[$index] = $value->temporaryUrl();
            } else {
                unset($this->documentPreviews[$index]);
            }
        }
    }
    
    /**
     * Remove a document from the component
     * @param int $termIndex
     * @return void
     */
    public function removeDocument($termIndex)
    {
        if (isset($this->aggreed_terms[$termIndex])) {
            // Clear any newly uploaded temporary document
            $this->aggreed_terms[$termIndex]['document'] = null;

            // Also clear existing persisted document URL so the preview disappears immediately
            if (isset($this->aggreed_terms[$termIndex]['document_url'])) {
                unset($this->aggreed_terms[$termIndex]['document_url']);
            }

            // Remove any live preview entry
            unset($this->documentPreviews[$termIndex]);
        }
    }
    
    // Service addon management methods
    /**
     * Update the selected addon id for the component
     * @param int $value
     * @return void
     */
    public function updatedSelectedAddonId($value)
    {
        if ($value) {
            // Get all service addons from database
            $serviceAddons = Product::where('product_type', 'addon')->get();
            $selectedAddon = $serviceAddons->firstWhere('id', $value);
            
            if ($selectedAddon) {
                // Check if addon is already selected
                $alreadySelected = collect($this->service_addons)->contains('id', $value);
                
                if (!$alreadySelected) {
                    $this->service_addons[] = [
                        'id' => $selectedAddon->id,
                        'name' => $selectedAddon->name
                    ];
                }
            }
            
            // Reset the dropdown
            $this->selected_addon_id = null;
        }
    }
    
    /**
     * Remove an addon from the component
     * @param int $index
     * @return void
     */
    public function removeAddon($index)
    {
        if (isset($this->service_addons[$index])) {
            unset($this->service_addons[$index]);
            $this->service_addons = array_values($this->service_addons);
        }
    }
    
    // Filter methods
    /**
     * Toggle the filter dropdown for the component
     * @return void
     */
    public function toggleFilterDropdown()
    {
        $this->showFilterDropdown = !$this->showFilterDropdown;
    }
    
    /**
     * Apply the filters for the component
     * @return void
     */
    public function applyFilters()
    {
        $this->showFilterDropdown = false;
        // The filtering will be handled in the render method
    }
    
    /**
     * Clear the filters for the component
     * @return void
     */
    public function clearFilters()
    {
        $this->filterSpecies = '';
        $this->filterRoomType = '';
        $this->filterBy = '';
    }
    
    /**
     * Save the room type for the component
     * @return void
     */
    public function saveRoomType()
    {

        $this->validate(\App\Rules\RoomTypeRules::rules($this->room_type_id));
        
        try {
            // Handle image uploads with primary flag
            $imageUrls = [];
            if ($this->images) {
                foreach ($this->images as $index => $image) {

                    if (!empty($image) && Storage::disk('do_spaces')->exists($image->getClientOriginalName())) {
                        Storage::disk('do_spaces')->delete($image->getClientOriginalName());
                    }
                    // Upload new one
                    $path = $image->getClientOriginalName()->store('room_types', 'do_spaces');
                    $imageUrls[] = [
                        'url' => ImageService::getPublicUrl($path),
                        'primary' => isset($this->imagePrimary[$index]) ? (bool)$this->imagePrimary[$index] : false
                    ];
                }
            }
            
            // Handle document uploads for agreed terms
            $processedAgreedTerms = [];
            foreach ($this->aggreed_terms as $index => $term) {
                $processedTerm = [
                    'content' => $term['content']
                ];
                
                if (isset($term['document']) && $term['document']) {
                    if (!empty($term['document']) && Storage::disk('do_spaces')->exists($term['document'])) {
                       // Storage::disk('do_spaces')->delete($term['document']);
                    }
                    // Upload new one
                    $path = $term['document']->store('room_types/documents', 'do_spaces');
                    $processedTerm['document_url'] = ImageService::getPublicUrl($path);
                }
                
                $processedAgreedTerms[] = $processedTerm;
            }
            
            // Create room type record
            // Don't use json_encode here - the model's $casts will handle it automatically
            $roomType = RoomTypeModel::create([
                'name' => $this->name,
                'slug' => $this->slug,
                'species_id' => $this->species_id,
                'room_attributes' => $this->room_attributes,
                'room_amenities' => $this->room_amenities,
                'room_description' => $this->room_description,
                'room_overview' => $this->room_overview,
                'room_highlights' => $this->room_highlights,
                'room_terms_and_conditions' => $this->room_terms_and_conditions,
                'images' => $imageUrls,
                'service_addons' => $this->service_addons,
                'aggreed_terms' => $processedAgreedTerms,
                'default_clean_minutes' => $this->default_clean_minutes,
                'turnover_buffer_min' => $this->turnover_buffer_min,
                'evaluation_required' => $this->evaluation_required ?? false,
                'seo_title' => $this->seo_title,
                'seo_description' => $this->seo_description,
                'seo_keywords' => $this->seo_keywords,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);
            
            // Handle price options - insert into room_price_options table
            if (!empty($this->price_options)) {
                foreach ($this->price_options as $option) {
                    if (!empty($option['label']) && !empty($option['no_of_days']) && !empty($option['price'])) {
                        RoomPriceOptionModel::create([
                            'room_type_id' => $roomType->id,
                            'label' => $option['label'],
                            'no_of_days' => $option['no_of_days'],
                            'price' => $option['price'],
                            'status' => 'active',
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }
            
            // Show success message using session flash
            session()->flash('success', 'Room Type has been successfully created!');
            
            // Switch to list view
            $this->showForm = false;
            $this->showList = true;
            
        } catch (\Exception $e) {
            $this->addError('save', 'Error saving room type: ' . $e->getMessage());
        }
    }
    
    /**
     * Update the room type for the component
     * @return void
     */
    public function updateRoomType()
    {
        $this->validate(\App\Rules\RoomTypeRules::rules($this->room_type_id));
        
        try {
            $roomType = RoomTypeModel::findOrFail($this->room_type_id);
            
            // Handle image uploads with primary flags
            // Use the existingImages array which reflects any removals made by the user
            $imageUrls = $this->existingImages ?? []; // Keep existing images (after any removals)
            
            // Ensure imageUrls is an array
            if (!is_array($imageUrls)) {
                $imageUrls = [];
            }
            
            // Process existing images to ensure proper format
            $processedExistingImages = [];
            foreach ($imageUrls as $index => $image) {
                // Skip empty or invalid images
                if (empty($image)) {
                    continue;
                }
                
                $imageUrl = '';
                $isPrimary = false;
                
                // Extract URL from different formats
                if (is_string($image)) {
                    $imageUrl = $image;
                } elseif (is_array($image)) {
                    if (isset($image['url']) && is_string($image['url'])) {
                        $imageUrl = $image['url'];
                    } else {
                        // Skip malformed images
                        continue;
                    }
                    $isPrimary = isset($image['primary']) ? (bool)$image['primary'] : false;
                } else {
                    // Skip invalid image formats
                    continue;
                }
                
                // Apply primary flag from existingImagePrimary if available
                if (isset($this->existingImagePrimary[$index])) {
                    $isPrimary = (bool)$this->existingImagePrimary[$index];
                }
                
                // Only add if we have a valid URL
                if (!empty($imageUrl)) {
                    $processedExistingImages[] = [
                        'url' => $imageUrl,
                        'primary' => $isPrimary
                    ];
                }
            }
            
            $imageUrls = $processedExistingImages;
            
            // Add new images with primary flags
            if ($this->images) {
                foreach ($this->images as $index => $image) {
                   // $path = $image->store('room_types/images', 'public');
                   if (!empty($image) && Storage::disk('do_spaces')->exists($image)) {
                        Storage::disk('do_spaces')->delete($image);
                    }
                    // Upload new one
                    $path = $image->store('room_types', 'do_spaces');
                    $imageUrls[] = [
                        'url' => ImageService::getPublicUrl($path),
                        'primary' => isset($this->newImagePrimary[$index]) ? (bool)$this->newImagePrimary[$index] : false
                    ];
                }
            }
            
            // Handle document uploads for agreed terms
            $processedAgreedTerms = [];
            foreach ($this->aggreed_terms as $index => $term) {
                $processedTerm = [
                    'content' => $term['content']
                ];
                
                if (isset($term['document']) && $term['document']) {
                    if (!empty($term['document']) && Storage::disk('do_spaces')->exists($term['document'])) {
                        Storage::disk('do_spaces')->delete($term['document']);
                    }
                    // Upload new one
                    $path = $term['document']->store('room_types/documents', 'do_spaces');
                    $processedTerm['document_url'] = ImageService::getPublicUrl($path);
                } elseif (isset($term['document_url'])) {
                    // Keep existing document URL
                    $processedTerm['document_url'] = $term['document_url'];
                }
                
                $processedAgreedTerms[] = $processedTerm;
            }
            
            // Update room type record
            // Don't use json_encode here - the model's $casts will handle it automatically
            $roomType->update([
                'name' => $this->name,
                'slug' => $this->slug,
                'species_id' => $this->species_id,
                'room_attributes' => $this->room_attributes,
                'room_amenities' => $this->room_amenities,
                'room_description' => $this->room_description,
                'room_overview' => $this->room_overview,
                'room_highlights' => $this->room_highlights,
                'room_terms_and_conditions' => $this->room_terms_and_conditions,
                'images' => $imageUrls,
                'service_addons' => $this->service_addons,
                'aggreed_terms' => $processedAgreedTerms,
                'default_clean_minutes' => $this->default_clean_minutes,
                'turnover_buffer_min' => $this->turnover_buffer_min,
                'evaluation_required' => $this->evaluation_required ?? false,
                'seo_title' => $this->seo_title,
                'seo_description' => $this->seo_description,
                'seo_keywords' => $this->seo_keywords,
                'updated_by' => auth()->id(),
            ]);
            
            // Handle price options - update room_price_options table
            // First, delete existing price options for this room type
            RoomPriceOptionModel::where('room_type_id', $roomType->id)->delete();
            
            // Then insert new price options
            if (!empty($this->price_options)) {
                foreach ($this->price_options as $option) {
                    if (!empty($option['label']) && !empty($option['no_of_days']) && !empty($option['price'])) {
                        RoomPriceOptionModel::create([
                            'room_type_id' => $roomType->id,
                            'label' => $option['label'],
                            'no_of_days' => $option['no_of_days'],
                            'price' => $option['price'],
                            'status' => 'active',
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }
            
            // Show success message using session flash
            session()->flash('success', 'Room Type has been successfully updated!');
            $this->closeForm();
            
        } catch (\Exception $e) {
            \Log::error('Error updating room type: ' . $e->getMessage());
            $this->addError('update', 'Error updating room type: ' . $e->getMessage());
        }
    }

    /**
     * Render the component
     * @return void
     * @return \Illuminate\Contracts\View\View
     * @throws \Exception
     */
    public function render()
    {
        $roomTypesQuery = RoomTypeModel::with(['species', 'roomPriceOptions', 'petsizeLimits']);

        if ($this->listType === 'room_types') {
           // $roomTypesQuery->where('status', 'active');
        } // 'all' shows all types

        // Apply search filter (case-insensitive)
        if (!empty($this->search)) {
            $searchTerm = strtolower($this->search);
            $roomTypesQuery->where(function($query) use ($searchTerm) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereHas('species', function($q) use ($searchTerm) {
                          $q->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%']);
                      });
            });
        }

        // Apply species filter
        if (!empty($this->filterSpecies)) {
            $roomTypesQuery->where('species_id', $this->filterSpecies);
        }

        // Apply room type filter
        if (!empty($this->filterRoomType)) {
            $roomTypesQuery->where('room_type_id', $this->filterRoomType);
        }

        $roomTypes = $roomTypesQuery->orderBy('created_at', 'desc')->paginate($this->perPage);

        // Load dropdown data for the form
        $species = Species::orderBy('name')->get();
        //$serviceCategories = ServiceCategory::orderBy('name')->get();
        $serviceCategories = [];
       // $serviceAddons = Product::where('product_type', 'addon')->orderBy('name')->get();
       $serviceAddons = Product::where('product_type', 'addon')->orderBy('name')->get();
        return view('livewire.backend.room-type', [
            'roomTypes' => $roomTypes,
            'species' => $species,
            'serviceCategories' => $serviceCategories,
            'serviceAddons' => $serviceAddons,
        ])->layout('layouts.backend.index');
    }

    /**
     * Clear success message from session
     * @return void
     */
    public function clearSuccessMessage()
    {
        session()->forget('success');
    }
     public function updatedName($value)
    {
        $this->slug = $this->generateUniqueSlug($value);
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
        $query = RoomTypeModel::where('slug', $slug);
        
        if ($this->editingRoomType) {
            $query->where('id', '!=', $this->room_type_id);
        }
        
        return $query->exists();
    }

    /**
     * Parse JSON field data - handles both JSON arrays and legacy text data
     */
    private function parseJsonField($data)
    {
        if (empty($data)) {
            return [];
        }

        // If it's already an array, return it
        if (is_array($data)) {
            return $data;
        }

        // Try to decode as JSON
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // If it's a JSON object with 'text' key (from migration), extract the text
            if (isset($decoded['text']) && is_string($decoded['text'])) {
                return [$decoded['text']];
            }
            // If it's a JSON array, return it
            return $decoded;
        }

        // If it's plain text, return as single-item array
        return [$data];
    }

    /**
     * Add a new attribute
     */
    public function addAttribute()
    {
        if (!empty(trim($this->newAttribute))) {
            $attribute = trim($this->newAttribute);
            if (!in_array($attribute, $this->room_attributes)) {
                $this->room_attributes[] = $attribute;
            }
            $this->newAttribute = '';
        }
    }

    /**
     * Remove an attribute by index
     */
    public function removeAttribute($index)
    {
        if (isset($this->room_attributes[$index])) {
            unset($this->room_attributes[$index]);
            $this->room_attributes = array_values($this->room_attributes); // Reindex array
        }
    }

    /**
     * Add a new amenity
     */
    public function addAmenity()
    {
        if (!empty(trim($this->newAmenity))) {
            $amenity = trim($this->newAmenity);
            if (!in_array($amenity, $this->room_amenities)) {
                $this->room_amenities[] = $amenity;
            }
            $this->newAmenity = '';
        }
    }

    /**
     * Remove an amenity by index
     */
    public function removeAmenity($index)
    {
        if (isset($this->room_amenities[$index])) {
            unset($this->room_amenities[$index]);
            $this->room_amenities = array_values($this->room_amenities); // Reindex array
        }
    }

}