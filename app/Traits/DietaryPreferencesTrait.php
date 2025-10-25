<?php

namespace App\Traits;

use Auth;
use App\Models\DietaryPreferences;

trait DietaryPreferencesTrait
{
    public function save()
    {
        $this->validate(\App\Rules\DietaryPreferencesRules::rules());
        try {
            $data = $this->only(['pet_id','customer_id', 'notes','feed_time','allergies']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->DietaryPreferencesService->saveDietaryPreferences($this->editId,$data);

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
            $DietaryPreferences = DietaryPreferences::findOrFail($id);
            $this->editId = $id;
            $this->notes = $DietaryPreferences->notes;
            $this->feed_time = $DietaryPreferences->feed_time;
            $this->allergies = $DietaryPreferences->allergies;
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
            $result = $this->DietaryPreferencesService->deleteDietaryPreferences($this->deleteId);
            
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
            $result = $this->DietaryPreferencesService->statusDietaryPreferences($id);
            
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
        $this->reset(['notes','feed_time','allergies','editId']);
    }
}