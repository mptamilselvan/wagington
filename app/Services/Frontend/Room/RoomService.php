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
use App\Models\Pet;

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
    public function checkRoomAvailability($room_type_id, $check_in_date, $check_out_date)
    {
        $roomType = RoomTypeModel::find($room_type_id);
        if (!$roomType) {
            return false;
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
            return false;
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
            ->get(['pets_reserved']);

        // Aggregate reserved counts per pet_size_id
        $reservedCounts = [];
        foreach ($bookings as $bk) {
            if (empty($bk->pets_reserved)) {
                continue;
            }
            $pets = json_decode($bk->pets_reserved, true) ?: [];
            foreach ($pets as $p) {
                if (is_array($p) && isset($p['pet_size_id'])) {
                    $sid = (int)$p['pet_size_id'];
                    $reservedCounts[$sid] = ($reservedCounts[$sid] ?? 0) + 1;
                }
            }
        }

        // Check against limits
        foreach ($limitsMap as $sizeId => $limit) {
            $used = $reservedCounts[$sizeId] ?? 0;
            if ($used >= $limit) {
                return false; // capacity reached for this size
            }
        }

        return true;
    }
}