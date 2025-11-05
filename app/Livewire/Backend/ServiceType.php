<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use App\Services\ServiceTypeService;
use App\Models\ServiceType as ServiceTypeModel;
use Auth;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class ServiceType extends Component
{
    use WithFileUploads;
    protected $ServiceTypeService;

    public  $name,$slug,$description,$image,$src,$meta_title,$meta_description,$meta_keywords,$focus_keywords, $created_by,$updated_by;
    // public $size;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Service Setting';

    public function boot(ServiceTypeService $ServiceTypeService)
    {
        $this->ServiceTypeService = $ServiceTypeService;
    }

    public function mount()
    {
        \session(['submenu' => 'service-type']);
    }

    public function render()
    {
        try {
            $result = $this->ServiceTypeService->getServiceType();

            if ($result['status'] === 'success') {
                // Get size record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.service-type', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.service-type');
    }

    protected function messages()
    {
        return \App\Rules\ServiceTypeRules::messages();
    }

    /* --------------------------------------------------------------
     * SLUG HANDLING
     * -------------------------------------------------------------- */
    // generate unique slug
    public function generateUniqueSlug()
    {
        if (empty($this->name)) {
            return '';
        }

        // Base slug
        $baseSlug = Str::slug($this->name);

        $slug = $baseSlug;
        $counter = 1;

        // Ensure uniqueness
        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        return $this->slug = $slug;
    }


    // slug existence check
    private function slugExists($slug)
    {
        $query = ServiceTypeModel::where('slug', $slug);
        
        if ($this->editId) {
            $query->where('id', '!=', $this->editId);
        }
        return $query->exists();
    }

    public function save()
    {
        $this->name = ucfirst(strtolower($this->name));
        $this->validate(\App\Rules\ServiceTypeRules::rules($this->editId));
        try {
            $data = $this->only(['name','slug','description','image','meta_title','meta_description','meta_keywords','focus_keywords']);

            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->ServiceTypeService->saveServiceType($this->editId,$data);

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
            $service_type= ServiceTypeModel::findOrFail($id);
            $this->editId = $id;
            $this->name = $service_type->name;
            $this->slug = $service_type->slug;
            $this->src = $service_type->image;
            $this->description = $service_type->description;
            $this->meta_title = $service_type->meta_title;
            $this->meta_description = $service_type->meta_description;
            $this->meta_keywords = $service_type->meta_keywords;
            $this->focus_keywords = $service_type->focus_keywords;
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
            $result = $this->ServiceTypeService->deleteServiceType($this->deleteId);
            
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
        $this->reset(['name','description','image','meta_title','meta_description','meta_keywords','focus_keywords','editId','src','slug']);
    }
}
