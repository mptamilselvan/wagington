<?php

namespace App\Services\Frontend\Room;


use App\Models\Catalog;
use App\Models\CartItem;
use App\Models\CartAddon;
use App\Models\GuestCartItem;
use App\Models\GuestCartAddon;
use App\Services\ImageService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Room\RoomTypeModel;
use App\Models\Room\RoomBookingModel;
use App\Models\Room\RoomModel;
use App\Models\Room\PetSizeLimitModel;
use App\Models\Room\PeakSeasonModel;
use App\Models\Room\RoomWeekendModel;
use Carbon\Carbon;
use App\Models\Pet;
use App\Models\Size;

class RoomService
{
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Helper to get current authenticated user from web or api guard
    private function getCurrentUser()
    {
        return Auth::user() ?? Auth::guard('api')->user();
    }

    // Helper to check if authenticated in web or api
    private function isAuthenticated(): bool
    {
        return Auth::check() || Auth::guard('api')->check();
    }
     public function getLandingRoomTypes($species_id = null, int $perPage = 10, ?string $q = null)
    {
        // Get room types that have at least one room associated with them
        $roomTypesQuery = RoomTypeModel::query()
            ->with('species','roomPriceOptions','petsizeLimits','rooms')
            ->whereHas('rooms') // Only room types that have associated rooms
            ->orderBy('id','asc')
            ->orderBy('name');
        if ($species_id !== null) {
            $roomTypesQuery->where('species_id', $species_id);
        }
        if ($q !== null && trim($q) !== '') {
            $sq = trim($q);
            // Escape SQL wildcards
            $sq = str_replace(['%', '_'], ['\\%', '\\_'], $sq);
            $roomTypesQuery->where(function(Builder $qb) use ($sq) {
                $qb->where('name', 'like', "%{$sq}%")
                   ->orWhere('species.name', 'like', "%{$sq}%")
                   ->orWhere('roomPriceOptions.label', 'like', "%{$sq}%")
                   ->orWhere('petsizeLimits.allowed_pet_size', 'like', "%{$sq}%");
            });
         }
        $roomTypes = $roomTypesQuery->take($perPage)->get();
        
        return $roomTypes;
    }

    public function getRoomTypeBySlug(string $slug)
    {
        $roomType = RoomTypeModel::with('species','roomPriceOptions','petsizeLimits','rooms')->where('slug', $slug)->firstOrFail();
        if (!$roomType) {
            return null;
        }
        $roomType->rooms = $roomType->rooms->map(function($room) {
            $room->roomPriceOptions = $room->roomPriceOptions->map(function($roomPriceOption) {
                return $roomPriceOption->price;
            });
            $room->petsizeLimits = $room->petsizeLimits->map(function($petsizeLimit) {
                return $petsizeLimit->allowed_pet_size;
            });
            return $room;
        });
        return $roomType;
    }

