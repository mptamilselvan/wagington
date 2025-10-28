<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Catalog;
use App\Models\Attribute;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAddon;
use App\Models\CartItem;
use App\Models\CartAddon;
use App\Models\GuestCartItem;
use App\Models\GuestCartAddon;
use App\Models\VariantAttributeType;
use App\Models\VariantAttributeValue;
use App\Services\ImageService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use App\Models\RoomTypeModel;

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
    // -------------------------
    // Landing
    // -------------------------
    public function getLandingRoomTypes($species_id = null, int $perPage = 10, ?string $q = null)
    {
        // Get room types that have at least one room associated with them
        $roomTypesQuery = \App\Models\RoomTypeModel::query()
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

    public function getRoomBySlug(string $slug)
    {
        $roomType = RoomTypeModel::with('species','roomPriceOptions','petsizeLimits','rooms')->where('slug', $slug)->first();
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

}