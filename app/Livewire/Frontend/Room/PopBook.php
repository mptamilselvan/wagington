<?php

namespace App\Livewire\Frontend\Room;
use App\Services\RoomService;
use App\Models\RoomModel;
use App\Models\Pet;
use App\Models\Product;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\ImageService;
use App\Models\RoomTypeModel;
class PopBook extends Component
{
    public $room = null;
    protected ?RoomService $svc = null;
    public $booking = null;
    
    // Pet selection properties
    public $pets = [];
    public $selectedPets = [];
    public $currentPage = 0;
    public $petsPerPage = 3;
    public $totalPets = 0;
    public $hasMorePets = false;

    // Add-ons
    public $addons = [];
    public $selectedAddons = []; // [id => ['id'=>, 'name'=>, 'price'=>, 'qty'=>]]

    // Agreements
    public $agreeLeashed = false;
    public $agreeTerms = false;
    public $agreementDocumentUrl = null;
    public $agreementContent = null;
    public $aggreed_terms = [];

    public function mount()
    {
        $this->booking = [];
        $this->loadPets();
        $this->loadAddons();
    }

    public function refreshBooking()
    {
        $this->booking = [];
        $this->loadAgreements();
    }

    public function loadAgreements()
    {
        if (!$this->room || !$this->room->room_type_id) {
            return;
        }

        $roomType = RoomTypeModel::where('id', $this->room->room_type_id)->first();
        
        if (!$roomType || !$roomType->aggreed_terms) {
            return;
        }

        $this->aggreed_terms = $roomType->aggreed_terms;
        
        if (is_array($this->aggreed_terms) && count($this->aggreed_terms) > 0) {
            $firstTerm = $this->aggreed_terms[0];
            if (isset($firstTerm['document_url'])) {
                $this->agreementDocumentUrl = $firstTerm['document_url'];
            }
            if (isset($firstTerm['content'])) {
                $this->agreementContent = $firstTerm['content'];
            }
        }
    }
    
    public function loadPets()
    {
        $user = Auth::user();
        if ($user) {
            $this->totalPets = Pet::where('user_id', $user->id)
                ->where('sterilisation_status', true)
                ->count();
            
            $this->pets = Pet::where('user_id', $user->id)
                ->where('sterilisation_status', true)
                ->skip($this->currentPage * $this->petsPerPage)
                ->take($this->petsPerPage)
                ->get();
            
            $this->hasMorePets = ($this->currentPage + 1) * $this->petsPerPage < $this->totalPets;
        }
    }
    
    public function nextPage()
    {
        if ($this->hasMorePets) {
            $this->currentPage++;
            $this->loadPets();
        }
    }
    
    public function loadAddons()
    {
        $this->addons = Product::where('product_type', 'addon')
            ->where('status', 'published')
            ->orderBy('name')
            ->get(['id','name'])
            ->toArray();
    }

    public function selectAddon($addonId)
    {
        $addon = collect($this->addons)->firstWhere('id', (int)$addonId);
        if (!$addon) {
            return;
        }
        if (!isset($this->selectedAddons[$addon['id']])) {
            $this->selectedAddons[$addon['id']] = [
                'id' => $addon['id'],
                'name' => $addon['name'],
                'price' => 0,
                'qty' => 1,
            ];
        }
    }

    public function incrementAddon($addonId)
    {
        if (isset($this->selectedAddons[$addonId])) {
            $this->selectedAddons[$addonId]['qty']++;
        }
    }

    public function decrementAddon($addonId)
    {
        if (isset($this->selectedAddons[$addonId]) && $this->selectedAddons[$addonId]['qty'] > 1) {
            $this->selectedAddons[$addonId]['qty']--;
        }
    }

    public function removeAddon($addonId)
    {
        if (isset($this->selectedAddons[$addonId])) {
            unset($this->selectedAddons[$addonId]);
        }
    }

    public function getAddonsTotalProperty()
    {
        $sum = 0.0;
        foreach ($this->selectedAddons as $ad) {
            $sum += (float)$ad['price'] * (int)$ad['qty'];
        }
        return $sum;
    }

    public function previousPage()
    {
        if ($this->currentPage > 0) {
            $this->currentPage--;
            $this->loadPets();
        }
    }
    
    public function togglePetSelection($petId)
    {
        if (in_array($petId, $this->selectedPets)) {
            $this->selectedPets = array_filter($this->selectedPets, function($id) use ($petId) {
                return $id != $petId;
            });
        } else {
            $this->selectedPets[] = $petId;
        }
    }
    
    public function openBooking()
    {
        $this->dispatch('open-booking');
    }
    
    public function setRoom($roomId)
    {
        $this->room = RoomModel::find($roomId);
        if ($this->room) {
            $this->loadAgreements();
        }
    }
    public function render()
    {
        return view('livewire.frontend.room.pop-book');
    }
}