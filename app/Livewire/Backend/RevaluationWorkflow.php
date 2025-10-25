<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Request;
use App\Services\RevaluationWorkflowService;
use App\Models\Species;
use App\Models\RevaluationWorkflow as RevaluationWorkflowModel;
use Auth;

class RevaluationWorkflow extends Component
{
    
    protected $RevaluationWorkflowService;

    public $species_id, $age_threshold_months, $is_required, $created_by,$updated_by,$is_active = [];
    public $RevaluationWorkflow,$species;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Pet Settings';

    public function boot(RevaluationWorkflowService $RevaluationWorkflowService)
    {
        $this->RevaluationWorkflowService = $RevaluationWorkflowService;
    }

    public function mount()
    {
        $this->species = Species::select('id as value', 'name as option')->get()->toArray();
        \session(['submenu' => 'revaluation-workflow']);
    }

    public function render()
    {
        try {
            $result = $this->RevaluationWorkflowService->getRevaluationWorkflow();

            if ($result['status'] === 'success') {
                // Get RevaluationWorkflow record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.revaluation-workflow', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.revaluation-workflow');
        
    }

    protected function messages()
    {
        return \App\Rules\RevaluationWorkflowRules::messages();
    }

    public function save()
    {
        $this->validate(\App\Rules\RevaluationWorkflowRules::rules());
        try {
            // dd($this->is_required);
            $data = $this->only(['species_id', 'age_threshold_months']);
            if($this->is_required == true)
            {
                $data['is_required'] = true;
            }
            else{
                $data['is_required'] = false;
            }
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->RevaluationWorkflowService->saveRevaluationWorkflow($this->editId,$data);

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
            $RevaluationWorkflow= RevaluationWorkflowModel::findOrFail($id);
            $this->editId = $id;
            $this->is_required = $RevaluationWorkflow->is_required;
            $this->age_threshold_months = $RevaluationWorkflow->age_threshold_months;
            $this->species_id = $RevaluationWorkflow->species_id;
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
            $result = $this->RevaluationWorkflowService->deleteRevaluationWorkflow($this->deleteId);
            
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
            $result = $this->RevaluationWorkflowService->statusRevaluationWorkflow($id);
            
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
        $this->reset(['species_id','age_threshold_months','is_required','editId']);
    }
}
