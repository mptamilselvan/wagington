<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use App\Services\AdvanceDurationService;
use App\Models\AdvanceDuration as AdvanceDurationModel;
use Auth;

class AdvanceDuration extends Component
{
    
    protected $AdvanceDurationService;

    public $advance_days = 0;
    public $advance_hours = 0;

    public $created_by,$updated_by;
    public $size;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Services Settings';

    public function boot(AdvanceDurationService $AdvanceDurationService)
    {
        $this->AdvanceDurationService = $AdvanceDurationService;
    }

    public function mount()
    {
        \session(['submenu' => 'advance-duration']);
    }

    public function render()
    {
        try {
            $result = $this->AdvanceDurationService->getAdvanceDuration();

            if ($result['status'] === 'success') {
                // Get size record
                $data = $result['data'];

                if($result['data'])
                {
                    $this->advance_days = $data->advance_days;
                    $this->advance_hours = $data->advance_hours;
                }

            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.advance-duration');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.advance-duration');
    }

    // protected function messages()
    // {
    //     return \App\Rules\AdvanceDurationRules::messages();
    // }

    public function save()
    {
        $this->validate(\App\Rules\AdvanceDurationRules::rules($this->editId));
        try {
            $data = $this->only(['advance_days','advance_hours']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->AdvanceDurationService->saveAdvanceDuration($this->editId,$data);

            if ($result['status'] === 'success') {
                // Store record ID for address creation
                session()->flash('success', $result['message']);
                
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
            
        } catch (Exception $e) {
            dd($e);
        }
    }

}