    public function getRelatedRoomTypes(RoomTypeModel $currentRoom, int $limit = 5, int $offset = 0)
    {
        $relatedRooms = collect();
        
        // Get current room's attributes and amenities for matching
        $currentAttributes = $currentRoom->room_attributes ?? [];
        $currentAmenities = $currentRoom->room_amenities ?? [];
        $currentPrice = $currentRoom->roomPriceOptions->first()?->price ?? 0;
        $priceRange = $currentPrice * 0.3; // 30% price range tolerance
        
        // First priority: Same species with matching attributes/amenities
        $sameSpeciesWithMatches = RoomTypeModel::with('species', 'rooms', 'roomPriceOptions')
            ->where('species_id', $currentRoom->species_id)
            ->where('id', '!=', $currentRoom->id)
            ->whereHas('rooms')
            ->get()
            ->filter(function($room) use ($currentAttributes, $currentAmenities, $currentPrice, $priceRange) {
                $roomPrice = $room->roomPriceOptions->first()?->price ?? 0;
                $priceMatch = abs($roomPrice - $currentPrice) <= $priceRange;
                
                $attributesMatch = $this->calculateMatchScore($currentAttributes, $room->room_attributes ?? []);
                $amenitiesMatch = $this->calculateMatchScore($currentAmenities, $room->room_amenities ?? []);
                
                return $priceMatch && ($attributesMatch > 0 || $amenitiesMatch > 0);
            })
            ->sortByDesc(function($room) use ($currentAttributes, $currentAmenities) {
                $attributesMatch = $this->calculateMatchScore($currentAttributes, $room->room_attributes ?? []);
                $amenitiesMatch = $this->calculateMatchScore($currentAmenities, $room->room_amenities ?? []);
                return $attributesMatch + $amenitiesMatch;
            });

        $relatedRooms = $relatedRooms->merge($sameSpeciesWithMatches);

        // Second priority: Same species with price match (if we need more)
        if ($relatedRooms->count() < ($offset + $limit)) {
            $sameSpeciesPriceMatch = RoomTypeModel::with('species', 'rooms', 'roomPriceOptions')
                ->where('species_id', $currentRoom->species_id)
                ->where('id', '!=', $currentRoom->id)
                ->whereNotIn('id', $relatedRooms->pluck('id'))
                ->whereHas('rooms')
                ->get()
                ->filter(function($room) use ($currentPrice, $priceRange) {
                    $roomPrice = $room->roomPriceOptions->first()?->price ?? 0;
                    return abs($roomPrice - $currentPrice) <= $priceRange;
                });

            $relatedRooms = $relatedRooms->merge($sameSpeciesPriceMatch);
        }

        // Third priority: Different species with matching attributes/amenities (if we still need more)
        if ($relatedRooms->count() < ($offset + $limit)) {
            $differentSpeciesWithMatches = RoomTypeModel::with('species', 'rooms', 'roomPriceOptions')
                ->where('species_id', '!=', $currentRoom->species_id)
                ->where('id', '!=', $currentRoom->id)
                ->whereNotIn('id', $relatedRooms->pluck('id'))
                ->whereHas('rooms')
                ->get()
                ->filter(function($room) use ($currentAttributes, $currentAmenities, $currentPrice, $priceRange) {
                    $roomPrice = $room->roomPriceOptions->first()?->price ?? 0;
                    $priceMatch = abs($roomPrice - $currentPrice) <= $priceRange;
                    
                    $attributesMatch = $this->calculateMatchScore($currentAttributes, $room->room_attributes ?? []);
                    $amenitiesMatch = $this->calculateMatchScore($currentAmenities, $room->room_amenities ?? []);
                    
                    return $priceMatch && ($attributesMatch > 0 || $amenitiesMatch > 0);
                })
                ->sortByDesc(function($room) use ($currentAttributes, $currentAmenities) {
                    $attributesMatch = $this->calculateMatchScore($currentAttributes, $room->room_attributes ?? []);
                    $amenitiesMatch = $this->calculateMatchScore($currentAmenities, $room->room_amenities ?? []);
                    return $attributesMatch + $amenitiesMatch;
                });

            $relatedRooms = $relatedRooms->merge($differentSpeciesWithMatches);
        }

        // Fourth priority: Any other room types (if we still need more)
        if ($relatedRooms->count() < ($offset + $limit)) {
            $otherRooms = RoomTypeModel::with('species', 'rooms', 'roomPriceOptions')
                ->where('id', '!=', $currentRoom->id)
                ->whereNotIn('id', $relatedRooms->pluck('id'))
                ->whereHas('rooms')
                ->get();
            
            $relatedRooms = $relatedRooms->merge($otherRooms);
        }

        // Apply pagination
        return $relatedRooms->slice($offset, $limit)->values();
    }

