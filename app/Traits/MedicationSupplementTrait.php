<?php

namespace App\Traits;

use Auth;
use App\Models\MedicationSupplement;
use App\Models\MedicationSupplementAdminDetail;

trait MedicationSupplementTrait
{
    public function save()
    {
        $this->validate(\App\Rules\MedicationSupplementRules::rules());
        try {
            $data = $this->only(['pet_id','customer_id','type','name','dosage','notes']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->MedicationSupplementService->saveMedicationSupplement($this->editId,$data);

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
            $MedicationSupplement = MedicationSupplement::findOrFail($id);
            $this->editId = $id;
            $this->name = $MedicationSupplement->name;
            $this->type = $MedicationSupplement->type;
            $this->dosage = $MedicationSupplement->dosage;
            $this->notes = $MedicationSupplement->notes;
        } catch (Exception $e) {
            $e->getMessage();
        }
    }
    
    public function showAdministered($id)
    {
        $this->medication_supplement_id = $id;
        $this->showAdministeredPopup = true;
        $this->resetFields();
        $this->loadRecords();
        // $this->records = MedicationSupplementAdminDetail::where('medication_supplement_id', $id)->get();
    }

    function loadRecords()  {
        $this->records = MedicationSupplementAdminDetail::where('medication_supplement_id', $this->medication_supplement_id)->get();
    }

    public function saveAdministeredDetails() 
    {
        $data = $this->only(['medication_supplement_id','administer_name','date','time','administer_notes']);
        $data['created_by'] = Auth::user()->id;
        $data['updated_by'] = Auth::user()->id;

        $result = $this->MedicationSupplementService->saveAdministeredDetails($this->medication_supplement_id,$data);

        $this->loadRecords();

        
        if ($result['status'] === 'success') {
            // Store record ID for address creation
            $this->resetErrorBag();
            session()->flash('success', $result['message']);
            
            $this->reset(['administer_notes']);
            // $this->resetFields();
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
        
    }
    
    public function deletePopUp($id)
    {
        $this->deleteId = $id;
        $this->popUp = true;
    }

    public function delete()
    {
        try{
            $result = $this->MedicationSupplementService->deleteMedicationSupplement($this->deleteId);
            
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
            $result = $this->MedicationSupplementService->statusMedicationSupplement($id);
            
            if ($result['status'] == 'success') {
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
        $this->reset(['type','name','dosage','notes','editId']);
    }
}