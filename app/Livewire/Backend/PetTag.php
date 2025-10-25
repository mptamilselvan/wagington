<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Request;
use App\Services\PetTagService;
use App\Models\Species;
use App\Models\PetTag as PetTagModel;
use Auth;

class PetTag extends Component
{
    
    protected $PetTagService;

    public $species_id, $from_age, $to_age,$tag, $created_by,$updated_by,$is_active = [];
    public $PetTag,$species;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Pet Settings';

    public function boot(PetTagService $PetTagService)
    {
        $this->PetTagService = $PetTagService;
    }

    public function mount()
    {
        $this->species = Species::select('id as value', 'name as option')->get()->toArray();
        \session(['submenu' => 'pet-tags']);
    }

    public function render()
    {
        try {
            $result = $this->PetTagService->getPetTag();

            if ($result['status'] === 'success') {
                // Get PetTag record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.pet-tag', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.pet-tag');
        
    }

    protected function messages()
    {
        return \App\Rules\PetTagRules::messages();
    }

    public function save()
    {
        $this->validate(\App\Rules\PetTagRules::rules());
        try {
            $data = $this->only(['species_id', 'from_age', 'to_age','tag']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->PetTagService->savePetTag($this->editId,$data);

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
            $PetTag= PetTagModel::findOrFail($id);
            $this->editId = $id;
            $this->tag = $PetTag->tag;
            $this->from_age = $PetTag->from_age;
            $this->to_age = $PetTag->to_age;
            $this->species_id = $PetTag->species_id;
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
            $result = $this->PetTagService->deletePetTag($this->deleteId);
            
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
        $this->reset(['tag','species_id','from_age','to_age','editId']);
    }
}
