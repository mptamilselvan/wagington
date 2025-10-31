<?php

namespace App\Livewire\Frontend\Room;
//use App\Services\RoomService;
use App\Models\Room\RoomModel;
use App\Models\Pet;
use App\Models\Product;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\ImageService;
use App\Models\Room\RoomTypeModel;
use App\Services\Frontend\Room\RoomService;
use App\Models\Room\RoomPriceOptionModel;
use Carbon\Carbon;
// removed duplicate Pet import
use App\Models\Size;
class PopBook extends Component
{
    public $room = null;
    protected ?RoomService $roomService = null;
    public ?string $availabilityMessage = null;
    public ?string $availabilityType = null; // 'success' | 'error'
    public $availabilityDetails = null; // ['available_rooms'=>int, 'pet_size_availability'=>[]]
    public $quote = null; // pricing summary when available
    public $booking = null;
    public $room_type_id = null;
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
    public $agreeDocs = []; // checkbox states per agreement term index

    // Date selection
    public $start_date = null; // Y-m-d
    public $end_date = null;   // Y-m-d

    public function mount($roomTypeId = null)
    {
        $this->room_type_id = $roomTypeId;
        $this->booking = [];
        $this->loadPets();
        $this->loadAddons();
        $this->roomService = new RoomService();
        $this->loadAgreements();
    }

    public function refreshBooking()
    {
        $this->booking = [];
        $this->loadAgreements();
    }

