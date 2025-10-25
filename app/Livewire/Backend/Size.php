<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use App\Services\SizeService;
use App\Models\Size as SizeModel;
use Auth;

class Size extends Component
{
    
    protected $SizeService;

    public  $name, $created_by,$updated_by;
    public $size;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Pet Settings';

    public function boot(SizeService $SizeService)
    {
        $this->SizeService = $SizeService;
    }

    public function mount()
    {
        \session(['submenu' => 'sizes']);
    }

    public function render()
    {
        try {
            $result = $this->SizeService->getsize();

            if ($result['status'] === 'success') {
                // Get size record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.size', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.size');
    }

    protected function messages()
    {
        return \App\Rules\SizeRules::messages();
    }

    public function save()
    {
        $this->name = ucfirst(strtolower($this->name));
        $this->validate(\App\Rules\SizeRules::rules($this->editId));
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

            $result = $this->SizeService->savesize($this->editId,$data);

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
            $size= SizeModel::findOrFail($id);
            $this->editId = $id;
            $this->name = $size->name;
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
            $result = $this->SizeService->deletesize($this->deleteId);
            
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
