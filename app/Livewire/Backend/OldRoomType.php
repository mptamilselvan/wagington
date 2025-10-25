<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\RoomTypeModel;
use Illuminate\Support\Facades\Auth;
use App\Services\RoomTypeService;

/**
 * @OA\Tag(
 *     name="Room Type",
 *     description="room-type-specific APIs"
 * )
 * 
 */
class OldRoomType extends Component
{
    protected $roomTypeService;

    public $title = 'Room Type Settings', $popUp = false;

    public $type, $editId = 0, $deleteId;

    public function boot(RoomTypeService $roomTypeService)
    {
        $this->roomTypeService = $roomTypeService;
    }

    public function mount()
    {
        \session(['submenu' => 'room-type']);
    }

    public function render()
    {
        try{
            $result = $this->roomTypeService->getRoomType();
            if ($result['status'] === 'success') {
                // Get room type records
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.room-type', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.room-type');
    }

    protected function messages()
    {
        return \App\Rules\RoomTypeRules::messages();
    }


    public function save()
    {
        $this->type = ucfirst(strtolower($this->type));
        // $this->validate(\App\Rules\SpeciesRules::rules($this->editId));
        $this->validate(\App\Rules\RoomTypeRules::rules($this->editId));

        try {
            // Map 'name' to 'type' for database column
            $data = [
                'type' => $this->type
            ];
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->roomTypeService->saveRoomType($this->editId, $data);

            

            if ($result['status'] === 'success') {
                // dd($result);
                // Store record ID for address creation
                session()->flash('success', $result['message']);
                
                $this->resetFields();
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

    public function edit($id)
    {
        try {
            $roomTypeData = RoomTypeModel::findOrFail($id);
            $this->editId = $id;
            // Map 'type' column to 'name' property
            $this->type = $roomTypeData->type;
        } catch (\Exception $e) {
            $e->getMessage();
        }
    }

    public function deletePopUp($id)
    {
        $this->deleteId = $id;
        $this->popUp = true;
    }

    public function delete()
    {
        try{
            $result = $this->roomTypeService->deleteRoomType($this->deleteId);
            
            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
            $this->reset('deleteId', 'popUp');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function resetFields()
    {
        $this->reset(['type', 'editId']);
    }
}