    public function loadAgreements()
    {
        $roomTypeId = null;
        if ($this->room && $this->room->room_type_id) {
            $roomTypeId = $this->room->room_type_id;
        } elseif ($this->room_type_id) {
            $roomTypeId = $this->room_type_id;
        }
        if (!$roomTypeId) {
            return;
        }

        $roomType = RoomTypeModel::where('id', $roomTypeId)->first();
        
        if (!$roomType || !$roomType->aggreed_terms) {
            return;
        }

        $this->aggreed_terms = $roomType->aggreed_terms;
        
        if (is_array($this->aggreed_terms) && count($this->aggreed_terms) > 0) {
            $firstTerm = $this->aggreed_terms[0];
            $this->agreementDocumentUrl = $firstTerm['document_url'] ?? $this->agreementDocumentUrl;
            $this->agreementContent = $firstTerm['content'] ?? $this->agreementContent;
            // initialize checkbox states for all terms
            $this->agreeDocs = [];
            foreach ($this->aggreed_terms as $idx => $t) {
                $this->agreeDocs[$idx] = false;
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

    public function checkAvailability()
    {
        if ($this->roomService === null) {
            $this->roomService = new RoomService();
        }
        // reset inline message
        $this->availabilityMessage = null;
        $this->availabilityType = null;
        $this->availabilityDetails = null;
        $this->quote = null;
        if (empty($this->selectedPets)) {
            $this->dispatch('notify', message: 'Please select at least one pet', type: 'error');
            $this->availabilityMessage = 'Please select at least one pet';
            $this->availabilityType = 'error';
            return;
        }
        if (!$this->room_type_id) {
            $this->dispatch('notify', message: 'Room type is not selected', type: 'error');
            $this->availabilityMessage = 'Room type is not selected';
            $this->availabilityType = 'error';
            return;
        }
        $this->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        $resp = $this->roomService->checkRoomAvailability($this->room_type_id, $this->start_date, $this->end_date, $this->selectedPets);
        if (!is_array($resp)) {
            $this->dispatch('notify', message: 'Unable to check availability', type: 'error');
            $this->availabilityMessage = 'Unable to check availability';
            $this->availabilityType = 'error';
            return;
        }

        $status = (bool)($resp['status'] ?? false);
        $message = (string)($resp['message'] ?? ($status ? 'Room is available' : 'Room is not available for the selected dates'));
        $this->availabilityDetails = [
            'available_rooms' => (int)($resp['available_rooms'] ?? 0),
            'pet_size_availability' => $resp['pet_size_availability'] ?? [],
            'selected_pet_size_counts' => $resp['selected_pet_size_counts'] ?? [],
            'selected_sizes_ok_by_size' => $resp['selected_sizes_ok_by_size'] ?? [],
            'price_variation' => (float)($resp['price_variation'] ?? 0),
        ];

        if (!$status) {
            $this->dispatch('notify', message: $message, type: 'error');
            $this->availabilityMessage = $message;
            $this->availabilityType = 'error';
            return;
        }

        $this->dispatch('notify', message: $message, type: 'success');
        $this->availabilityMessage = $message;
        $this->availabilityType = 'success';

        // Build pricing summary based on selected pet sizes and number of days
        $days = 1;
        try {
            $sd = Carbon::parse($this->start_date);
            $ed = Carbon::parse($this->end_date);
            $diff = $sd && $ed ? $sd->diffInDays($ed) : 0;
            $days = max(1, (int)$diff);
        } catch (\Throwable $e) {
            $days = 1;
        }
        $selectedPets = Pet::whereIn('id', $this->selectedPets)->get(['id','name','pet_size_id']);
        $selectedSizeIds = $selectedPets->pluck('pet_size_id')->filter()->map(fn($v) => (int)$v)->toArray();
        $baseSubtotal = 0.0; // before variation
        $finalSubtotal = 0.0; // after variation
        $variationTotal = 0.0;
        $petLines = [];
        if (!empty($selectedSizeIds)) {
            $prices = RoomPriceOptionModel::where('room_type_id', (int)$this->room_type_id)
                ->whereIn('pet_size_id', $selectedSizeIds)
                ->where('no_of_days', $days)
                ->get(['pet_size_id','price']);
            // Fallback set for 1-day price if exact days not configured
            $prices1d = collect();
            if ($days !== 1) {
                $prices1d = RoomPriceOptionModel::where('room_type_id', (int)$this->room_type_id)
                    ->whereIn('pet_size_id', $selectedSizeIds)
                    ->where('no_of_days', 1)
                    ->get(['pet_size_id','price']);
            }
            $sizeMap = Size::whereIn('id', $selectedSizeIds)->get(['id','name'])->keyBy('id');
            $ppv = (float)($resp['price_variation'] ?? ($resp['peak_price_variation'] ?? 0));
            foreach ($selectedPets as $pet) {
                $sid = (int)$pet->pet_size_id;
                $sizeName = optional($sizeMap->get($sid))->name;
                $row = $prices->firstWhere('pet_size_id', $sid);
                $base = (float)($row->price ?? 0);
                if ($base <= 0 && $days > 1 && $prices1d->isNotEmpty()) {
                    $row1 = $prices1d->firstWhere('pet_size_id', $sid);
                    $base = (float)($row1->price ?? 0) * $days;
                }
                // Additional fallbacks if still zero: use any price for room_type
                if ($base <= 0) {
                    $rowDays = RoomPriceOptionModel::where('room_type_id', (int)$this->room_type_id)
                        ->where('no_of_days', $days)
                        ->orderByDesc('price')
                        ->first(['price']);
                    if ($rowDays && (float)$rowDays->price > 0) {
                        $base = (float)$rowDays->price;
                    }
                }
                if ($base <= 0) {
                    $rowAny = RoomPriceOptionModel::where('room_type_id', (int)$this->room_type_id)
                        ->orderByDesc('no_of_days')
                        ->orderByDesc('price')
                        ->first(['price','no_of_days']);
                    if ($rowAny && (float)$rowAny->price > 0) {
                        // Scale proportionally by days if we have a 1-day price; otherwise use as-is
                        $base = (int)($rowAny->no_of_days ?? 1) === 1 ? ((float)$rowAny->price * $days) : (float)$rowAny->price;
                    }
                }
                $final = $base;
                if ($ppv > 0 && $base > 0) {
                    $final = $base + (($base * $ppv) / 100.0);
                }
                $baseSubtotal += $base;
                $finalSubtotal += $final;
                $variationTotal += max(0, $final - $base);
                $petLines[] = [
                    'pet_id' => (int)$pet->id,
                    'pet_name' => (string)$pet->name,
                    'pet_size_id' => $sid,
                    'pet_size_name' => $sizeName,
                    'base_price' => $base,
                    'variation_percent' => $ppv,
                    'variation_amount' => max(0, $final - $base),
                    'final_price' => $final,
                ];
            }
        }
        $addonsTotal = 0.0;
        $addonLines = [];
        foreach ($this->selectedAddons as $ad) {
            $line = ((float)($ad['price'] ?? 0)) * ((int)($ad['qty'] ?? 1));
            $addonsTotal += $line;
            $addonLines[] = [
                'name' => $ad['name'] ?? 'Addon',
                'qty' => (int)($ad['qty'] ?? 1),
                'price' => (float)($ad['price'] ?? 0),
                'total' => $line,
            ];
        }
        $roomName = optional(RoomTypeModel::find($this->room_type_id))->name;
        $this->quote = [
            'room_name' => $roomName,
            'pet_quantity' => count($this->selectedPets ?? []),
            'days' => $days,
            'base_total' => $baseSubtotal,
            'variation_total' => $variationTotal,
            'final_subtotal' => $finalSubtotal,
            'addons_total' => $addonsTotal,
            'total' => $finalSubtotal + $addonsTotal,
            'pet_lines' => $petLines,
            'addon_lines' => $addonLines,
        ];
    }
    public function render()
    {
        return view('livewire.frontend.room.pop-book');
    }
}