<?php

namespace App\Livewire\Backend\Room;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Room\RoomBookingModel;
use App\Models\Room\RoomTypeModel;
use App\Models\Room\RoomPriceOptionModel;
use App\Models\Room\RoomModel;
use App\Models\Species;
use App\Models\User;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Order;
use App\Models\Size;
use App\Rules\RoomBookingRules;
use App\Services\TaxService;
use Illuminate\Support\Facades\DB;

class RoomBooking extends Component
{
    use WithPagination, WithFileUploads;

    // UI State
    public $showForm = false;
    public $showList = true;
    public $showModal = false;
    public $editingRoomBooking = null;
    public $search = '';
    public $filterBy = '';
    public $showFilterDropdown = false;
    public $showDeleteModal = false;
    public $roomBookingToDelete = null;
    public $showSuccessPopup = false;
    public $successMessage = '';

     // Form Properties 
    public $room_booking_id = null;
    public $customer_id = null;
    public $room_id = null;
    public $room_type_id = null;
    public $room_price = null;
    public $room_price_label = null;
    public $pets_reserved = null;
    public $species_id = null;
    public $pet_quantity = null;
    public $service_addons = null;
    public $is_peak_season = false;
    public $is_off_day = false;
    public $is_weekend = false;
    public $check_in_date = null;
    public $check_out_date = null;
    public $no_of_days = null;
    public $service_charge = null;
    public $total_price = null;
    public $payment_status = null;
    public $booking_status = null;
    public $payment_method = null;
    public $payment_reference = null;

    // Removed price_options from this form; pricing managed elsewhere

    // Deletion-related properties
    public $deleteType = 'room_booking';
    public $selectedRoomBookingToDelete = null;
    public $deletionDependencies = [];
    public $canDelete = true;
    public $deleteWarnings = [];
    public $isLoadingDependencies = false;

    // List view filter: products | addons | all
    public $listType = 'room_bookings';

    // Pagination
    public $perPage = 6;

    // Data for dropdowns
    public $customers = [];
    public $species = [];
    public $pets = [];
    public $serviceAddons = [];
    public $roomTypes = [];
    public $roomPriceOptions = [];
    public $selectedPetIds = [];
    public $selectedServiceAddonIds = [];
    public $selectedRoomPriceOptionId = null;
    public $selectedPetId = null;
    public $selectedServiceAddonId = null;

