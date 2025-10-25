<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use App\Services\BookingStatusSettingService;
use App\Models\BookingStatusSetting as BookingStatusSettingModel;
use Auth;

class BookingStatusSetting extends Component
{
    
    protected $BookingStatusSettingService;

    public  $name, $created_by,$updated_by;
    public $BookingStatusSetting;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Services Settings';

    public function boot(BookingStatusSettingService $BookingStatusSettingService)
    {
        $this->BookingStatusSettingService = $BookingStatusSettingService;
    }

    public function mount()
    {
        \session(['submenu' => 'booking-status-settings']);
    }

    public function render()
    {
        try {
            $result = $this->BookingStatusSettingService->getBookingStatusSetting();

            if ($result['status'] === 'success') {
                // Get BookingStatusSetting record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.booking-status-setting', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.booking-status-setting');
    }

    protected function messages()
    {
        return \App\Rules\BookingStatusSettingRules::messages();
    }

    public function save()
    {
        $this->name = ucfirst(strtolower($this->name));
        $this->validate(\App\Rules\BookingStatusSettingRules::rules($this->editId));
        try {
            $data = $this->only(['name']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->BookingStatusSettingService->saveBookingStatusSetting($this->editId,$data);

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
            $BookingStatusSetting= BookingStatusSettingModel::findOrFail($id);
            $this->editId = $id;
            $this->name = $BookingStatusSetting->name;
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
            $result = $this->BookingStatusSettingService->deleteBookingStatusSetting($this->deleteId);
            
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
        $this->reset(['name','editId']);
    }
}
