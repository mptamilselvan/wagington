<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Request;
use App\Services\VaccinationService;
use App\Models\Species;
use App\Models\Vaccination as VaccinationModel;
use Auth;

class Vaccination extends Component
{
    
    protected $VaccinationService;

    public $species_id, $name, $expiry_days, $created_by,$updated_by,$is_active = [];
    public $vaccination,$species;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Pet Settings';

    public function boot(VaccinationService $VaccinationService)
    {
        $this->VaccinationService = $VaccinationService;
    }

    public function mount()
    {
        $this->species = Species::select('id as value', 'name as option')->get()->toArray();
        
        \session(['submenu' => 'vaccination']);
    }

    public function render()
    {
        try {
            $result = $this->VaccinationService->getVaccination();

            if ($result['status'] === 'success') {
                // Get Vaccination record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.vaccination', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.vaccination');
        
    }

    protected function messages()
    {
        return \App\Rules\VaccinationRules::messages();
    }

    public function save()
    {
        $this->validate(\App\Rules\VaccinationRules::rules());
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

            $result = $this->VaccinationService->saveVaccination($this->editId,$data);

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
            $vaccination= VaccinationModel::findOrFail($id);
            $this->editId = $id;
            $this->name = $vaccination->name;
            $this->expiry_days = $vaccination->expiry_days;
            $this->species_id = $vaccination->species_id;
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
            $result = $this->VaccinationService->deleteVaccination($this->deleteId);
            
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
            $result = $this->VaccinationService->statusVaccination($id);
            
            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function resetFields()
    {
        $this->reset(['name','species_id','expiry_days','editId']);
    }
}