    // Original values (for update mode comparison)
    public $original_room_booking_id = null;
    public $original_customer_id = null;
    public $original_room_id = null;
    public $original_room_type_id = null;
    public $original_room_price = null;
    public $original_room_price_label = null;
    public $original_pets_reserved = null;
    public $original_species_id = null;
    public $original_pet_quantity = null;
    public $original_service_addons = null;
   
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
        // Load customers
        $this->customers = User::role('customer')
            ->whereNotNull('phone_verified_at')
            ->whereNotNull('email_verified_at')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'name', 'email']);
        
        // Load species
        $this->species = Species::orderBy('name')->get(['id', 'name']);
        
        // Load service addons (services where service_addon = true)
        $this->serviceAddons = Service::where('service_addon', true)
            ->whereNull('parent_id')
            ->orderBy('title')
            ->get(['id', 'title', 'price']);
        
        // Load room types
        $this->roomTypes = RoomTypeModel::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Reset the form for the component
     * @return void
     */
    public function resetForm()
    {
        $this->editingRoomBooking = null;
        $this->customer_id = null;
        $this->room_id = null;
        $this->pet_quantity = 0;
        $this->species_id = 1;
        $this->room_type_id = null;
        $this->room_price = null;
        $this->room_price_label = null;
        $this->pets_reserved = null;
        $this->service_addons = null;
        $this->pets = [];
        $this->roomPriceOptions = [];
        $this->selectedPetIds = [];
        $this->selectedServiceAddonIds = [];
        $this->selectedRoomPriceOptionId = null;
        $this->resetValidation();
    }

    /**
     * Set the default values for the component
     * @return void
     */
    public function setDefaltValues(){
        $this->customer_id = null;
        $this->room_id = null;
        $this->species_id = null;
        $this->pet_quantity = 0;
        $this->service_addons = [];
        $this->selectedPetIds = [];
        $this->selectedServiceAddonIds = [];
        $this->is_peak_season = false;
        $this->is_off_day = false;
        $this->is_weekend = false;
        $this->check_in_date = null;
        $this->check_out_date = null;
        $this->no_of_days = 1;
        $this->room_type_id = null;
        $this->selectedRoomPriceOptionId = null;
        $this->room_price = null;
        $this->room_price_label = null;
        $this->service_charge = 0;
        $this->total_price = 0;
        $this->payment_status = 'pending';
        $this->booking_status = 'pending';
        $this->payment_method = null;
        $this->payment_reference = null;
        $this->pets = [];
        $this->roomPriceOptions = [];
    }

    /**
     * Handle customer selection change - load pets for selected customer
     */
    public function updatedCustomerId($value)
    {
        if ($value) {
            $this->pets = Pet::where('user_id', $value)
                ->orderBy('name')
                ->get(['id', 'name', 'species_id']);
        } else {
            $this->pets = [];
            $this->selectedPetIds = [];
        }
    }

    /**
     * Handle room type selection change - load price options for no_of_days = 1
     */
    public function updatedRoomTypeId($value)
    {
        if ($value) {
            $this->roomPriceOptions = RoomPriceOptionModel::where('room_type_id', $value)
                ->where('no_of_days', 1)
                ->orderBy('label')
                ->get(['id', 'label', 'price', 'no_of_days']);
        } else {
            $this->roomPriceOptions = [];
            $this->selectedRoomPriceOptionId = null;
            $this->room_price = null;
            $this->room_price_label = null;
        }
    }

    /**
     * Handle room price option selection change
     */
    public function updatedSelectedRoomPriceOptionId($value)
    {
        if ($value) {
            $priceOption = RoomPriceOptionModel::find($value);
            if ($priceOption) {
                $this->room_price = $priceOption->price;
                $this->room_price_label = $priceOption->label;
            }
        } else {
            $this->room_price = null;
            $this->room_price_label = null;
        }
    }

    /**
     * Add selected pet to pets_reserved
     */
    public function addPet($petId)
    {
        if ($petId && !in_array($petId, $this->selectedPetIds)) {
            $this->selectedPetIds[] = $petId;
            $this->selectedPetId = null; // Reset dropdown
        }
    }

    /**
     * Remove pet from pets_reserved
     */
    public function removePet($index)
    {
        if (isset($this->selectedPetIds[$index])) {
            unset($this->selectedPetIds[$index]);
            $this->selectedPetIds = array_values($this->selectedPetIds);
        }
    }

    /**
     * Add selected service addon
     */
    public function addServiceAddon($addonId)
    {
        if ($addonId && !in_array($addonId, $this->selectedServiceAddonIds)) {
            $this->selectedServiceAddonIds[] = $addonId;
            $this->selectedServiceAddonId = null; // Reset dropdown
        }
    }

    /**
     * Remove service addon
     */
    public function removeServiceAddon($index)
    {
        if (isset($this->selectedServiceAddonIds[$index])) {
            unset($this->selectedServiceAddonIds[$index]);
            $this->selectedServiceAddonIds = array_values($this->selectedServiceAddonIds);
        }
    }

    /**
     * Create a new room booking
     * @return void
     */
     public function createRoomBooking()
    {
        $this->resetForm();
        $this->setDefaltValues();
        $this->showForm = true;
        $this->showList = false;
    }
    
    /**
     * Edit a room booking
     * @param int $roomBookingId
     * @return void
     */
    public function editRoomBooking($roomBookingId)
    {
        $this->resetForm();
        $roomBooking = RoomBookingModel::findOrFail($roomBookingId);
        $this->editingRoomBooking = $roomBooking;
        $this->room_booking_id = $roomBooking->id;
        
        // Load basic data
        $this->customer_id = $roomBooking->customer_id;
        $this->room_type_id = $roomBooking->room_type_id;
        $this->species_id = $roomBooking->species_id;
        $this->room_price = $roomBooking->room_price;
        $this->room_price_label = $roomBooking->room_price_label;
        $this->pet_quantity = $roomBooking->pet_quantity ?? 0;
        $this->check_in_date = $roomBooking->check_in_date;
        $this->check_out_date = $roomBooking->check_out_date;
        $this->no_of_days = $roomBooking->no_of_days ?? 1;
        $this->service_charge = $roomBooking->service_charge ?? 0;
        $this->total_price = $roomBooking->total_price ?? 0;
        $this->payment_status = $roomBooking->payment_status;
        $this->booking_status = $roomBooking->booking_status;
        $this->payment_method = $roomBooking->payment_method;
        $this->payment_reference = $roomBooking->payment_reference;
        $this->is_peak_season = $roomBooking->is_peak_season ?? false;
        $this->is_off_day = $roomBooking->is_off_day ?? false;
        $this->is_weekend = $roomBooking->is_weekend ?? false;
        
        // Load pets for selected customer
        if ($this->customer_id) {
            $this->updatedCustomerId($this->customer_id);
            // Load selected pet IDs from pets_reserved
            // pets_reserved format: [{"pet_id":2,"pet_name":"Pet2","pet_size_id":1,"pet_size_name":"S"}]
            $this->selectedPetIds = [];
            if (is_array($roomBooking->pets_reserved)) {
                foreach ($roomBooking->pets_reserved as $petData) {
                    if (is_array($petData) && isset($petData['pet_id'])) {
                        // New format: array of objects with pet_id
                        $this->selectedPetIds[] = (int)$petData['pet_id'];
                    } elseif (is_numeric($petData)) {
                        // Legacy format: array of pet IDs
                        $this->selectedPetIds[] = (int)$petData;
                    }
                }
            } elseif (is_string($roomBooking->pets_reserved)) {
                $decoded = json_decode($roomBooking->pets_reserved, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $petData) {
                        if (is_array($petData) && isset($petData['pet_id'])) {
                            // New format: array of objects with pet_id
                            $this->selectedPetIds[] = (int)$petData['pet_id'];
                        } elseif (is_numeric($petData)) {
                            // Legacy format: array of pet IDs
                            $this->selectedPetIds[] = (int)$petData;
                        }
                    }
                }
            }
        }
        
        // Load room price options for selected room type
        if ($this->room_type_id) {
            $this->updatedRoomTypeId($this->room_type_id);
            // Find matching price option
            if ($this->room_price && $this->room_price_label) {
                $priceOption = RoomPriceOptionModel::where('room_type_id', $this->room_type_id)
                    ->where('price', $this->room_price)
                    ->where('label', $this->room_price_label)
                    ->where('no_of_days', 1)
                    ->first();
                if ($priceOption) {
                    $this->selectedRoomPriceOptionId = $priceOption->id;
                }
            }
        }
        
        // Load selected service addon IDs
        if (is_array($roomBooking->service_addons)) {
            $this->selectedServiceAddonIds = array_column($roomBooking->service_addons, 'id');
        } elseif (is_string($roomBooking->service_addons)) {
            $decoded = json_decode($roomBooking->service_addons, true);
            if (is_array($decoded)) {
                $this->selectedServiceAddonIds = array_column($decoded, 'id');
            }
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
      public function initiateDelete($roomBookingId)
    {
        $this->roomBookingToDelete = RoomBookingModel::find($roomBookingId);
        $this->checkDeletionDependencies($roomBookingId);
        $this->showDeleteModal = true;
    }

    /**
     * Check the deletion dependencies for the component
     * @param int $roomBookingId
     * @return void
     */
    public function checkDeletionDependencies($roomBookingId)
    {
        $this->deletionDependencies = [];
        $this->deleteWarnings = [];
        $this->canDelete = true;
        $this->isLoadingDependencies = true;
        // Check if this room type is being used by any rooms
        $roomsUsingThisType = \App\Models\RoomModel::where('room_type_id', $roomBookingId)->count();
        
        if ($roomsUsingThisType > 0) {
            $this->canDelete = false;
            $this->deleteWarnings = "Cannot delete room booking '{$this->roomBookingToDelete->id}' because it is being used by {$roomsUsingThisType} room(s). Please remove or reassign the rooms first.";
        }
        $this->isLoadingDependencies = false;
    }
    // Delete functionality
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
        $this->roomBookingToDelete = null;
    }

    /**
     * Confirm the delete for the component
     * @return void
     */
    public function confirmDelete()
    {
       // if ($this->roomTypeToDelete) {
            try {
                
                $roomBooking = RoomBookingModel::findOrFail($this->roomBookingToDelete->id);
                
                $roomBooking->delete();
               
                $this->cancelDelete();
            // Show success popup
                $this->showSuccessPopup = true;
                $this->successMessage = "Room Booking '{$roomBooking->id}' has been deleted successfully!";
                session()->flash('success', $this->successMessage);
            } catch (\Exception $e) {
                $this->cancelDelete();
                $this->showDeleteModal = false;
                session()->flash('error', 'Error deleting room booking: ' . $e->getMessage());
            }
    }
    
    // Dynamic form management methods - removed duplicates, using methods defined above
    
    /**
     * Add a new price option to the component
     * @return void
     */
    
    // Image management methods
    /**
     * Update the images for the component
     * @return void
     */
    public function updatedPetsReserved()
    {
        // Only update previews for new pets reserved, don't touch existing pets reserved
        $this->petsReservedPreviews = [];
        if ($this->pets_reserved) {
            foreach ($this->pets_reserved as $pet) {
                $this->petsReservedPreviews[] = $pet->temporaryUrl();
            }
        }
        // Auto-set primary flag for new pets reserved
        $this->updateNewPetsReservedPrimaryFlags();
        
    }
    
    /**
     * Update primary flags for new pets reserved based on business rules
     * @return void
     */
    private function updateNewPetsReservedPrimaryFlags()
    {
        $newPetsReservedCount = count($this->pets_reserved);
        $existingPetsReservedCount = count($this->existingPetsReserved);
        
        // If there's only one new pet reserved and no existing pets reserved, set it as primary
        if ($newPetsReservedCount == 1 && $existingPetsReservedCount == 0) {
            $this->newPetsReservedPrimary = [true]; // Set first (and only) new pet reserved as primary
        } elseif ($newPetsReservedCount > 1 && $existingPetsReservedCount == 0) {
            // If multiple new pets reserved and no existing pets reserved, set first one as primary
            $this->newPetsReservedPrimary = array_fill(0, $newPetsReservedCount, false);
            $this->newPetsReservedPrimary[0] = true; // Set first pet reserved as primary
        } elseif ($newPetsReservedCount > 0 && $existingPetsReservedCount > 0) {
            // If there are existing pets reserved, don't auto-set primary for new pets reserved
            $this->newPetsReservedPrimary = array_fill(0, $newPetsReservedCount, false);
        }
    }
    
    /**
     * Remove an image from the component
     * @param int $index
     * @return void
     */
    public function removePetsReserved($index)
    {
        if (isset($this->pets_reserved[$index])) {
            unset($this->pets_reserved[$index]);
            $this->pets_reserved = array_values($this->pets_reserved);
            
            // Update previews
            $this->petsReservedPreviews = [];
            if ($this->pets_reserved) {
                foreach ($this->pets_reserved as $pet) {
                    $this->petsReservedPreviews[] = $pet->temporaryUrl();
                }
            }
            
            // Remove corresponding primary flag
            if (isset($this->newPetsReservedPrimary[$index])) {
                unset($this->newPetsReservedPrimary[$index]);
                $this->newPetsReservedPrimary = array_values($this->newPetsReservedPrimary);
            }
            
            // Update primary flags after removal
            $this->updateNewPetsReservedPrimaryFlags();
        }
    }
    
    /**
     * Remove an existing pet reserved from the component
     * @param int $index
     * @return void
     */
    public function removeExistingPetsReserved($index)
    {
        if (isset($this->existingPetsReserved[$index])) {
            // Remove from existing pets reserved array
            unset($this->existingPetsReserved[$index]);
            $this->existingPetsReserved = array_values($this->existingPetsReserved);
            
            // Remove corresponding primary flag
            if (isset($this->existingPetsReservedPrimary[$index])) {
                unset($this->existingPetsReservedPrimary[$index]);
                $this->existingPetsReservedPrimary = array_values($this->existingPetsReservedPrimary);
            }
            
            // Update primary flags after removal - if no existing pets reserved left, auto-set primary for new pets reserved
            $this->updateNewPetsReservedPrimaryFlags();
        }
    }
    
    // Service addon management methods
    /**
     * Update the service addons for the component
     * @param mixed $value
     * @param string $key
     * @return void
     */
    /**
     * Handle individual service addon updates for service addons
     * @param mixed $value
     * @param string $key
     * @return void
     */
    public function updatedServiceAddons($value = null, $key = null)
    {
        // If key is provided, handle individual field updates
        if ($key !== null) {
            // Only handle document updates, not content updates
            if (str_contains($key, '.service_addon')) {
                // Extract the index from the key (e.g., "0.document" -> 0)
                $index = explode('.', $key)[0];
                
                if ($value) {
                    // Generate preview URL for the uploaded document
                    $this->serviceAddonsPreviews[$index] = $value->temporaryUrl();
                } else {
                    // Clear preview if document is removed
                    unset($this->serviceAddonsPreviews[$index]);
                }
            }
        } else {
            // Handle array updates (legacy behavior)
            $this->serviceAddonsPreviews = [];
            foreach ($this->service_addons as $index => $service_addon) {
                if (isset($term['document']) && $term['document']) {
                    $this->serviceAddonsPreviews[$index] = $service_addon['service_addon']->temporaryUrl();
                } else {
                    $this->serviceAddonsPreviews[$index] = null;
                }
            }
        }
    }

    /**
     * Alternative approach: Handle service addon updates via updated hook
     * @param mixed $value
     * @param string $key
     * @return void
     */
    public function updated($property, $value)
    {
        // Handle document updates
        if (str_contains($property, 'service_addons') && str_contains($property, 'service_addon')) {
            $index = explode('.', $property)[1]; // Get index from property name
            
            if ($value) {
                $this->serviceAddonsPreviews[$index] = $value->temporaryUrl();
            } else {
                unset($this->serviceAddonsPreviews[$index]);
            }
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
            $serviceAddons = ServiceAddonModel::where('product_type', 'addon')->get();
            $selectedAddon = $serviceAddons->firstWhere('id', $value);
            $this->selected_addon_id = null;
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
     * Find an available room for the given room type and date range
     * @param int $roomTypeId
     * @param string $checkInDate
     * @param string $checkOutDate
     * @param int|null $excludeBookingId Optional booking ID to exclude from check (for updates)
     * @return RoomModel|null
     */
    private function findAvailableRoom(int $roomTypeId, string $checkInDate, string $checkOutDate, ?int $excludeBookingId = null): ?RoomModel
    {
        // Get all available rooms for this room type
        $availableRooms = RoomModel::where('room_type_id', $roomTypeId)
            ->where('status', RoomModel::STATUS_AVAILABLE)
            ->get();
        
        if ($availableRooms->isEmpty()) {
            return null;
        }
        
        // Check each room for date conflicts
        foreach ($availableRooms as $room) {
            // Check for overlapping bookings (excluding cancelled bookings and optionally the current booking being updated)
            // Two date ranges overlap if: (start1 <= end2) AND (end1 >= start2)
            $hasConflict = RoomBookingModel::where('room_id', $room->id)
                ->where('booking_status', '!=', 'cancelled') // Exclude cancelled bookings
                ->where(function ($query) use ($checkInDate, $checkOutDate) {
                    // Check for date overlap: existing booking overlaps with new booking dates
                    // This covers all overlap scenarios:
                    // 1. Existing check-in is between new dates
                    // 2. Existing check-out is between new dates
                    // 3. Existing booking completely encompasses new booking
                    // 4. New booking completely encompasses existing booking
                    $query->where(function ($q) use ($checkInDate, $checkOutDate) {
                        $q->whereBetween('check_in_date', [$checkInDate, $checkOutDate])
                          ->orWhereBetween('check_out_date', [$checkInDate, $checkOutDate])
                          ->orWhere(function ($qq) use ($checkInDate, $checkOutDate) {
                              // Existing booking completely encompasses new booking
                              $qq->where('check_in_date', '<=', $checkInDate)
                                 ->where('check_out_date', '>=', $checkOutDate);
                          })
                          ->orWhere(function ($qq) use ($checkInDate, $checkOutDate) {
                              // New booking completely encompasses existing booking
                              $qq->where('check_in_date', '>=', $checkInDate)
                                 ->where('check_out_date', '<=', $checkOutDate);
                          });
                    });
                })
                ->when($excludeBookingId, function ($query) use ($excludeBookingId) {
                    // Exclude the current booking when updating
                    $query->where('id', '!=', $excludeBookingId);
                })
                ->exists();
            
            // If no conflict, this room is available
            if (!$hasConflict) {
                return $room;
            }
        }
        
        // No available room found
        return null;
    }

    /**
     * Create an order for a room booking
     * @param array $processedServiceAddons
     * @param float $totalPrice
     * @return Order
     */
    private function createOrderForRoomBooking(array $processedServiceAddons, float $totalPrice): Order
    {
        // Calculate subtotal: room_price + sum of service addon prices
        $subtotal = (float)($this->room_price ?? 0);
        foreach ($processedServiceAddons as $addon) {
            // Price is stored as string in format "10.00", convert to float
            $subtotal += (float)($addon['price'] ?? 0);
        }
        
        // Calculate tax
        $taxService = app(TaxService::class);
        $taxResult = $taxService->calculateTax($subtotal);
        $taxAmount = (float)($taxResult['amount'] ?? 0);
        $taxRate = (float)($taxResult['rate'] ?? 0);
        
        // Calculate total (use provided total_price if available, otherwise calculate)
        $calculatedTotal = $subtotal + $taxAmount;
        $orderTotal = ($totalPrice > 0) ? $totalPrice : $calculatedTotal;
        
        // Generate order number
        $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Determine order status based on payment_status
        $orderStatus = ($this->payment_status === 'paid') ? 'confirmed' : 'pending';
        
        // Create order
        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $this->customer_id,
            'shipping_address_id' => null,
            'billing_address_id' => null,
            'status' => $orderStatus,
            'payment_failed_attempts' => 0,
            'shipping_method' => null,
            'tracking_number' => null,
            'estimated_delivery' => null,
            'subtotal' => $subtotal,
            'coupon_discount_amount' => 0,
            'strikethrough_discount_amount' => 0,
            'tax_amount' => $taxAmount,
            'applied_tax_rate' => $taxRate,
            'shipping_amount' => 0,
            'total_amount' => $orderTotal,
        ]);
        
        return $order;
    }

    /**
     * Save the room booking for the component
     * @return void
     */
    public function saveRoomBooking()
    {
        $this->validate(RoomBookingRules::rules($this->room_booking_id));
        
        try {
            DB::beginTransaction();
            
            // Check if dates are available and find an available room
            $availableRoom = $this->findAvailableRoom(
                $this->room_type_id,
                $this->check_in_date,
                $this->check_out_date
            );
            
            if (!$availableRoom) {
                DB::rollBack();
                $this->addError('check_in_date', 'No rooms available for the selected dates and room type. Please choose different dates.');
                return;
            }
            
            // Process selected pets - Build in required format
            $petsReserved = [];
            if (!empty($this->selectedPetIds)) {
                $pets = Pet::whereIn('id', $this->selectedPetIds)->get();
                // Load all sizes in one query for better performance
                $sizeIds = $pets->pluck('pet_size_id')->filter()->unique()->values()->all();
                $sizes = !empty($sizeIds) ? Size::whereIn('id', $sizeIds)->get()->keyBy('id') : collect();
                
                foreach ($pets as $pet) {
                    $sizeName = null;
                    if ($pet->pet_size_id && $sizes->has($pet->pet_size_id)) {
                        $sizeName = $sizes->get($pet->pet_size_id)->name;
                    }
                    $petsReserved[] = [
                        'pet_id' => $pet->id,
                        'pet_name' => $pet->name,
                        'pet_size_id' => $pet->pet_size_id ?? null,
                        'pet_size_name' => $sizeName
                    ];
                }
            }
            
            // Process selected service addons - Build in required format
            $processedServiceAddons = [];
            foreach ($this->selectedServiceAddonIds as $addonId) {
                $addon = Service::find($addonId);
                if ($addon) {
                    $processedServiceAddons[] = [
                        'id' => $addon->id,
                        'name' => $addon->title, // Use title as name
                        'price' => number_format((float)($addon->price ?? 0), 2, '.', ''),
                        'qty' => 1
                    ];
                }
            }
            
            // Create order for the room booking
            $order = $this->createOrderForRoomBooking($processedServiceAddons, (float)($this->total_price ?? 0));
            
            // Create room booking record
            $roomBooking = RoomBookingModel::create([
                'room_id' => $availableRoom->id, // Use the found available room
                'room_type_id' => $this->room_type_id,
                'customer_id' => $this->customer_id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'room_price' => $this->room_price,
                'room_price_label' => $this->room_price_label,
                'pets_reserved' => $petsReserved,
                'species_id' => $this->species_id,
                'pet_quantity' => count($this->selectedPetIds),
                'service_addons' => $processedServiceAddons,
                'is_peak_season' => $this->is_peak_season,
                'is_off_day' => $this->is_off_day,
                'is_weekend' => $this->is_weekend,
                'check_in_date' => $this->check_in_date,
                'check_out_date' => $this->check_out_date,
                'no_of_days' => $this->no_of_days ?? 1,
                'service_charge' => $this->service_charge ?? 0,
                'total_price' => $this->total_price ?? 0,
                'payment_status' => $this->payment_status,
                'booking_status' => $this->booking_status,
                'payment_method' => $this->payment_method,
                'payment_reference' => $order->order_number,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            session()->flash('success', 'Room Booking has been successfully created!');
            $this->closeForm();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving room booking: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('save', 'Error saving room booking: ' . $e->getMessage());
        }
    }
    /**
     * Update the room booking for the component
     * @return void
     */
    public function updateRoomBooking()
    {
        $this->validate(\App\Rules\RoomBookingRules::rules($this->room_booking_id));
        
        try {
            DB::beginTransaction();
            
            $roomBooking = RoomBookingModel::findOrFail($this->room_booking_id);
            
            // Check if dates are available and find an available room
            // If room_type_id or dates changed, check availability
            $needToCheckAvailability = ($roomBooking->room_type_id != $this->room_type_id) ||
                                      ($roomBooking->check_in_date != $this->check_in_date) ||
                                      ($roomBooking->check_out_date != $this->check_out_date);
            
            $availableRoom = null;
            if ($needToCheckAvailability) {
                $availableRoom = $this->findAvailableRoom(
                    $this->room_type_id,
                    $this->check_in_date,
                    $this->check_out_date,
                    $this->room_booking_id // Exclude current booking from conflict check
                );
                
                if (!$availableRoom) {
                    DB::rollBack();
                    $this->addError('check_in_date', 'No rooms available for the selected dates and room type. Please choose different dates.');
                    return;
                }
            } else {
                // Use existing room if dates and room type haven't changed
                $availableRoom = RoomModel::find($roomBooking->room_id);
                if (!$availableRoom || $availableRoom->room_type_id != $this->room_type_id) {
                    // If room doesn't exist or room type changed, find a new one
                    $availableRoom = $this->findAvailableRoom(
                        $this->room_type_id,
                        $this->check_in_date,
                        $this->check_out_date,
                        $this->room_booking_id
                    );
                    
                    if (!$availableRoom) {
                        DB::rollBack();
                        $this->addError('check_in_date', 'No rooms available for the selected dates and room type. Please choose different dates.');
                        return;
                    }
                }
            }
            
            // Process selected pets - Build in required format
            $petsReserved = [];
            if (!empty($this->selectedPetIds)) {
                $pets = Pet::whereIn('id', $this->selectedPetIds)->get();
                // Load all sizes in one query for better performance
                $sizeIds = $pets->pluck('pet_size_id')->filter()->unique()->values()->all();
                $sizes = !empty($sizeIds) ? Size::whereIn('id', $sizeIds)->get()->keyBy('id') : collect();
                
                foreach ($pets as $pet) {
                    $sizeName = null;
                    if ($pet->pet_size_id && $sizes->has($pet->pet_size_id)) {
                        $sizeName = $sizes->get($pet->pet_size_id)->name;
                    }
                    $petsReserved[] = [
                        'pet_id' => $pet->id,
                        'pet_name' => $pet->name,
                        'pet_size_id' => $pet->pet_size_id ?? null,
                        'pet_size_name' => $sizeName
                    ];
                }
            }
            
            // Process selected service addons - Build in required format
            $processedServiceAddons = [];
            foreach ($this->selectedServiceAddonIds as $addonId) {
                $addon = Service::find($addonId);
                if ($addon) {
                    $processedServiceAddons[] = [
                        'id' => $addon->id,
                        'name' => $addon->title, // Use title as name
                        'price' => number_format((float)($addon->price ?? 0), 2, '.', ''),
                        'qty' => 1
                    ];
                }
            }
            
            // Check if room booking already has an order
            $order = null;
            if ($roomBooking->order_id) {
                // Update existing order
                $order = Order::find($roomBooking->order_id);
            }
            
            if (!$order) {
                // Create new order if it doesn't exist
                $order = $this->createOrderForRoomBooking($processedServiceAddons, (float)($this->total_price ?? 0));
            } else {
                // Update existing order totals
                $subtotal = (float)($this->room_price ?? 0);
                foreach ($processedServiceAddons as $addon) {
                    // Price is stored as string in format "10.00", convert to float
                    $subtotal += (float)($addon['price'] ?? 0);
                }
                
                // Calculate tax
                $taxService = app(TaxService::class);
                $taxResult = $taxService->calculateTax($subtotal);
                $taxAmount = (float)($taxResult['amount'] ?? 0);
                $taxRate = (float)($taxResult['rate'] ?? 0);
                
                // Calculate total
                $calculatedTotal = $subtotal + $taxAmount;
                $orderTotal = ($this->total_price > 0) ? (float)$this->total_price : $calculatedTotal;
                
                // Determine order status based on payment_status
                $orderStatus = ($this->payment_status === 'paid') ? 'confirmed' : 'pending';
                
                $order->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'applied_tax_rate' => $taxRate,
                    'total_amount' => $orderTotal,
                    'status' => $orderStatus,
                ]);
            }
            
            // Update room booking
            $roomBooking->update([
                'room_id' => $availableRoom->id, // Use the found available room
                'room_type_id' => $this->room_type_id,
                'customer_id' => $this->customer_id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'room_price' => $this->room_price,
                'room_price_label' => $this->room_price_label,
                'pets_reserved' => $petsReserved,
                'species_id' => $this->species_id,
                'pet_quantity' => count($this->selectedPetIds),
                'service_addons' => $processedServiceAddons,
                'is_peak_season' => $this->is_peak_season,
                'is_off_day' => $this->is_off_day,
                'is_weekend' => $this->is_weekend,
                'check_in_date' => $this->check_in_date,
                'check_out_date' => $this->check_out_date,
                'no_of_days' => $this->no_of_days ?? 1,
                'service_charge' => $this->service_charge ?? 0,
                'total_price' => $this->total_price ?? 0,
                'payment_status' => $this->payment_status,
                'booking_status' => $this->booking_status,
                'payment_method' => $this->payment_method,
                'payment_reference' => $order->order_number,
                'updated_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            // Show success message using session flash
            session()->flash('success', 'Room Booking has been successfully updated!');
            $this->closeForm();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating room booking: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('update', 'Error updating room booking: ' . $e->getMessage());
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
        $roomBookingsQuery = RoomBookingModel::with(['room', 'roomType', 'customer', 'species']);
        $roomBookings = $roomBookingsQuery->orderBy('created_at', 'desc')->paginate($this->perPage);
        return view('livewire.backend.room.room-booking', [
            'roomBookings' => $roomBookings,
        ])->layout('layouts.backend.index');
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

    

}