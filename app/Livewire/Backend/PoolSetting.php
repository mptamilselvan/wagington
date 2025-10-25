<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use App\Services\PoolSettingService;
use App\Models\PoolSetting as PoolSettingModel;
use Auth;

class PoolSetting extends Component
{
    protected $PoolSettingService;

    public  $name,$type,$allowed_pet, $created_by,$updated_by;

    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Services Settings';

    public function boot(PoolSettingService $PoolSettingService)
    {
        $this->PoolSettingService = $PoolSettingService;
    }

    public function mount()
    {
        \session(['submenu' => 'pool-setting']);
    }

    public function render()
    {
        try {
            $result = $this->PoolSettingService->getPoolSetting();

            if ($result['status'] === 'success') {
                // Get size record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.pool-setting', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.pool-setting');
    }

    protected function messages()
    {
        return \App\Rules\PoolSettingRules::messages();
    }

    public function save()
    {
        $this->name = ucfirst(strtolower($this->name));
        $this->validate(\App\Rules\PoolSettingRules::rules($this->editId));
        try {
            $data = $this->only(['name','type','allowed_pet']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->PoolSettingService->savePoolSetting($this->editId,$data);

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
            $pool_setting= PoolSettingModel::findOrFail($id);
            $this->editId = $id;
            $this->name = $pool_setting->name;
            $this->type = $pool_setting->type;
            $this->allowed_pet = $pool_setting->allowed_pet;
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
            $result = $this->PoolSettingService->deletePoolSetting($this->deleteId);
            
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
        $this->reset(['name','type','allowed_pet','editId']);
    }
}

