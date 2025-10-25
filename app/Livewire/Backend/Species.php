<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\Species as SpeciesModel;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Services\SpeciesService;

/**
 * @OA\Tag(
 *     name="Species",
 *     description="species-specific APIs"
 * )
 * 
 */
class Species extends Component
{

    protected $speciesService;

    use WithFileUploads;
   
    public $title = 'Pet Settings', $popUp = false;

    public $name, $description, $image_url, $species_data, $editId = 0, $src, $photo,$deleteId;

    public function boot(SpeciesService $speciesService)
    {
        $this->speciesService = $speciesService;
    }

    public function mount()
    {
        \session(['submenu' => 'species']);
    }

    public function render()
    {
        try{
            $result = $this->speciesService->getSpecies();
            if ($result['status'] === 'success') {
                // Get size record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.species', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.species');
    }

    protected function messages()
    {
        return \App\Rules\SpeciesRules::messages();
    }


    public function save()
    {
        $this->name = ucfirst(strtolower($this->name));
        // $this->validate(\App\Rules\SpeciesRules::rules($this->editId));
        $this->validate(\App\Rules\SpeciesRules::rules($this->editId));

        try {
            $data = $this->only(['name','description','photo']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $result = $this->speciesService->saveSpecies($this->editId,$data);

            

            if ($result['status'] === 'success') {
                // dd($result);
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
        try {
            $speciesData = SpeciesModel::findOrFail($id);
            $this->editId = $id;
            $this->name = $speciesData->name;
            $this->description = $speciesData->description;
            $this->src = $speciesData->image_url;
        } catch (\Exception $e) {
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
            $result = $this->speciesService->deleteSpecies($this->deleteId);
            
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
        $this->reset(['name', 'description', 'photo', 'editId', 'src']);
    }
}
