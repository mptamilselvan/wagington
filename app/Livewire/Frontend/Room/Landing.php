<?php

namespace App\Livewire\Frontend\Room;

use Livewire\Component;
use App\Services\Frontend\Room\RoomService;
use App\Models\Species;

class Landing extends Component
{
    public array $sections = [];
    public string $q = '';
    public ?int $selectedSpecies = null;
    //protected ?RoomService $svc = null;
    public $species = [];
    public $filteredRoomTypes = [];
    public $renderCounter = 0;
    public $roomTypesData = [];
    public $lastUpdated = null;
    public $forceUpdate = 0;
    protected ?RoomService $roomService = null;
    public function mount()
    {
        $this->roomService = new RoomService();
        $roomTypes = $this->roomService->getLandingRoomTypes(species_id: $this->selectedSpecies);
        $this->roomTypesData = $roomTypes;
        $this->species = Species::orderBy('name')->pluck('name', 'id');
    }
    /*
    public function updatedQ(): void
    {
        if ($this->svc === null) {
            return;
        }
        
        // Convert empty string to null for consistency
        $speciesId = $this->selectedSpecies === '' ? null : $this->selectedSpecies;
        
        //$roomTypes = $this->svc->getLandingRoomTypes(species_id: $speciesId, q: $this->q);
        //$this->sections['roomTypes'] = $roomTypes;
    }*/

    /*
    public function updatedSelectedSpecies(RoomService $svc): void
    {
        \Log::info('updatedSelectedSpecies called', [
            'selectedSpecies' => $this->selectedSpecies,
            'q' => $this->q
        ]);
        
        // Convert empty string to null for consistency
        $speciesId = $this->selectedSpecies === '' ? null : $this->selectedSpecies;
        
        //$roomTypes = $svc->getLandingRoomTypes(species_id: $speciesId, q: $this->q);
        
        // Update the property directly
        //$this->filteredRoomTypes = $roomTypes;
        //$this->roomTypesData = $roomTypes;
        //$this->renderCounter++;
        //$this->lastUpdated = now();
        
        //\Log::info('Room types updated', [
        //    'count' => $roomTypes->count(),
        //    'roomTypes' => $roomTypes->pluck('name', 'id')->toArray(),
        //    'renderCounter' => $this->renderCounter
        //]);
    }*/


    public function testFilter(RoomService $svc): void
    {
        \Log::info('testFilter called', [
            'selectedSpecies' => $this->selectedSpecies,
            'q' => $this->q
        ]);
        
        // Convert empty string to null for consistency
        $speciesId = $this->selectedSpecies === '' ? null : $this->selectedSpecies;
        
        //$roomTypes = $svc->getLandingRoomTypes(species_id: $speciesId, q: $this->q);
        
        // Update the property directly
        //$this->filteredRoomTypes = $roomTypes;
        //$this->roomTypesData = $roomTypes;
        //$this->renderCounter++;
        //$this->lastUpdated = now();
        
        //\Log::info('Test filter completed', [
        //    'count' => $roomTypes->count(),
        //    'roomTypes' => $roomTypes->pluck('name', 'id')->toArray(),
        //    'renderCounter' => $this->renderCounter
        //]);
    }

    public function updatedSelectedSpecies(): void
    {
        $this->forceUpdate++;
        
        // Manually update the room types property
        $speciesId = $this->selectedSpecies === '' ? null : $this->selectedSpecies;
        $this->ensureService();
        $roomTypes = $this->roomService->getLandingRoomTypes(species_id: $speciesId, q: $this->q);
        $this->roomTypesData = $roomTypes;
        
        \Log::info('selectedSpecies updated', [
            'selectedSpecies' => $this->selectedSpecies,
            'forceUpdate' => $this->forceUpdate,
            'roomTypesCount' => $roomTypes->count()
        ]);
    }

    public function getRoomTypesProperty()
    {
        \Log::info('getRoomTypesProperty called', [
            'selectedSpecies' => $this->selectedSpecies
        ]);
        $this->ensureService();
        $roomTypes = $this->roomService->getLandingRoomTypes(species_id: $this->selectedSpecies);
        \Log::info('computed roomTypes', [
            'count' => $roomTypes->count(),
            'roomTypes' => $roomTypes->pluck('name', 'id')->toArray()
        ]);
        return $roomTypes;
    }

    public function render()
    {
        $this->ensureService();
        $roomTypes = $this->roomService->getLandingRoomTypes(species_id: $this->selectedSpecies);
        \Log::info('render called', [
            'selectedSpecies' => $this->selectedSpecies,
            'roomTypesCount' => $roomTypes->count()
        ]);
        return view('livewire.frontend.room.landing', [
            'roomTypes' => $roomTypes
        ]);
    }

    private function ensureService(): void
    {
        if ($this->roomService === null) {
            $this->roomService = new RoomService();
        }
    }
}