<?php

namespace App\Livewire\Backend\Room;

use Livewire\Component;
use App\Services\PetSizeLimitSettingService;
use App\Models\Size as SizeModel;
use App\Models\Room\RoomTypeModel;
use App\Models\Room\PetSizeLimitModel;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Pet Size Limit Setting",
 *     description="pet-size-limit-setting-specific APIs"
 * )
 * 
 */
class PetSizeLimitSetting extends Component
{
    protected $petSizeLimitSettingService;

    public $title = 'Pet Size Limit Setting', $popUp = false;

    // Room type and species selection
    public $room_type_id = null;
    public $selectedSpeciesId = null;
    public $roomTypes = [];
    public $species = [];
    public $sizes = [];
    public $sizeLimits = []; // Array to store limit values for each size ID
    public $petSizeLimits = []; // Current pet size limits for the room type
    public $editId = 0, $deleteId;

    /**
     * Initialize the component with dependency injection
     * Boots the PetSizeLimitSettingService for handling business logic
     * @param PetSizeLimitSettingService $petSizeLimitSettingService Service for pet size limit operations
     */
    public function boot(PetSizeLimitSettingService $petSizeLimitSettingService)
    {
        $this->petSizeLimitSettingService = $petSizeLimitSettingService;
    }

    /**
     * Display the pet size limit setting index page
     * Returns the main view for pet size limit management
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('backend.room.pet-size-limit-setting');
    }

    /**
     * Initialize the component when it's mounted
     * Sets the active submenu and loads initial room type data
     * Called automatically when the component is first rendered
     */
    public function mount()
    {
        \session(['submenu' => 'pet-size-limit-setting']);
        $this->sizes = SizeModel::orderBy('name')->get();
        // Load all room types
        $this->roomTypes = RoomTypeModel::with('species')->orderBy('name')->get();
    }

    public function render()
    {
        // Load species for selected room type
        if ($this->room_type_id) {
            $roomType = RoomTypeModel::with('species')->find($this->room_type_id);
            if ($roomType && $roomType->species) {
                $this->selectedSpeciesId = $roomType->species->id;
                $this->species = [$roomType->species];
                
                // Load all sizes for this species
                $this->sizes = SizeModel::orderBy('name')
                    ->get();
                
                // Load existing pet size limits for this room type
                $this->loadPetSizeLimits();
            } else {
                $this->selectedSpeciesId = null;
                $this->species = [];
                $this->sizes = [];
                $this->sizeLimits = [];
            }
        } else {
            $this->selectedSpeciesId = null;
            $this->species = [];
            $this->sizes = [];
            $this->sizeLimits = [];
        }
        return view('livewire.backend.room.pet-size-limit-setting');
    }

    /**
     * Load existing pet size limits for the selected room type
     * Supports legacy format: [{"12":2},{"13":1}]
     * and new format: [{"pet_size_id":2,"pet_size_name":"Small","limit":7}, ...]
     */
    private function loadPetSizeLimits()
    {
        $petSizeLimit = PetSizeLimitModel::where('room_type_id', $this->room_type_id)->first();
        if ($petSizeLimit && $petSizeLimit->allowed_pet_size) {
            $allowedSizes = json_decode($petSizeLimit->allowed_pet_size, true);
            $this->sizeLimits = [];
            foreach ($allowedSizes as $entry) {
                // New format
                if (is_array($entry) && array_key_exists('pet_size_id', $entry) && array_key_exists('limit', $entry)) {
                    $sizeId = (string)$entry['pet_size_id'];
                    $limit = (int)$entry['limit'];
                    $this->sizeLimits[$sizeId] = $limit;
                    continue;
                }
                // Legacy format fallback
                if (is_array($entry)) {
                    foreach ($entry as $sizeId => $limit) {
                        $this->sizeLimits[(string)$sizeId] = (int)$limit;
                    }
                }
            }
        } else {
            $this->sizeLimits = [];
        }
    }

    /**
     * Save pet size limits for the selected room type
     * Validates form data, converts size limits to JSON format, and saves to database
     * Handles both creating new records and updating existing ones
     * Shows success or error messages based on operation result
     */
    public function savePetSizeLimits()
    {
        $this->validate(\App\Rules\PetSizeLimitSettingRules::rules($this->editId));
        if (!$this->room_type_id) {
            session()->flash('error', 'Please select a room type first.');
            return;
        }

        try {
            // Prepare the JSON data in the new format
            // [{"pet_size_id":2,"pet_size_name":"Small","limit":7}, ...]
            $allowedPetSize = [];
            foreach ($this->sizeLimits as $sizeId => $limit) {
                $numericSizeId = (int)$sizeId;
                $numericLimit = (int)$limit;
                if ($numericLimit > 0) {
                    $sizeModel = collect($this->sizes)->firstWhere('id', $numericSizeId);
                    $allowedPetSize[] = [
                        'pet_size_id' => $numericSizeId,
                        'pet_size_name' => $sizeModel ? ($sizeModel->name ?? $sizeModel['name'] ?? '') : '',
                        'limit' => $numericLimit,
                    ];
                }
            }

            // Check if pet size limit already exists for this room type
            $petSizeLimit = PetSizeLimitModel::where('room_type_id', $this->room_type_id)->first();
            
            if ($petSizeLimit) {
                // Update existing record
                $petSizeLimit->update([
                    'allowed_pet_size' => json_encode($allowedPetSize),
                    'updated_by' => Auth::id(),
                ]);
            } else {
                // Create new record
                PetSizeLimitModel::create([
                    'room_type_id' => $this->room_type_id,
                    'allowed_pet_size' => json_encode($allowedPetSize),
                    'current_room_capacity' => 0, // Default value
                    'status' => 'active',
                    'created_by' => Auth::id(),
                ]);
            }

            session()->flash('success', 'Pet size limits saved successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error saving pet size limits: ' . $e->getMessage());
        }
    }

    /**
     * Clear success message from session
     * Removes the success flash message from the session storage
     * Called when user dismisses success notifications or when component re-renders
     */
    public function clearSuccessMessage()
    {
        session()->forget('success');
    }
}
