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
use App\Models\Room\RoomTypeModel;

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
   

}