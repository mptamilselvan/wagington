<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\Species;
use App\Models\SpeciesSizeModel;
use Illuminate\Support\Facades\Auth;
use App\Services\SpeciesSizeSettingService;

/**
 * @OA\Tag(
 *     name="Species Size Setting",
 *     description="species-size-setting-specific APIs"
 * )
 * 
 */
class SpeciesSizeSetting extends Component
{
    protected $speciesSizeSettingService;

    public $title = 'Species Size Setting', $popUp = false;

    // Species selection
    public $selectedSpeciesId = null;
    public $species = [];

    // Size management
    public $sizes = [];
    public $editingSizeId = null;
    public $newSize = '';
    public $newDescription = '';
    public $newImage = '';
    public $newIcon = '';
    public $newColor = '#000000'; // Default black color

    // Form properties
    public $editId = 0, $deleteId;

    public function boot(SpeciesSizeSettingService $speciesSizeSettingService)
    {
        $this->speciesSizeSettingService = $speciesSizeSettingService;
    }

    public function index()
    {
        return view('backend.species-size-setting');
    }

    public function mount()
    {
        \session(['submenu' => 'species-size-setting']);
        
        // Load all species
        $this->species = Species::orderBy('name')->get();
    }

    public function render()
    {
        // Ensure species are loaded
        if (empty($this->species)) {
            $this->species = Species::orderBy('name')->get();
        }
                
        // Load sizes for selected species
        if ($this->selectedSpeciesId) {
            $this->sizes = SpeciesSizeModel::where('species_id', $this->selectedSpeciesId)
                ->orderBy('size')
                ->get();
        } else {
            $this->sizes = [];
        }

        return view('livewire.backend.species-size-setting');
    }

    // Species selection
    public function selectSpecies($speciesId)
    {
        $this->selectedSpeciesId = $speciesId;
        $this->resetSizeForm();
    }

    // Size management methods
    public function addSize()
    {
        $this->validate(\App\Rules\SpeciesSizeSettingRules::rules($this->editId));

        try {
            SpeciesSizeModel::create([
                'species_id' => $this->selectedSpeciesId,
                'size' => $this->newSize,
                'color' => $this->newColor,
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);

            $this->resetSizeForm();
            session()->flash('success', 'Size added successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error adding size: ' . $e->getMessage());
        }
    }

    public function editSize($sizeId)
    {
        $this->editingSizeId = $sizeId;
        $size = SpeciesSizeModel::findOrFail($sizeId);
        $this->newSize = $size->size;
        $this->newColor = $size->color;
    }

    public function updateSize($sizeId)
    {
        $this->validate(\App\Rules\SpeciesSizeSettingRules::rules($this->editId));

        try {
            $size = SpeciesSizeModel::findOrFail($sizeId);
            $size->update([
                'size' => $this->newSize,
                'color' => $this->newColor,
                'updated_by' => Auth::id(),
            ]);

            $this->resetSizeForm();
            session()->flash('success', 'Size updated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating size: ' . $e->getMessage());
        }
    }

    public function deleteSize($sizeId)
    {
        try {
            $size = SpeciesSizeModel::findOrFail($sizeId);
            $size->delete();
            session()->flash('success', 'Size deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting size: ' . $e->getMessage());
        }
    }

    public function cancelEdit()
    {
        $this->resetSizeForm();
    }

    private function resetSizeForm()
    {
        $this->editingSizeId = null;
        $this->newSize = '';
        $this->newImage = '';
        $this->newIcon = '';
        $this->newColor = '#000000'; // Default black color
    }

    // Clear success message from session
    public function clearSuccessMessage()
    {
        session()->forget('success');
    }
}
