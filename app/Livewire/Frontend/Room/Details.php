<?php

namespace App\Livewire\Frontend\Room;

use Livewire\Component;
use App\Services\RoomService;
use App\Models\RoomModel;

class Details extends Component
{
    public $room = null;
    public $slug;
    protected RoomService $svc;

    public function mount($slug = null, RoomService $svc)
    {
        $this->slug = $slug;
        $this->svc = $svc;
        
        if (!$slug) {
            abort(404, 'Room slug is required');
        }
        
        $this->room = $this->svc->getRoomBySlug($slug);
        
        if (!$this->room) {
            abort(404, 'Room not found');
        }
    }

    public function render()
    {
        return view('livewire.frontend.room.details');
    }
}