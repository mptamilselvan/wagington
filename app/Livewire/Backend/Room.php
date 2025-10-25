<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\RoomModel;
use App\Models\Species;
use App\Models\RoomTypeModel;
use App\Models\Product;


class Room extends Component
{
    use WithFileUploads, WithPagination;

    // UI State
    public $showForm = false;
    public $showList = true;
    public $showModal = false;
    public $editingRoom = null;
    public $search = '';
    public $filterBy = '';
    public $showFilterDropdown = false;
    public $filterSpecies = '';
    public $filterRoomType = '';
    public $showDeleteModal = false;
    public $roomToDelete = null;
    public $showSuccessPopup = false;
    public $successMessage = '';
    public $serviceCategories = [];

     // Form Properties 
    public $room_id = null;
    public $name = '';
    public $room_type_id = null;
    public $cctv_stream = null;
    public $status = '';
    
    // Deletion-related properties
    public $deleteType = 'room';
    public $selectedRoomToDelete = null;
    public $deletionDependencies = [];
    public $canDelete = true;
    public $deleteWarnings = [];
    public $isLoadingDependencies = false;

    // List view filter: products | addons | all
    public $listType = 'rooms';

    // Pagination
    public $perPage = 4;

    // Original values (for update mode comparison)
    public $original_room_id = null;
    public $original_name = null;
    public $original_room_type_id = null;

   

    
    /**
     * Initialize the component when it's mounted
     * Loads initial data and sets up the component state
     */
    public function mount()
    {
        $this->loadData();

    }

    /**
     * Load initial data for the component
     * Currently loads service categories and room types for dropdowns
     * Can be extended to load other required data
     */
    public function loadData()
    {
        // Load all categories so that previously selected ones (even if inactive now)
        // still appear in the dropdown when editing an existing product.
       // $this->serviceCategories = ServiceCategory::orderBy('name')->get();
       
       // $this->roomTypes = RoomType::orderBy('name')->get();
     
    }
    /**
     * Reset all form fields to their default values
     * Clears all form inputs and validation errors
     * Used when switching between create/edit modes or canceling operations
     */
    public function resetForm()
    {
        $this->editingSuite = null;
        $this->name = '';
        $this->room_type_id = '';
        $this->cctv_stream = '';
        $this->status = '';
        $this->resetValidation();
    }
    /**
     * Set default values for form fields during room creation
     * Provides sample data to help users understand the expected format
     * Used for testing and demonstration purposes
     */
    public function setDefaltValues(){
        $this->name = 'hotel1';
        $this->room_type_id = 1;
        $this->cctv_stream = "https://www.youtube.com/watch?v=dQw4w9WgXcQ";
    }
    /**
     * Initialize the room creation form
     * Resets form fields, sets default values, and switches to form view
     * Called when user clicks "Add New Room" button
     */
     public function createRoom()
    {
        $this->resetForm();
        $this->setDefaltValues();
        $this->showForm = true;
        $this->showList = false;
    }
    
    /**
     * Initialize the room editing form
     * Loads existing room data into form fields and switches to form view
     * @param int $roomId The ID of the room to edit
     */
    public function editRoom($roomId)
    {
        $room = RoomModel::findOrFail($roomId);
        $this->editingRoom = $room;
        
        // Load suite data into form
        $this->room_id = $room->id;
        $this->name = $room->name;
        $this->room_type_id = $room->room_type_id;
        $this->cctv_stream = $room->cctv_stream;
        $this->status = $room->status;
        
            
        $this->showForm = true;
        $this->showList = false;
    }
    /**
     * Close the form and return to list view
     * Resets form fields and switches back to the room list
     * Called when user cancels form or after successful save
     */
    public function closeForm()
    {
        $this->showForm = false;
        $this->showList = true;
        $this->resetForm();
    }
    
    /**
     * Close the success popup modal
     * Hides the success message and clears the message text
     * Called when user clicks close button on success popup
     */
    public function closeSuccessPopup()
    {
        $this->showSuccessPopup = false;
        $this->successMessage = '';
    }
    
    /**
     * Clear success message and hide popup
     * Alternative method to closeSuccessPopup() for programmatic clearing
     * Used internally by the component
     */
    public function clearSuccessMessage()
    {
        $this->showSuccessPopup = false;
        $this->successMessage = '';
    }
    
    /**
     * Switch from form view back to list view
     * Used after successful room creation to return to the room list
     * Called programmatically with a delay to show success message first
     */
    public function switchToListView()
    {
        $this->showForm = false;
        $this->showList = true;
        $this->resetForm();
    }
    
    // Delete functionality
    /**
     * Initiate the room deletion process
     * Sets the room ID to be deleted and shows confirmation modal
     * @param int $roomId The ID of the room to delete
     */
    public function initiateDelete($roomId)
    {
        $this->roomToDelete = $roomId;
    }
    
    /**
     * Cancel the room deletion process
     * Clears the room ID and hides the confirmation modal
     * Called when user clicks "Cancel" in delete confirmation
     */
    public function cancelDelete()
    {
        $this->roomToDelete = null;
    }
    
