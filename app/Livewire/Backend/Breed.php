<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\Breed as BreedModel;
use App\Models\Species as SpeciesModel;
use Illuminate\Support\Facades\Auth;
use Log;
use DB;
use App\Models\Pet as PetModel;

class Breed extends Component
{
    public $title = 'Pet Settings', $popUp = false;

    // public $name, $description, $image_url, $species_data, $editId = 0, $currentImage, $photo;
    public $speciesId, $breedsData, $speciesData, $editId = 0, $breedCount = 1, $breedNames = [], $x = [], $deleteId;

    public function rules()
    {
        $rules =  [
            'speciesId' => 'required|integer',
            'breedNames.*' => 'required|string|max:255'
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'speciesId.required' => 'Species is required',
            'speciesId.unique' => 'Species already exists',
            'breedNames.*.required' => 'Breed name is required',
        ];
    }

    public function mount()
    {
        \session(['submenu' => 'breeds']);
    }

    public function render()
    {
        $this->speciesData = SpeciesModel::select('id as value', 'name as option')->get()->toArray();
        $this->breedsData = BreedModel::select('species_id', DB::raw('STRING_AGG(name, \', \') as "name"'))->with('species')->groupBy('species_id')->get(); //dd($this->breedsData);
        return view('livewire.backend.breed');
    }

    public function index()
    {
        return view('backend.breed');
    }


    public function save()
    {
        $this->validate($this->rules(), $this->messages());

        try {

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            if ($this->editId) {
                //$this->rules['name'] = 'required|string|max:255|unique:species,name,' . $this->editId;              
                //BreedModel::where('species_id', $this->editId)->delete();
                foreach ($this->breedNames as $key => $name) { //dd($this->breedNames);
                    $data['species_id'] = $this->speciesId;
                    $data['name'] = $name;        
                    //BreedModel::find($key)->update($data);
                    BreedModel::updateOrCreate(
                                ['id' => $key], // Search by ID
                                $data // Attributes to create or update
                            );
                }
            }
            else{
                    foreach ($this->breedNames as $key => $name) {
                    $data['species_id'] = $this->speciesId;
                    $data['name'] = $name;                    
                    BreedModel::create($data);
                }
            }            

            session()->flash('success', 'Breed saved successfully.');
            $this->resetFields();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $this->breedsData = BreedModel::with('species')->where('species_id', $id)->get();
            $this->editId = $this->speciesId = $id;
            //$this->speciesId = $this->breedsData['species_id'];
            //$breedNames = explode(',', $this->breedsData['name']);
            $this->breedCount = $this->breedsData->count();
            foreach ($this->breedsData as $index => $breeds) {
                $this->breedNames[$breeds['id']] = $breeds['name'];
            } //dd($this->breedNames);
        } catch (\Exception $e) {
            $e->getMessage();
        }
    }

    public function deletePopUp($id)
    {
        // checking foreign key relation
        $deleteFlag = false;
        $breedIds = BreedModel::where('species_id', $id)->pluck('id')->toArray(); //dd($breedIds);
        foreach ($breedIds as $breedId) {
            $breed = BreedModel::find($breedId);
            if ($breed->pets()->exists()) {
                $deleteFlag = true;
                break;
            }
        }

        if ($deleteFlag) {
            session()->flash('error', 'Cannot delete this breed as it is associated with existing pets.');
        } else {
            $this->deleteId = $id;
            $this->popUp = true;
        }
    }

    public function delete()
    {
        try {
            $breed = BreedModel::where('species_id', $this->deleteId)->delete();
            $this->reset('deleteId', 'popUp');
            session()->flash('success', 'Breed deleted successfully.');
        } catch (Exception $e) {
            dd($e->getMessage());
            $e->getMessage();
        }
    }

    public function addMoreBreeds()
    {
        $this->breedCount++;
        array_push($this->breedNames, "");
        //dd($this->breedCount);
    }

    public function removeBreed($index)
    {
        // checking foreign key relation
        $breed = BreedModel::find($index);
        if($breed)
        {
            if ($breed->pets()->exists()) {
                session()->flash('error', 'Cannot remove this breed as it is associated with existing pets.');
                return;
            }
            unset($this->breedNames[$index]);
            $this->breedCount--;
            $this->breedCount = count($this->breedNames);
            BreedModel::find($index)->delete();
        }
        else{
            unset($this->breedNames[$index]);
            $this->breedCount--;
            $this->breedCount = count($this->breedNames);
        }
        //$this->breedNames = array_values($this->breedNames);
        //     Log::info($this->breedNames);
        //     Log::info($index);
        //     Log::info($this->breedCount);
        //     //dd($this->name);
    }

    public function resetFields()
    {
        $this->reset(['breedNames', 'speciesId', 'editId']);
        $this->breedCount = 1;
    }
}
