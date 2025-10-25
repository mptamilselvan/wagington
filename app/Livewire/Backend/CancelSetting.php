<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\CancelSetting as CancelSettingModel;
use Illuminate\Support\Facades\Auth;
use App\Services\CancelSettingService;

/**
 * @OA\Tag(
 *     name="Cancellation & Refund Settings",
 *     description="cancellation-refund-settings-specific APIs"
 * )
 * 
 */
class CancelSetting extends Component
{
    protected $cancelSettingService;

    public $title = 'Cancellation & Refund Settings', $popUp = false;

    public $before_6_hour_percentage, $before_24_hour_percentage, $before_72_hour_percentage, $admin_cancel_percentage, $editId = 0, $deleteId;

    public function boot(CancelSettingService $cancelSettingService)
    {
        $this->cancelSettingService = $cancelSettingService;
    }

    public function mount()
    {
        \session(['submenu' => 'cancel-setting']);
        
        // Load existing configuration if exists
        $existingConfig = CancelSettingModel::first();
        if ($existingConfig) {
            $this->editId = $existingConfig->id;
            $this->before_6_hour_percentage = $existingConfig->before_6_hour_percentage;
            $this->before_24_hour_percentage = $existingConfig->before_24_hour_percentage;
            $this->before_72_hour_percentage = $existingConfig->before_72_hour_percentage;
            $this->admin_cancel_percentage = $existingConfig->admin_cancel_percentage;
        }
    }

    public function render()
    {
        try{
            $result = $this->cancelSettingService->getCancelSetting();
            $data = [];
            if ($result['status'] === 'success') {
                // Get cancel setting records
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.cancel-setting', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.cancel-setting');
    }

    protected function messages()
    {
        return \App\Rules\CancelSettingRules::messages();
    }


    public function save()
    {
        // No need to transform percentages - they should be numeric
        $this->validate(\App\Rules\CancelSettingRules::rules($this->editId));

        try {
            $data = [
                'before_6_hour_percentage' => $this->before_6_hour_percentage,
                'before_24_hour_percentage' => $this->before_24_hour_percentage,
                'before_72_hour_percentage' => $this->before_72_hour_percentage,
                'admin_cancel_percentage' => $this->admin_cancel_percentage,
            ];
            
            $data['updated_by'] = Auth::id();
            
            // Check if config already exists (singleton pattern)
            $existingConfig = CancelSettingModel::first();
            
            if ($existingConfig) {
                // Update existing configuration
                $this->editId = $existingConfig->id;
            } else {
                // First time creating configuration
                $data['created_by'] = Auth::id();
            }

            $result = $this->cancelSettingService->saveCancelSetting($this->editId, $data);

            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
                
                // Reload the configuration
                $this->mount();
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

    // No edit, delete, or reset methods needed for singleton configuration
    // The form always shows and updates the single configuration record
}
