<?php

namespace App\Livewire\Backend\Room;

use Livewire\Component;
use App\Models\Room\RoomWeekendModel;
use Illuminate\Support\Facades\Auth;
use App\Services\Backend\Room\RoomSettingService;

/**
 * @OA\Tag(
 *     name="Room Weekend",
 *     description="room-weekend-specific APIs"
 * )
 * 
 */
class RoomWeekend extends Component
{
    protected $roomSettingService;

    public $heading = 'Room Weekend', $popUp = false;

    public $title, $description, $weekend_price_variation, $editId = 0, $deleteId;

    public function boot(RoomSettingService $roomSettingService)
    {
        $this->roomSettingService = $roomSettingService;
    }

    public function mount()
    {
        \session(['submenu' => 'room-weekend']);
        
        // Load existing configuration if exists
        $existingRoomWeekend = RoomWeekendModel::first();
        if ($existingRoomWeekend) {
            $this->editId = $existingRoomWeekend->id;
            $this->title = $existingRoomWeekend->title;
            $this->description = $existingRoomWeekend->description;
            $this->weekend_price_variation = $existingRoomWeekend->weekend_price_variation;
        }
    }

    public function render()
    {
        try{
            $result = $this->roomSettingService->getRoomWeekend();
            $data = [];
            if ($result['status'] === 'success') {
                // Get room weekend records
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.room.room-weekend', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.room.room-weekend');
    }

    protected function messages()
    {
        return \App\Rules\RoomWeekendRules::messages();
    }


    public function save()
    {
        // No need to transform percentages - they should be numeric
        $this->validate(\App\Rules\RoomWeekendRules::rules($this->editId));

        try {
            $data = [
                'title' => $this->title,
                'description' => $this->description,
                'weekend_price_variation' => $this->weekend_price_variation,
            ];
            
            $data['updated_by'] = Auth::id();
            
            // Check if config already exists (singleton pattern)
            $existingRoomWeekend = RoomWeekendModel::first();
            
            if ($existingRoomWeekend) {
                // Update existing configuration
                $this->editId = $existingRoomWeekend->id;
            } else {
                // First time creating configuration
                $data['created_by'] = Auth::id();
            }

            $result = $this->roomSettingService->saveRoomWeekend($this->editId, $data);

            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
                
                // Reload the configuration
                $this->mount();
            } else {
                // Handle validation errors
                if (isset($result['errors'])) {
                    foreach ($result['errors'] as $field => $messages) {
                        foreach ($messages as $message) {
                            $this->addError($field, $message);
                        }
                    }
                } else {
                    session()->flash('error', $result['message']);
                }
            }
            
        } catch (Exception $e) {
            dd($e);
        }
    }

    // No edit, delete, or reset methods needed for singleton configuration
    // The form always shows and updates the single configuration record
}
