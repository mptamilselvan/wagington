<?php

namespace App\Livewire\Frontend\Room;

use Livewire\Component;
use App\Services\RoomService;
use App\Models\RoomModel;
use Illuminate\Support\Collection;

class Details extends Component
{
    public $room = null;
    public Collection $relatedRooms;
    public $slug;
    public $relatedOffset = 0;
    public $relatedLimit = 5;
    public $hasMoreRelated = true;
    protected ?RoomService $svc = null;

    public function mount($slug = null, RoomService $svc)
    {
        $this->slug = $slug;
        $this->svc = $svc;
        $this->relatedRooms = collect();
        
        if (!$slug) {
            abort(404, 'Room slug is required');
        }
        
        $this->room = $this->svc->getRoomBySlug($slug);
        
        if (!$this->room) {
            abort(404, 'Room not found');
        }

        // Load initial related room types
        $this->loadRelatedRooms();
    }

    public function loadNextRelated()
    {
        $this->ensureService();
        $this->relatedOffset += $this->relatedLimit;
        $this->relatedRooms = $this->svc->getRelatedRoomTypes($this->room, $this->relatedLimit, $this->relatedOffset);
        
        // Check if there are more rooms available for next page
        $nextBatch = $this->svc->getRelatedRoomTypes($this->room, $this->relatedLimit, $this->relatedOffset + $this->relatedLimit);
        $this->hasMoreRelated = $nextBatch->count() > 0;
    }

    public function loadPreviousRelated()
    {
        $this->ensureService();
        $this->relatedOffset = max(0, $this->relatedOffset - $this->relatedLimit);
        $this->relatedRooms = $this->svc->getRelatedRoomTypes($this->room, $this->relatedLimit, $this->relatedOffset);
        
        // Check if there are more rooms available for next page
        $nextBatch = $this->svc->getRelatedRoomTypes($this->room, $this->relatedLimit, $this->relatedOffset + $this->relatedLimit);
        $this->hasMoreRelated = $nextBatch->count() > 0;
    }

    private function loadRelatedRooms()
    {
        $this->ensureService();
        $this->relatedRooms = $this->svc->getRelatedRoomTypes($this->room, $this->relatedLimit, $this->relatedOffset);
        
        // Check if there are more rooms available
        $nextBatch = $this->svc->getRelatedRoomTypes($this->room, $this->relatedLimit, $this->relatedOffset + $this->relatedLimit);
        $this->hasMoreRelated = $nextBatch->count() > 0;
    }

    private function ensureService()
    {
        if ($this->svc === null) {
            $this->svc = app(RoomService::class);
        }
    }

    public function render()
    {
        return view('livewire.frontend.room.details');
    }
}