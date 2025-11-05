<?php

namespace App\Livewire\Frontend\Room;

use Livewire\Component;
use App\Services\Frontend\Room\RoomService;
use App\Models\Room\RoomModel;
use Illuminate\Support\Collection;

class Details extends Component
{
    public $roomType = null;
    public Collection $relatedRooms;
    public $slug;
    public $relatedOffset = 0;
    public $relatedLimit = 5;
    public $hasMoreRelated = true;
    protected ?RoomService $roomService = null;

    // Pricing - only show price for no_of_days = 1
    public $selectedPrice = null;

    public function mount($slug = null)
    {
        $this->slug = $slug;
        $this->roomService = new RoomService();
        $this->relatedRooms = collect();
        
        if (!$slug) {
            abort(404, 'Room slug is required');
        }
        
        $this->roomType = $this->roomService->getRoomTypeBySlug($slug);
        
        if (!$this->roomType) {
            abort(404, 'Room type not found');
        }

        // Load room price options for this room type
        $this->loadPriceOptions();

        // Load initial related room types
        $this->loadRelatedRooms();
    }

    public function openBooking()
    {
        $this->dispatch('open-booking', roomTypeId: $this->roomType->id);
    }

    public function loadNextRelated()
    {
        $this->ensureService();
        $this->relatedOffset += $this->relatedLimit;
        $this->relatedRooms = $this->roomService->getRelatedRoomTypes($this->roomType, $this->relatedLimit, $this->relatedOffset);
        
        // Check if there are more rooms available for next page
        $nextBatch = $this->roomService->getRelatedRoomTypes($this->roomType, $this->relatedLimit, $this->relatedOffset + $this->relatedLimit);
        $this->hasMoreRelated = $nextBatch->count() > 0;
    }

    public function loadPreviousRelated()
    {
        $this->ensureService();
        $this->relatedOffset = max(0, $this->relatedOffset - $this->relatedLimit);
        $this->relatedRooms = $this->roomService->getRelatedRoomTypes($this->roomType, $this->relatedLimit, $this->relatedOffset);
        
        // Check if there are more rooms available for next page
        $nextBatch = $this->roomService->getRelatedRoomTypes($this->roomType, $this->relatedLimit, $this->relatedOffset + $this->relatedLimit);
        $this->hasMoreRelated = $nextBatch->count() > 0;
    }

    private function loadRelatedRooms()
    {
        $this->ensureService();
        $this->relatedRooms = $this->roomService->getRelatedRoomTypes($this->roomType, $this->relatedLimit, $this->relatedOffset);
        
        // Check if there are more rooms available
        $nextBatch = $this->roomService->getRelatedRoomTypes($this->roomType, $this->relatedLimit, $this->relatedOffset + $this->relatedLimit);
        $this->hasMoreRelated = $nextBatch->count() > 0;
    }

    private function ensureService()
    {
        if ($this->roomService === null) {
            $this->roomService = new RoomService();
        }
    }

    private function loadPriceOptions(): void
    {
        // Only load price for no_of_days = 1
        $priceOption = \App\Models\Room\RoomPriceOptionModel::where('room_type_id', $this->roomType->id)
            ->where('no_of_days', 1)
            ->first(['id','price']);
        
        if ($priceOption) {
            $this->selectedPrice = $priceOption->price;
        } else {
            $this->selectedPrice = null;
        }
    }

    public function render()
    {
        return view('livewire.frontend.room.details');
    }
}