    /**
     * Calculate match score between two arrays of attributes/amenities
     */
    private function calculateMatchScore($array1, $array2)
    {
        if (empty($array1) || empty($array2)) {
            return 0;
        }

        $matches = 0;
        foreach ($array1 as $item1) {
            foreach ($array2 as $item2) {
                if (strtolower(trim($item1)) === strtolower(trim($item2))) {
                    $matches++;
                    break;
                }
            }
        }

        return $matches;
    }
    public function checkRoomAvailability($room_type_id, $check_in_date, $check_out_date, array $selected_pet_ids)
    {
        if (empty($selected_pet_ids)) {
            return [
                'status' => false,
                'message' => 'Please select at least one pet to check availability',
            ];
        }
        $roomType = RoomTypeModel::find($room_type_id);
        if (!$roomType) {
            return ['status' => false, 'message' => 'Room type not found'];
        }

        // Get pet size limits for this room type
        $limitsMap = [];
        $limitRow = PetSizeLimitModel::where('room_type_id', $room_type_id)->first();
        if ($limitRow && !empty($limitRow->allowed_pet_size)) {
            $allowed = json_decode($limitRow->allowed_pet_size, true) ?: [];
            foreach ($allowed as $entry) {
                // New format
                if (is_array($entry) && isset($entry['pet_size_id']) && isset($entry['limit'])) {
                    $limitsMap[(int)$entry['pet_size_id']] = (int)$entry['limit'];
                    continue;
                }
                // Legacy format: [{"12":2}]
                if (is_array($entry)) {
                    foreach ($entry as $sizeId => $limit) {
                        $limitsMap[(int)$sizeId] = (int)$limit;
                    }
                }
            }
        }

        if (empty($limitsMap)) {
            // No limits configured means not available (or unlimited). Choose conservative: not available
            return ['status' => false, 'message' => 'No limits configured'];
        }

        // Find bookings that overlap with the requested window
        $bookings = RoomBookingModel::where('room_type_id', $room_type_id)
            ->where(function ($q) use ($check_in_date, $check_out_date) {
                $q->whereBetween('check_in_date', [$check_in_date, $check_out_date])
                  ->orWhereBetween('check_out_date', [$check_in_date, $check_out_date])
                  ->orWhere(function ($qq) use ($check_in_date, $check_out_date) {
                      $qq->where('check_in_date', '<=', $check_in_date)
                         ->where('check_out_date', '>=', $check_out_date);
                  });
            })
            ->get(['pets_reserved','id','room_id']);
        // Collect reserved room ids, filter out nulls
        $reservedRoomIds = collect($bookings)->pluck('room_id')->filter(fn($id) => !is_null($id))->unique()->values();
        
        // Count rooms for this room type
        $roomsQuery = RoomModel::where('room_type_id', $room_type_id);
        $totalRoomsCount = (int) $roomsQuery->count();
        
        // Available rooms for this room type in the requested window (only if rooms are defined)
        
        // Aggregate reserved counts per pet_size_id and per room
        $reservedCounts = [];
        $reservedCountsByRoom = [];
        foreach ($bookings as $bk) {
            if (empty($bk->pets_reserved)) {
                continue;
            }
            // pets_reserved may already be an array due to model casts
            $pets = is_string($bk->pets_reserved)
                ? (json_decode($bk->pets_reserved, true) ?: [])
                : (is_array($bk->pets_reserved) ? $bk->pets_reserved : []);
            foreach ($pets as $p) {
                if (is_array($p) && isset($p['pet_size_id'])) {
                    $sid = (int)$p['pet_size_id'];
                    $reservedCounts[$sid] = ($reservedCounts[$sid] ?? 0) + 1;
                    $rid = $bk->room_id;
                    if (!is_null($rid)) {
                        if (!isset($reservedCountsByRoom[$rid])) {
                            $reservedCountsByRoom[$rid] = [];
                        }
                        $reservedCountsByRoom[$rid][$sid] = ($reservedCountsByRoom[$rid][$sid] ?? 0) + 1;
                    }
                }
            }
        }

        // Fetch pet size names for all size IDs
        $sizeIds = array_keys($limitsMap);
        $sizes = Size::whereIn('id', $sizeIds)->get()->keyBy('id');
        
        // Build per-size availability details (overall)
        $petSizeAvailability = [];
        $hasAnySizeCapacity = false;
        // If specific pets are selected, only consider their sizes for display and capacity checks
        $selectedSizeSet = [];
        if (!empty($selected_pet_ids)) {
            $selectedSizeSet = array_fill_keys(
                Pet::whereIn('id', $selected_pet_ids)->pluck('pet_size_id')->filter()->map(fn($v) => (int)$v)->toArray(),
                true
            );
        }
        foreach ($limitsMap as $sizeId => $limit) {
            if (!empty($selectedSizeSet) && !isset($selectedSizeSet[(int)$sizeId])) {
                continue;
            }
            $used = $reservedCounts[$sizeId] ?? 0;
            $remaining = max(0, (int)$limit - (int)$used);
            if ($remaining > 0) {
                $hasAnySizeCapacity = true;
            }
            $size = $sizes->get($sizeId);
            $petSizeAvailability[] = [
                'pet_size_id' => (int)$sizeId,
                'pet_size_name' => $size ? $size->name : 'Unknown',
                'limit' => (int)$limit,
                'used' => (int)$used,
                'remaining' => (int)$remaining,
            ];
        }

        // If specific pets are selected, compute if their sizes can be accommodated
        $selectedPetSizeCounts = [];
        $selectedSizesOkBySize = [];
        $selectedAllSizesOk = true;
        if (!empty($selected_pet_ids)) {
            $selectedSizeIds = Pet::whereIn('id', $selected_pet_ids)->pluck('pet_size_id')->filter()->map(fn($v) => (int)$v)->toArray();
            foreach ($selectedSizeIds as $sid) {
                $selectedPetSizeCounts[$sid] = ($selectedPetSizeCounts[$sid] ?? 0) + 1;
            }

            \Log::info("selectedPetSizeCounts: " . json_encode($selectedPetSizeCounts));
            // Build a quick lookup for remaining per size
            $remainingBySize = [];
            foreach ($petSizeAvailability as $row) {
                $remainingBySize[(int)$row['pet_size_id']] = (int)$row['remaining'];
            }
            foreach ($selectedPetSizeCounts as $sid => $need) {
                $remaining = $remainingBySize[$sid] ?? 0;
                $ok = $remaining >= $need;
                $selectedSizesOkBySize[(int)$sid] = [
                    'needed' => (int)$need,
                    'remaining' => (int)$remaining,
                    'ok' => $ok,
                ];
                if (!$ok) {
                    $selectedAllSizesOk = false;
                }
            }
        }
        $availableRoomsCount = 0;
        // Build per-room per-size availability details
        $petSizeAvailabilityByRoom = [];
        if ($totalRoomsCount > 0) {
            $roomIds = (clone $roomsQuery)->where('status', RoomModel::STATUS_AVAILABLE)->pluck('id');
            $availableRoomIds = [];
            foreach ($roomIds as $rid) {
                $sizesForRoom = [];
                foreach ($limitsMap as $sizeId => $limit) {
                    if (!empty($selectedSizeSet) && !isset($selectedSizeSet[(int)$sizeId])) {
                        continue;
                    }
                    $size = $sizes->get($sizeId);
                    $usedForRoom = $reservedCountsByRoom[$rid][$sizeId] ?? 0;
                    $remainingForRoom = max(0, (int)$limit - (int)$usedForRoom);
                    $sizesForRoom[] = [
                        'pet_size_id' => (int)$sizeId,
                        'pet_size_name' => $size ? $size->name : 'Unknown',
                        'limit' => (int)$limit,
                        'used' => (int)$usedForRoom,
                        'remaining' => (int)$remainingForRoom,
                    ];
                }
                $petSizeAvailabilityByRoom[] = [
                    'room_id' => (int)$rid,
                    'sizes' => $sizesForRoom,
                ];
                // Mark room available if any selected size has remaining > 0
                foreach ($sizesForRoom as $row) {
                    if (($row['remaining'] ?? 0) > 0) {
                        $availableRoomIds[(int)$rid] = true;
                        break;
                    }
                }
            }
            // Add rooms with no bookings and available status
            $availableNotBookedIds = $roomsQuery->whereNotIn('id', $reservedRoomIds)
                                ->where('status', '=', RoomModel::STATUS_AVAILABLE)
                                ->where('room_type_id', '=', $room_type_id)
                                ->pluck('id')
                                ->toArray();
            foreach ($availableNotBookedIds as $rid) {
                $availableRoomIds[(int)$rid] = true;
            }
            $availableRoomsCount = count($availableRoomIds);
        }
        \Log::info("booked rooms: " . $bookings->count());
        if ($totalRoomsCount > 0) {
            // already accounted available rooms in $availableRoomIds
        }
        // Recompute per-size availability aggregating across available rooms only
        if ($totalRoomsCount > 0) {
            $availableIdsArray = array_map('intval', array_keys($availableRoomIds ?? []));
            $petSizeAvailability = [];
            $hasAnySizeCapacity = false;
            foreach ($limitsMap as $sizeId => $limit) {
                if (!empty($selectedSizeSet) && !isset($selectedSizeSet[(int)$sizeId])) {
                    continue;
                }
                $totalLimit = (int)$limit * max(1, (int)$availableRoomsCount);
                $usedSum = 0;
                foreach ($availableIdsArray as $rid) {
                    $usedSum += (int)($reservedCountsByRoom[$rid][$sizeId] ?? 0);
                }
                $remaining = max(0, $totalLimit - $usedSum);
                if ($remaining > 0) {
                    $hasAnySizeCapacity = true;
                }
                $size = $sizes->get($sizeId);
                $petSizeAvailability[] = [
                    'pet_size_id' => (int)$sizeId,
                    'pet_size_name' => $size ? $size->name : 'Unknown',
                    'limit' => (int)$totalLimit,
                    'used' => (int)$usedSum,
                    'remaining' => (int)$remaining,
                ];
            }
        }
        // Determine overall availability
        // If specific pets are selected, require capacity for those sizes
        // If no physical rooms are defined for the room type, rely solely on pet-size capacity
        $capacityOk = !empty($selected_pet_ids) ? $selectedAllSizesOk : $hasAnySizeCapacity;
        //$status = (($totalRoomsCount === 0) || ($availableRoomsCount > 0)) && $capacityOk;
        $status = $availableRoomsCount > 0;
        //$message = $status ? 'Room is available' : 'Room is not available for the selected dates';
        $message = $availableRoomsCount>0 ? 'Room is available' : 'Room is not available for the selected dates';

        \Log::info("selectedAllSizesOk: " . $selectedAllSizesOk);
        \Log::info("hasAnySizeCapacity: " . $hasAnySizeCapacity);
        \Log::info("capacityOk: " . $capacityOk);
        \Log::info("availableRoomsCount: " . $availableRoomsCount);
        \Log::info("petSizeAvailability: " . json_encode($petSizeAvailability));
        \Log::info("reservedCountsByRoom: " . json_encode($reservedCountsByRoom));
        \Log::info("petSizeAvailabilityByRoom: " . json_encode($petSizeAvailabilityByRoom));

        // Determine peak season price variation overlapping the requested dates
        $peakVariation = 0.0;
        $weekendVariation = 0.0;
        try {
            $overlap = PeakSeasonModel::where(function($q) use ($check_in_date, $check_out_date) {
                    $q->whereBetween('start_date', [$check_in_date, $check_out_date])
                      ->orWhereBetween('end_date', [$check_in_date, $check_out_date])
                      ->orWhere(function($qq) use ($check_in_date, $check_out_date) {
                          $qq->where('start_date', '<=', $check_in_date)
                             ->where('end_date', '>=', $check_out_date);
                      });
                })
                ->orderByDesc('peak_price_variation')
                ->first();
            if ($overlap && $overlap->peak_price_variation !== null) {
                $peakVariation = (float)$overlap->peak_price_variation;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // If not peak season, and any date falls on weekend, fetch weekend variation
        if ($peakVariation <= 0) {
            try {
                $start = Carbon::parse($check_in_date);
                $end = Carbon::parse($check_out_date);
                $hasWeekend = false;
                for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                    if ($d->isSaturday() || $d->isSunday()) {
                        $hasWeekend = true;
                        break;
                    }
                }
                if ($hasWeekend) {
                    $rw = RoomWeekendModel::first();
                    if ($rw && $rw->weekend_price_variation !== null) {
                        $weekendVariation = (float)$rw->weekend_price_variation;
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return [
            'status' => $status,
            'message' => $message,
            'available_rooms' => $availableRoomsCount,
            'pet_size_availability' => $petSizeAvailability,
            'reserved_counts_by_room' => $reservedCountsByRoom,
            'pet_size_availability_by_room' => $petSizeAvailabilityByRoom,
            'selected_pet_size_counts' => $selectedPetSizeCounts,
            'selected_sizes_ok_by_size' => $selectedSizesOkBySize,
            'peak_price_variation' => $peakVariation,
            'weekend_price_variation' => $weekendVariation,
            'price_variation' => $peakVariation > 0 ? $peakVariation : $weekendVariation,
        ];
    }
}