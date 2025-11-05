<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use App\Services\ServiceSubCategoryService;
use App\Models\ServiceSubcategory as ServiceSubcategoryModel;
use App\Models\Servicecategory;
use Auth;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class ServiceSubcategory extends Component
{
    use WithFileUploads;
    protected $ServiceSubCategoryService;

    public  $name,$slug,$description,$image,$src,$category_id,$meta_title,$meta_description,$meta_keywords,$focus_keywords, $created_by,$updated_by,$service_categories = [];
    // public $size;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $title = 'Service Setting';

    public function boot(ServiceSubCategoryService $ServiceSubCategoryService)
    {
        $this->ServiceSubCategoryService = $ServiceSubCategoryService;
    }

    public function mount()
    {
        $this->service_categories = Servicecategory::select('id as value','name as option')->get()->toArray();
        \session(['submenu' => 'service-subcategory']);
    }

    public function render()
    {
        try {
            $result = $this->ServiceSubCategoryService->getServiceSubCategory();

            if ($result['status'] === 'success') {
                // Get size record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.service-subcategory', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.service-subcategory');
    }

    protected function messages()
    {
        return \App\Rules\ServiceSubCategoryRules::messages();
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
        $query = ServiceSubcategoryModel::where('slug', $slug);
        
        if ($this->editId) {
            $query->where('id', '!=', $this->editId);
        }
        return $query->exists();
    }

    public function save()
    {
        $this->name = ucfirst(strtolower($this->name));
        $this->validate(\App\Rules\ServiceSubCategoryRules::rules($this->editId));
        try {
            $data = $this->only(['name','category_id','description','image','meta_title','meta_description','meta_keywords','focus_keywords','slug']);

            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->ServiceSubCategoryService->saveServiceSubCategory($this->editId,$data);

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
            $subcategory= ServiceSubcategoryModel::findOrFail($id);
            $this->editId = $id;
            $this->name = $subcategory->name;
            $this->slug = $subcategory->slug;
            $this->category_id = $subcategory->category_id;
            $this->src = $subcategory->image;
            $this->description = $subcategory->description;
            $this->meta_title = $subcategory->meta_title;
            $this->meta_description = $subcategory->meta_description;
            $this->meta_keywords = $subcategory->meta_keywords;
            $this->focus_keywords = $subcategory->focus_keywords;
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
            $result = $this->ServiceSubCategoryService->deleteServiceSubCategory($this->deleteId);
            
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
        $this->reset(['name','category_id','description','image','meta_title','meta_description','meta_keywords','focus_keywords','editId','src','slug']);
    }
}
