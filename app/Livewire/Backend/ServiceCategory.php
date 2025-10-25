<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use App\Services\ServiceCategoryService;
use App\Models\ServiceCategory as ServiceCategoryModel;
use Auth;
use Livewire\WithFileUploads;

class ServiceCategory extends Component
{
    use WithFileUploads;
    protected $ServiceCategoryService;

    public  $name,$description,$image,$src,$meta_title,$meta_description,$meta_keywords,$focus_keywords, $created_by,$updated_by;
    // public $size;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Service Setting';

    public function boot(ServiceCategoryService $ServiceCategoryService)
    {
        $this->ServiceCategoryService = $ServiceCategoryService;
    }

    public function mount()
    {
        \session(['submenu' => 'service-category']);
    }

    public function render()
    {
        try {
            $result = $this->ServiceCategoryService->getServiceCategory();

            if ($result['status'] === 'success') {
                // Get size record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.service-category', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.service-category');
    }

    protected function messages()
    {
        return \App\Rules\ServiceCategoryRules::messages();
    }

    public function save()
    {
        $this->name = ucfirst(strtolower($this->name));
        $this->validate(\App\Rules\ServiceCategoryRules::rules($this->editId));
        try {
            $data = $this->only(['name','description','image','meta_title','meta_description','meta_keywords','focus_keywords']);

            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->ServiceCategoryService->saveServiceCategory($this->editId,$data);

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
            $category= ServiceCategoryModel::findOrFail($id);
            $this->editId = $id;
            $this->name = $category->name;
            $this->src = $category->image;
            $this->description = $category->description;
            $this->meta_title = $category->meta_title;
            $this->meta_description = $category->meta_description;
            $this->meta_keywords = $category->meta_keywords;
            $this->focus_keywords = $category->focus_keywords;
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
            $result = $this->ServiceCategoryService->deleteServiceCategory($this->deleteId);
            
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
        $this->reset(['name','description','image','meta_title','meta_description','meta_keywords','focus_keywords','editId','src']);
    }
}
