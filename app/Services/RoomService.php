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

}