    /**
     * Confirm and execute the room deletion
     * Permanently deletes the room from the database
     * Shows success message on successful deletion or error message on failure
     */
    public function confirmDelete()
    {
        if ($this->roomToDelete) {
            try {
                $room = RoomModel::findOrFail($this->roomToDelete);
                $roomName = $room->name;
                $suite->delete();
                
                $this->roomToDelete = null;
                
            // Show success popup
            $this->showSuccessPopup = true;
            $this->successMessage = "Room '{$roomName}' has been deleted successfully!";
                
            } catch (\Exception $e) {
                $this->roomToDelete = null;
                
                session()->flash('message', 'Error deleting room: ' . $e->getMessage());
                session()->flash('message_type', 'error');
            }
        }
    }
   
    // Filter methods
    /**
     * Toggle the filter dropdown visibility
     * Shows or hides the advanced filter options
     * Called when user clicks the filter button
     */
    public function toggleFilterDropdown()
    {
        $this->showFilterDropdown = !$this->showFilterDropdown;
    }
    
    /**
     * Apply the selected filters to the room list
     * Closes the filter dropdown and triggers re-rendering with filters
     * The actual filtering logic is handled in the render() method
     */
    public function applyFilters()
    {
        $this->showFilterDropdown = false;
        // The filtering will be handled in the render method
    }
    
    /**
     * Clear all applied filters and reset to default view
     * Removes all filter selections and shows all rooms
     * Called when user clicks "Clear Filters" button
     */
    public function clearFilters()
    {
        $this->filterSpecies = '';
        $this->filterRoomType = '';
        $this->filterBy = '';
    }
    
    // Save suite functionality
    /**
     * Save a new room to the database
     * Validates form data, creates the room record, and shows success message
     * Uses event-driven approach to handle view switching with delay
     */
    public function saveRoom()
    {

        $this->validate(\App\Rules\RoomRules::rules($this->room_id));
        
        
        try {
            // Handle image uploads
                
            $room = RoomModel::create([
                'name' => $this->name,
                'room_type_id' => $this->room_type_id,
                'cctv_stream' => $this->cctv_stream,
                'status' => $this->status,
            ]);
            
            // Show success popup first
            //$this->showSuccessPopup = true;
            //$this->successMessage = 'Room has been successfully created!';
            session()->flash('success', 'Room has been successfully created!');
            $this->closeForm();
            // Use JavaScript to delay the view switch
            $this->dispatch('room-created');
            
        } catch (\Exception $e) {
            $this->addError('save', 'Error saving room: ' . $e->getMessage());
        }
    }
    
    /**
     * Update an existing room in the database
     * Validates form data, updates the room record, and shows success message
     * @throws \Exception if room is not found or update fails
     */
    public function updateRoom()
    {
        $this->validate(\App\Rules\RoomRules::rules($this->room_id));
        
        try {
            $room = RoomModel::findOrFail($this->room_id);
            
            // Handle image uploads
            $room->update([
                'name' => $this->name,
                'room_type_id' => $this->room_type_id,
                'cctv_stream' => $this->cctv_stream,
                'status' => $this->status,
                'updated_by' => auth()->id(),
            ]);
            
            // Update room record
            // Don't use json_encode here - the model's $casts will handle it automatically
            $room->update([
                'name' => $this->name,
                'room_type_id' => $this->room_type_id,
                'cctv_stream' => $this->cctv_stream,
                'status' => $this->status,
                'updated_by' => auth()->id(),
            ]);
            
            // Show success popup
            //$this->showSuccessPopup = true;
            //$this->successMessage = 'Room has been successfully updated!';
            session()->flash('success', 'Room has been successfully updated!');
            $this->closeForm();
            
        } catch (\Exception $e) {
            \Log::error('Error updating room: ' . $e->getMessage());
            $this->addError('update', 'Error updating room: ' . $e->getMessage());
        }
    }
    /**
     * Render the component view with filtered and paginated room data
     * Applies search filters, room type filters, and pagination
     * Returns the main room management view with data
     * @return \Illuminate\View\View
     */
     public function render()
    {
        $roomsQuery = RoomModel::with(['roomType']);
        $roomTypes = RoomTypeModel::orderBy('name')->get();
        if ($this->listType === 'rooms') {
           // $roomsQuery->where('status', 'active');
        } // 'all' shows all types

        // Apply search filter (case-insensitive)
        if (!empty($this->search)) {
            $searchTerm = strtolower($this->search);
            $roomsQuery->where(function($query) use ($searchTerm) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                      ->orWhereHas('roomType', function($q) use ($searchTerm) {
                          $q->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%']);
                      });
            });
        }

      
        // Apply room type filter
        if (!empty($this->filterRoomType)) {
            $roomsQuery->whereHas('roomType', function($q) {
                $q->where('id', $this->filterRoomType);
            });
        }

        $rooms = $roomsQuery->orderBy('created_at', 'desc')->paginate($this->perPage);
        return view('livewire.backend.room', [
            'rooms' => $rooms,
            'roomTypes' => $roomTypes,
        ])->layout('layouts.backend.index');
    }
}