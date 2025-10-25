<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Request;
use App\Services\VaccineExemptionService;
use App\Models\Species;
use App\Models\BloodTest;
use App\Models\VaccineExemption as VaccineExemptionModel;
use Auth;

class VaccineExemption extends Component
{
    
    protected $VaccineExemptionService;

    public $species_id, $blood_test_id = [], $created_by,$updated_by;
    public $VaccineExemption,$species,$blood_tests = [];
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Pet Settings';

    public function boot(VaccineExemptionService $VaccineExemptionService)
    {
        $this->VaccineExemptionService = $VaccineExemptionService;
    }

    public function mount()
    {
        $this->species = Species::select('id as value', 'name as option')->get()->toArray();
        \session(['submenu' => 'vaccine-exemptions']);
    }

    public function render()
    {
        try {
            $result = $this->VaccineExemptionService->getVaccineExemption();

            if ($result['status'] === 'success') {
                // Get VaccineExemption record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.vaccine-exemption', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.vaccine-exemption');
        
    }

    protected function messages()
    {
        return \App\Rules\VaccineExemptionRules::messages();
    }

    public function save()
    {
        $this->validate(\App\Rules\VaccineExemptionRules::rules($this->editId));
        try {
            $data = $this->only(['species_id','blood_test_id']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->VaccineExemptionService->saveVaccineExemption($this->editId,$data);

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

    public function changeSpecies()
    {
        // dd($this->species_id);
        $this->blood_tests = BloodTest::select('id as value', 'name as option')->where('species_id',$this->species_id)->where('is_active',true)->get()->toArray();
        $this->dispatch('setup-blood-tests', blood_tests: $this->blood_tests);
    }

    public function edit($id)
    {
        try{
            $VaccineExemption= VaccineExemptionModel::findOrFail($id);
            $this->editId = $id;
            // $this->blood_test_id = $VaccineExemption->blood_test_id;
            // Must be an array for Select2 multiple
            $this->blood_test_id = is_array($VaccineExemption->blood_test_id)
            ? $VaccineExemption->blood_test_id
            : (array) $VaccineExemption->blood_test_id;

            $this->species_id = $VaccineExemption->species_id;

            // Tell JS to refresh Select2
            $this->blood_tests = BloodTest::select('id as value', 'name as option')->where('species_id',$this->species_id)->where('is_active',true)->get()->toArray();
            $this->dispatch('setup-blood-tests', blood_tests: $this->blood_tests);
            $this->dispatch('set-blood-tests', blood_test_id: $this->blood_test_id);

            // $this->dispatchBrowserEvent('set-blood-tests', [
            //     'blood_test_id' => $this->blood_test_id
            // ]);

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
            $result = $this->VaccineExemptionService->deleteVaccineExemption($this->deleteId);
            
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
            $result = $this->VaccineExemptionService->statusVaccineExemption($id);
            
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
        $this->reset(['blood_test_id','species_id','editId']);
        $this->dispatch('reset-blood-tests');
    }
}
