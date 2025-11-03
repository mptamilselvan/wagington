<?php

namespace App\Livewire\Frontend\Room;
//use App\Services\RoomService;
use App\Models\Room\RoomModel;
use App\Models\Pet;
use App\Models\Product;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Services\ImageService;
use App\Models\Room\RoomTypeModel;
use App\Services\Frontend\Room\RoomService;
use App\Models\Room\RoomPriceOptionModel;
use Carbon\Carbon;
// removed duplicate Pet import
use App\Models\Size;
use App\Models\CartItem;
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
    
    // Cart readiness flag
    public $isReadyForCart = false; // true when availability check succeeds
    
    // Auto-open modal flag (for alternative room type switch)
    public $autoOpenModal = false;

    public function mount($roomTypeId = null)
    {
        $this->room_type_id = $roomTypeId;
        $this->booking = [];
        $this->loadPets();
        $this->loadAddons();
        $this->roomService = new RoomService();
        $this->loadAgreements();
        
        // Restore saved selections from session if they exist
        $this->restoreSelectionsFromSession();
    }

    public function refreshBooking()
    {
        $this->booking = [];
        $this->loadAgreements();
        
        // Restore saved selections from session if they exist (only once, not repeatedly)
        // This will be called from mount() first, so session will be cleared there
        // But we check again here in case refreshBooking is called before mount
        if (Session::has('booking_session')) {
            $this->restoreSelectionsFromSession();
        }
    }
    
    /**
     * Switch to an alternative room type, preserving current selections
     */
    public function switchToAlternativeRoomType($roomTypeSlug, $roomTypeId)
    {
        // Save current selections to session along with the room type ID
        Session::put('booking_session', [
            'selectedPets' => $this->selectedPets,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'selectedAddons' => $this->selectedAddons,
            'room_type_id' => $roomTypeId,
            'auto_open' => true, // Flag to auto-open modal on new page
        ]);
        
        // Redirect to the new room type page with a query parameter to trigger modal open
        $redirectUrl = route('room.details', ['slug' => $roomTypeSlug]);
        return redirect($redirectUrl . '?open_booking=1');
    }
    
    /**
     * Restore selections from session
     */
    private function restoreSelectionsFromSession()
    {
        if (Session::has('booking_session')) {
            $sessionData = Session::get('booking_session');
            
            // Restore room_type_id if present
            if (isset($sessionData['room_type_id'])) {
                $this->room_type_id = $sessionData['room_type_id'];
            }
            
            if (isset($sessionData['selectedPets'])) {
                $this->selectedPets = $sessionData['selectedPets'];
            }
            if (isset($sessionData['start_date'])) {
                $this->start_date = $sessionData['start_date'];
            }
            if (isset($sessionData['end_date'])) {
                $this->end_date = $sessionData['end_date'];
            }
            if (isset($sessionData['selectedAddons'])) {
                $this->selectedAddons = $sessionData['selectedAddons'];
            }
            
            // Set auto-open flag if needed
            if (isset($sessionData['auto_open']) && $sessionData['auto_open']) {
                $this->autoOpenModal = true;
            }
            
            // Clear session data after restoring
            Session::forget('booking_session');
        }
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
            $query = Pet::where('user_id', $user->id)
                ->where('sterilisation_status', true)
                ->whereHas('temperamentHealthEvaluations', function($q) {
                    $q->where('status', 'pass');
                });
            
            $this->totalPets = $query->count();
            
            $this->pets = $query
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

    public function getCanCheckAvailabilityProperty()
    {
        if (empty($this->start_date) || empty($this->end_date)) {
            return false;
        }

        try {
            $start = Carbon::parse($this->start_date);
            $end = Carbon::parse($this->end_date);
            $today = Carbon::today();

            // Both dates must be today or future
            if ($start->lt($today) || $end->lt($today)) {
                return false;
            }

            // End date must be after start date
            if ($end->lte($start)) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getEndDateMinProperty()
    {
        if (empty($this->start_date)) {
            return date('Y-m-d');
        }

        try {
            $start = Carbon::parse($this->start_date);
            $today = Carbon::today();

            // If start_date is in the past, use tomorrow as minimum
            if ($start->lt($today)) {
                return $today->copy()->addDay()->format('Y-m-d');
            }

            // Otherwise, end_date should be start_date + 1 day
            return $start->copy()->addDay()->format('Y-m-d');
        } catch (\Throwable $e) {
            return date('Y-m-d');
        }
    }

    public function updatedStartDate($value)
    {
        // Reset end_date if it's now invalid (before start_date + 1 day)
        if (!empty($this->start_date) && !empty($this->end_date)) {
            try {
                $start = Carbon::parse($this->start_date);
                $end = Carbon::parse($this->end_date);
                $minEndDate = $start->copy()->addDay();

                if ($end->lte($start)) {
                    $this->end_date = $minEndDate->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // Ignore errors
            }
        }
        
        // Clear any existing quote and reset cart readiness when dates change
        $this->quote = null;
        $this->isReadyForCart = false;
        $this->availabilityMessage = null;
        $this->availabilityType = null;
        $this->availabilityDetails = null;
    }

    public function updatedEndDate($value)
    {
        // Clear any existing quote and reset cart readiness when dates change
        $this->quote = null;
        $this->isReadyForCart = false;
        $this->availabilityMessage = null;
        $this->availabilityType = null;
        $this->availabilityDetails = null;
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
        
        // Clear quote, dates, and disable cart button when pet selection changes
        $this->quote = null;
        $this->start_date = null;
        $this->end_date = null;
        $this->isReadyForCart = false;
        $this->availabilityMessage = null;
        $this->availabilityType = null;
        $this->availabilityDetails = null;
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
        $this->isReadyForCart = false; // Disable cart button while checking
        if (empty($this->selectedPets)) {
            $this->dispatch('notify', message: 'Please select at least one pet', type: 'error');
            $this->availabilityMessage = 'Please select at least one pet';
            $this->availabilityType = 'error';
            $this->isReadyForCart = false;
            return;
        }
        if (!$this->room_type_id) {
            $this->dispatch('notify', message: 'Room type is not selected', type: 'error');
            $this->availabilityMessage = 'Room type is not selected';
            $this->availabilityType = 'error';
            $this->isReadyForCart = false;
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
            $this->isReadyForCart = false;
            return;
        }

        $status = (bool)($resp['status'] ?? false);
        $message = (string)($resp['message'] ?? ($status ? 'Room is available' : 'Room is not available for the selected dates'));
        $this->availabilityDetails = [
            'available_rooms' => (int)($resp['available_rooms'] ?? 0),
            'available_room_ids' => $resp['available_room_ids'] ?? [],
            'pet_size_availability' => $resp['pet_size_availability'] ?? [],
            'selected_pet_size_counts' => $resp['selected_pet_size_counts'] ?? [],
            'selected_sizes_ok_by_size' => $resp['selected_sizes_ok_by_size'] ?? [],
            'price_variation' => (float)($resp['price_variation'] ?? 0),
            'alternative_room_types' => $resp['alternative_room_types'] ?? [],
        ];

        if (!$status) {
            $this->dispatch('notify', message: $message, type: 'error');
            $this->availabilityMessage = $message;
            $this->availabilityType = 'error';
            $this->isReadyForCart = false; // Keep cart button disabled on error
            return;
        }

        // Set a concrete room if available
        $firstRoomId = null;
        if (!empty($resp['available_room_ids']) && is_array($resp['available_room_ids'])) {
            $firstRoomId = (int)($resp['available_room_ids'][0] ?? 0);
        }
        if ($firstRoomId > 0) {
            $this->room = \App\Models\Room\RoomModel::find($firstRoomId);
        }

        $this->dispatch('notify', message: $message, type: 'success');
        $this->availabilityMessage = $message;
        $this->availabilityType = 'success';
        $this->isReadyForCart = true; // Enable cart button when availability check succeeds

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

    public function addToCart()
    {
        \Log::info('addToCart method called', [
            'isReadyForCart' => $this->isReadyForCart,
            'hasRoom' => !is_null($this->room),
            'roomId' => $this->room->id ?? null,
            'selectedPetsCount' => count($this->selectedPets ?? []),
        ]);

        if (!$this->isReadyForCart) {
            $this->dispatch('notify', message: 'Please check availability first', type: 'error');
            return;
        }

        $user = Auth::user();
        if (!$user) {
            $this->dispatch('notify', message: 'Please login to add items to cart', type: 'error');
            return;
        }

        // Determine product_id: use only the selected room id
        $roomId = null;
        if ($this->room && $this->room->id) {
            $roomId = (int)$this->room->id;
        }

        if (!$roomId) {
            \Log::warning('Room information missing in addToCart', [
                'room' => $this->room,
                'room_type_id' => $this->room_type_id,
            ]);
            $this->dispatch('notify', message: 'Room information is missing. Please refresh and try again.', type: 'error');
            return;
        }

        if (empty($this->selectedPets)) {
            $this->dispatch('notify', message: 'Please select at least one pet', type: 'error');
            return;
        }

        $quantity = count($this->selectedPets);
        $catalogId = 2;

        \Log::info('Adding room to cart', [
            'user_id' => $user->id,
            'room_id' => $roomId,
            'catalog_id' => $catalogId,
            'quantity' => $quantity,
        ]);

        try {
            // Check if there is already a room booking cart item for this user (product_id NULL for rooms)
            $existingCartItem = CartItem::where('user_id', $user->id)
                ->whereNull('product_id')
                ->where('catalog_id', $catalogId)
                ->whereNull('variant_id')
                ->first();

            $cartItem = null;
            if ($existingCartItem) {
                // Update existing cart item quantity and expiration
                $existingCartItem->quantity = $quantity;
                $existingCartItem->expires_at = now()->addMinutes((int) config('sku.cart_reserve_minutes', 60));
                $existingCartItem->save();
                $cartItem = $existingCartItem;
            } else {
                // Check if there's any other room booking (null variant_id) that needs to be replaced
                // Note: Due to unique constraint on (user_id, variant_key), only one item can exist with null variant_id per user
                $otherRoomItem = CartItem::where('user_id', $user->id)
                    ->whereNull('product_id')
                    ->whereNull('variant_id')
                    ->where('catalog_id', $catalogId)
                    ->first();

                if ($otherRoomItem) {
                    // Update existing room booking to new room
                    $otherRoomItem->quantity = $quantity;
                    $otherRoomItem->expires_at = now()->addMinutes((int) config('sku.cart_reserve_minutes', 60));
                    $otherRoomItem->save();
                    $cartItem = $otherRoomItem;
                } else {
                    // Create new cart item
                    $cartItem = CartItem::create([
                        'user_id' => $user->id,
                        'catalog_id' => $catalogId,
                        'product_id' => null, // keep product_id NULL for room bookings to satisfy FK
                        'variant_id' => null,
                        'quantity' => $quantity,
                        'availability_status' => 'in_stock',
                        'expires_at' => now()->addMinutes((int) config('sku.cart_reserve_minutes', 60)),
                    ]);
                }
            }

            \Log::info('Room successfully added to cart', [
                'user_id' => $user->id,
                'cart_item_id' => $existingCartItem->id ?? ($otherRoomItem->id ?? 'new'),
            ]);

            // Persist cart room details
            try {
                $days = 1;
                try {
                    $sd = Carbon::parse($this->start_date);
                    $ed = Carbon::parse($this->end_date);
                    $diff = $sd && $ed ? $sd->diffInDays($ed) : 0;
                    $days = max(1, (int)$diff);
                } catch (\Throwable $e) { $days = 1; }

                $petQty = count($this->selectedPets ?? []);
                $addonsPrice = 0.0;
                foreach ($this->selectedAddons as $ad) {
                    $addonsPrice += ((float)($ad['price'] ?? 0)) * ((int)($ad['qty'] ?? 1));
                }
                // Derive per-pet room price from quote if present
                $perPetPrice = 0.0;
                $totalRoomFinal = 0.0;
                if (is_array($this->quote)) {
                    $totalRoomFinal = (float)($this->quote['final_subtotal'] ?? 0);
                    if ($petQty > 0) {
                        $perPetPrice = $totalRoomFinal / $petQty;
                    }
                }

                // Build pets_reserved payload with pet size id and name
                $petsReserved = [];
                try {
                    $selectedPetModels = Pet::whereIn('id', $this->selectedPets)->get(['id','pet_size_id','name']);
                    $sizeIds = $selectedPetModels->pluck('pet_size_id')->filter()->map(fn($v)=>(int)$v)->unique()->values()->all();
                    $sizesMap = Size::whereIn('id', $sizeIds)->get(['id','name'])->keyBy('id');
                    foreach ($selectedPetModels as $p) {
                        $sid = (int)($p->pet_size_id ?? 0);
                        $petsReserved[] = [
                            'pet_id' => (int)$p->id,
                            'pet_name' => (string)($p->name ?? ''),
                            'pet_size_id' => $sid,
                            'pet_size_name' => $sizesMap->get($sid)->name ?? null,
                        ];
                    }
                } catch (\Throwable $e) {
                    // fallback to basic pet ids
                    $petsReserved = array_map(fn($pid)=>[ 'pet_id' => (int)$pid ], $this->selectedPets ?? []);
                }

                // Save or update details record for this cart item
                if ($cartItem) {
                    $roomModel = \App\Models\Room\RoomModel::find($roomId);
                    \App\Models\CartRoomDetail::updateOrCreate(
                        [ 'cart_item_id' => $cartItem->id ],
                        [
                            'room_id' => (int)$roomId,
                            'room_type_id' => (int)($roomModel->room_type_id ?? 0),
                            'customer_id' => (int)$user->id,
                            'pets_reserved' => $petsReserved,
                            'service_addons' => array_values($this->selectedAddons ?? []),
                            'check_in_date' => $this->start_date,
                            'check_out_date' => $this->end_date,
                            'no_of_days' => $days,
                            'room_price' => $perPetPrice,
                            'addons_price' => $addonsPrice,
                            'service_charge' => 0.0,
                            'pet_quantity' => $petQty,
                            'total_price' => $totalRoomFinal + $addonsPrice,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                \Log::error('Error saving cart_room_details', [ 'error' => $e->getMessage() ]);
            }

            // Optional toast only; avoid triggering mini-cart drawer
            $this->dispatch('notify', message: 'Room booking added to cart successfully', type: 'success');
            
            // Redirect to cart page
            return redirect()->route('shop.cart');
            
        } catch (\Throwable $e) {
            \Log::error('Error adding room to cart', [
                'user_id' => $user->id ?? null,
                'room_id' => $roomId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', message: 'Failed to add room to cart: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.frontend.room.pop-book');
    }
}