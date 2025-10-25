<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Request;
use App\Services\BloodTestService;
use App\Models\Species;
use App\Models\BloodTest as BloodTestModel;
use Auth;

class BloodTest extends Component
{
    
    protected $BloodTestService;

    public $species_id, $name, $expiry_days, $created_by,$updated_by,$is_active = [];
    public $BloodTest,$species;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Pet Settings';

    public function boot(BloodTestService $BloodTestService)
    {
        $this->BloodTestService = $BloodTestService;
    }

    public function mount()
    {
        $this->species = Species::select('id as value', 'name as option')->get()->toArray();
        $this->is_active = BloodTestModel::pluck('is_active', 'id')->toArray();
        
        \session(['submenu' => 'blood-tests']);
    }

    public function render()
    {
        try {
            $result = $this->BloodTestService->getBloodTest();

            if ($result['status'] === 'success') {
                // Get BloodTest record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.blood-test', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.blood-test');
        
    }

    protected function messages()
    {
        return \App\Rules\BloodTestRules::messages();
    }

    public function save()
    {
        $this->validate(\App\Rules\BloodTestRules::rules());
        try {
            $data = $this->only(['species_id', 'name', 'expiry_days']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->BloodTestService->saveBloodTest($this->editId,$data);

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
            $BloodTest= BloodTestModel::findOrFail($id);
            $this->editId = $id;
            $this->name = $BloodTest->name;
            $this->expiry_days = $BloodTest->expiry_days;
            $this->species_id = $BloodTest->species_id;
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
            $result = $this->BloodTestService->deleteBloodTest($this->deleteId);
            
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

    public function toggleStatus($id)
    {
        try{
            $result = $this->BloodTestService->statusBloodTest($id);
            
            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (Exception $e) {
            dd($e);
            $e->getMessage();
        }
    }

    public function resetFields()
    {
        $this->reset(['name','species_id','expiry_days','editId']);
    }
}
