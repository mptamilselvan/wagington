<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Auth;
use Illuminate\Support\Facades\Request;
use App\Models\SizeManagement as SizeManagementModel;
use App\Models\Size;
use Illuminate\Support\Facades\Storage;
use App\Services\SizeManagementService;

class SizeManagement extends Component
{

    protected $SizeManagementService;

    public $pet_id, $size_id, $name,$created_by,$updated_by, $customer_id;
    public $SizeManagement,$size,$firstSegment;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Add pet profile';

    public function boot(SizeManagementService $SizeManagementService)
    {
        $this->SizeManagementService = $SizeManagementService;
    }

    public function mount()
    {
        $this->firstSegment = request()->segment(1);
        $this->pet_id = Request::route('id');
        $this->customer_id = Request::route('customer_id');
        // Fetch available sizes for the dropdown
        $this->size = Size::select('id as value','name as option')->get()->toArray();

        if(\Session::get('edit') == "edit")
        {
            $this->title = 'Edit pet profile';
        }

        \session(['submenu' => 'size-managements']);
    }

    public function render()
    {
        try {
            $result = $this->SizeManagementService->getSizeManagement($this->pet_id);

            if ($result['status'] === 'success') {
                // Get Size Management record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.size-management', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.size-management');
    }

    public function save()
    {
        // $this->validate();
        try {
            $data = $this->only(['pet_id','customer_id','name','size_id']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->SizeManagementService->saveSizeManagement($this->editId,$data);

            if ($result['status'] === 'success') {
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
        try{
            $SizeManagement = SizeManagementModel::findOrFail($id);
            $this->editId = $id;
            $this->name = $SizeManagement->name;
            $this->size_id = $SizeManagement->size_id;
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
            $result = $this->SizeManagementService->deleteSizeManagement($this->deleteId);
            
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
        $this->reset(['name','size_id','editId']);
    }
}
