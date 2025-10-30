<?php

namespace App\Livewire\Backend\Room;

use Livewire\Component;
use Exception;
use Auth;
use App\Models\Room\RoomPriceOptionModel;
use App\Models\Size as SizeModel;
use App\Models\Room\RoomTypeModel;

class RoomPriceOption extends Component
{

    public $label, $no_of_days, $price, $pet_size_id, $petSizes;
    public $room_type_id, $roomTypes;
    public $roomPriceOption;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $page_title = 'Room Settings';

    protected $rules = [
        'room_type_id' => 'required|integer',
        'label' => 'required|string',
        'no_of_days' => 'required|integer',
        'price' => 'required|numeric',
       // 'pet_size_id' => 'required|integer',
    ];

    public function mount()
    {
        \session(['submenu' => 'room-price-options']);
        $this->petSizes = SizeModel::select('id as value','name as option')->get()->toArray();
        $this->roomTypes = RoomTypeModel::select('id as value','name as option')->orderBy('name')->get()->toArray();
    }

    public function render()
    {
        try {
            $data = [];
            $query = RoomPriceOptionModel::orderby('id','asc');
            \Log::info($this->room_type_id);
            if (!empty($this->room_type_id)) {
                $query->where('room_type_id', (int) $this->room_type_id);
                $data = $query->get();
            }
            
        } catch (Exception $e) {
            \Log::error('RoomPriceOption render failed', [
                'message' => $e->getMessage(),
            ]);
            $data = collect([]);
        }
        return view('livewire.backend.room.room-price-option', ['data' => $data]);
    }

    public function index()
    {
        return view('backend.room.room-price-option');
    }

    public function save()
    {
        
        $this->validate();

        try {
            $data = $this->only(['room_type_id', 'label', 'no_of_days', 'price', 'pet_size_id']);
            $data['created_by'] = Auth::user()->id;


            if ($this->editId) {
                RoomPriceOptionModel::find($this->editId)->update($data);
            } else {
                RoomPriceOptionModel::create($data);
            }

            $this->resetFields();
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function edit($id)
    {
        try{
            $roomPriceOption = RoomPriceOptionModel::findOrFail($id);
            $this->editId = $id;
            $this->room_type_id = $roomPriceOption->room_type_id;
            $this->label = $roomPriceOption->label;
            $this->no_of_days = $roomPriceOption->no_of_days;
            $this->price = $roomPriceOption->price;
            $this->pet_size_id = $roomPriceOption->pet_size_id;
        } catch (Exception $e) {
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
            RoomPriceOptionModel::findOrFail($this->deleteId)->delete();
            $this->reset('deleteId', 'popUp');
            session()->flash('success', 'Room price option deleted successfully.');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function resetFields()
    {
        $this->reset(['label', 'no_of_days', 'price', 'pet_size_id', 'editId']);
    }
    public function changedRoomType()
    {
        $this->room_type_id = is_numeric($this->room_type_id) ? (int) $this->room_type_id : null;
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
    /*public function updatedRoomTypeId($value)
    {
        \Log::info("value: $value");
        $this->room_type_id = is_numeric($value) ? (int) $value : null;
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }*/
}