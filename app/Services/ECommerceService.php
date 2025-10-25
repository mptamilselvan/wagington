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

class ECommerceService
{
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Build a human-readable name from variant attributes (e.g., "Black - Large")
    public static function formatVariantName(array $attrs): string
    {
        if (empty($attrs)) return '';
        // Preserve attribute type order if configured; else natural key order
        $ordered = $attrs;
        return implode(' - ', array_values($ordered));
    }

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
    public function getLandingSections(int $perCategory = 8, ?string $q = null): array
    {
        // Get parent categories first (no parent_id), then child categories
        // This helps avoid duplicates by prioritizing parent categories
        $categories = \App\Models\Category::query()
            ->orderBy('display_order')
            ->orderBy('name')
            ->whereHas('products', function($q) {
                $q->where('status', 'published')
                  ->where('product_type', '!=', 'addon');
            })
            ->get();

        $sections = [];
        $usedProductIds = []; // Track products already included to avoid duplicates
        
        foreach ($categories as $category) {
            $productsQ = $this->baseProductQuery()
                // filter by related category id (belongsToMany categories)
                ->whereHas('categories', function(Builder $q2) use ($category) {
                    $q2->where('categories.id', $category->id);
                })
                // Exclude products already used in previous categories
                ->whereNotIn('id', $usedProductIds);

            if ($q !== null && trim($q) !== '') {
                $sq = trim($q);
                // Escape SQL wildcards
                $sq = str_replace(['%', '_'], ['\\%', '\\_'], $sq);
                $productsQ->where(function(Builder $qb) use ($sq) {
                    $qb->where('name', 'like', "%{$sq}%")
                       ->orWhere('slug', 'like', "%{$sq}%")
                       ->orWhere('description', 'like', "%{$sq}%");
                });
            }

            $products = $productsQ->with($this->cardWithRelations())->take($perCategory)->get();

            // Only add section if there are products (not already used elsewhere)
            if ($products->isNotEmpty()) {
                // Track these product IDs as used
                $usedProductIds = array_merge($usedProductIds, $products->pluck('id')->toArray());
                $categoryData = $category->makeHidden(['created_at', 'updated_at', 'deleted_at'])->toArray();

                $sections[] = [
                    'category' => $categoryData,
                    'products' => $products->map(fn($p) => $this->presentCard($p))->all(),
                ];
            }
        }

        return $sections;
    }

    // -------------------------
    // Listing + Filters
    // -------------------------
    public function listProducts(array $params): LengthAwarePaginator
    {
        $q             = trim((string) Arr::get($params, 'q', ''));
        $categoryId    = Arr::get($params, 'category_id');
        $shippableFilter = Arr::get($params, 'shippable'); // 'true', 'false', or null
        // Normalize price inputs (no auto-swap; treat independently)
        $priceMinRaw   = Arr::get($params, 'price_min');
        $priceMaxRaw   = Arr::get($params, 'price_max');
        $priceMin      = is_numeric($priceMinRaw) ? (float) $priceMinRaw : null;
        $priceMax      = is_numeric($priceMaxRaw) ? (float) $priceMaxRaw : null;
        $attrs         = Arr::get($params, 'attrs', []); // [Color=>[Red,Blue], Size=>[S,M]]
        $sort          = Arr::get($params, 'sort', 'newest');
        $perPage       = (int) (Arr::get($params, 'per_page', 12));

        // DEBUG params
        Log::info('listProducts params', [
            'q' => $q,
            'category_id' => $categoryId,
            'shippable' => $shippableFilter,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'attrs' => $attrs,
            'sort' => $sort,
        ]);

        $query = $this->baseProductQuery();

        if ($categoryId !== null && $categoryId !== '') {
            $catId = (int) $categoryId;

            // DEBUG: Inspect pivot and legacy links before applying
            try {
                $pivotIds = \DB::table('product_category_relations')
                    ->where('category_id', $catId)
                    ->pluck('product_id')
                    ->take(50)
                    ->all();
                $legacyIds = Product::query()
                    ->where('category_id', $catId)
                    ->pluck('id')
                    ->take(50)
                    ->all();
                Log::info('Category filter precheck', [
                    'catId' => $catId,
                    'pivot_product_ids_sample' => $pivotIds,
                    'legacy_product_ids_sample' => $legacyIds,
                    'pivot_count' => \DB::table('product_category_relations')->where('category_id', $catId)->count(),
                    'legacy_count' => Product::where('category_id', $catId)->count(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Category precheck failed', ['err' => $e->getMessage()]);
            }

            Log::info('Applying category filter', ['catId' => $catId]);
            $query->where(function(Builder $qq) use ($catId) {
                $qq->where('category_id', $catId)
                   ->orWhereHas('categories', function(Builder $qb) use ($catId) {
                       $qb->where('categories.id', $catId);
                   });
            });
        }

        // Shippable filter
        if ($shippableFilter !== null && $shippableFilter !== '') {
            $isShippable = filter_var($shippableFilter, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isShippable !== null) {
                $query->where('shippable', $isShippable);
                Log::info('Applying shippable filter', ['shippable' => $isShippable]);
            }
        }

        // Combine price and attribute filters so a SINGLE variant satisfies all constraints
        $hasPriceFilter = ($priceMin !== null || $priceMax !== null);
        $filteredAttrGroups = [];
        if (is_array($attrs) && !empty($attrs)) {
            foreach ($attrs as $attrName => $values) {
                if (is_array($values) && !empty($values)) {
                    $vals = array_values(array_filter($values, fn($v) => $v !== '' && $v !== null));
                    if (!empty($vals)) {
                        $filteredAttrGroups[$attrName] = $vals;
                    }
                }
            }
        }
        $hasAttrFilter = !empty($filteredAttrGroups);

        if ($hasPriceFilter || $hasAttrFilter) {
            $query->whereHas('variants', function(Builder $qb) use ($priceMin, $priceMax, $filteredAttrGroups) {
                // Price bounds on the same variant
                if ($priceMin !== null) {
                    $qb->where('selling_price', '>=', $priceMin);
                }
                if ($priceMax !== null) {
                    $qb->where('selling_price', '<=', $priceMax);
                }
                // Attribute groups: AND across groups, OR within group
                foreach ($filteredAttrGroups as $attrName => $values) {
                    $qb->where(function(Builder $qq) use ($attrName, $values) {
                        foreach ($values as $v) {
                            // Support both new 'variant_attributes' and legacy 'attributes' columns
                            $qq->orWhereJsonContains('variant_attributes->'.$attrName, $v)
                               ->orWhereJsonContains('attributes->'.$attrName, $v);
                        }
                    });
                }
            });
        }

        // Sorting
        switch ($sort) {
            case 'price_asc':
                // Order by min variant price
                $query->withMin('variants as min_variant_price', 'selling_price')
                      ->orderBy('min_variant_price', 'asc');
                break;
            case 'price_desc':
                $query->withMin('variants as min_variant_price', 'selling_price')
                      ->orderBy('min_variant_price', 'desc');
                break;
            case 'newest':
            default:
                $query->orderByDesc('id');
        }

        $paginator = $query->with($this->cardWithRelations())->paginate($perPage);

        // DEBUG: dump product IDs returned
        Log::info('listProducts result ids', [
            'ids' => $paginator->getCollection()->pluck('id')->all(),
        ]);

        // Map presentation
        $paginator->getCollection()->transform(function ($p) {
            return $this->presentCard($p);
        });

        return $paginator;
    }

    public function buildFilters(?int $categoryId = null): array
    {
        // Price min/max from variants
        $base = ProductVariant::query()->whereHas('product', function($q){
            $q->where('status', 'published')->where('product_type', '!=', 'addon');
        });
        if ($categoryId) {
            $base->whereHas('product.categories', function($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }
        $min = (float) ($base->min('selling_price') ?? 0);
        $max = (float) ($base->max('selling_price') ?? 0);

        // Attribute groups and values (limit to those used by published products in the category)
        $types = VariantAttributeType::where('is_filterable', true)
            ->orderBy('display_order')
            ->get();

        $attributes = [];
        foreach ($types as $type) {
            // Get distinct values from variants of all published products globally
            // This ensures all attribute types show up regardless of current category filter
            $query = \DB::table('product_variants')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->where('products.status', 'published')
                ->where('products.product_type', '!=', 'addon');

            // Remove category filter to show all available attribute values
            // This gives users the full range of filter options

                $values = $query
                    ->selectRaw('DISTINCT product_variants.variant_attributes ->> ? as val', [$type->name])
                    ->whereRaw('product_variants.variant_attributes ->> ? IS NOT NULL', [$type->name])
                    ->pluck('val')
                    ->filter()
                    ->values()
                    ->all();

            sort($values);

            \Log::info('buildFilters attribute', ['type' => $type->name, 'values' => $values]);

            $attributes[] = [
                'name' => $type->name,
                'type' => $type->type,
                'values' => $values,
            ];
        }


        // DEBUG: how many pivot rows exist for chosen category
        if ($categoryId) {
            try {
                $count = \DB::table('product_category_relations')->where('category_id', (int)$categoryId)->count();
                Log::info('Pivot rows for category', ['category_id' => (int)$categoryId, 'count' => $count]);
            } catch (\Throwable $e) {
                Log::warning('Pivot check failed', ['err' => $e->getMessage()]);
            }
        }

        $result = [
            'price' => ['min' => $min, 'max' => $max],
            'attributes' => $attributes,
            'shipping_filter' => [
                ['key' => '', 'label' => 'All'],
                ['key' => 'true', 'label' => 'Shippable Only'],
                ['key' => 'false', 'label' => 'Non-Shippable Only'],
            ],
            'sort' => [
                ['key' => 'newest', 'label' => 'Newest'],
                ['key' => 'price_asc', 'label' => 'Price: Low to High'],
                ['key' => 'price_desc', 'label' => 'Price: High to Low'],
            ],
            // Breadcrumbs for listing when a category filter is active
            'breadcrumbs' => $this->buildCategoryBreadcrumbs($categoryId),
        ];

        \Log::info('attributes array before result', ['attributes' => $attributes]);
        \Log::info('buildFilters result', ['attributes' => $attributes]);

        return $result;
    }

    // -------------------------
    // Details + Recommendations
    // -------------------------
    public function getProductBySlug(string $slug): Product
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'variants.mediaAssets' => function($q){ $q->orderBy('display_order'); },
                'generalImages' => function($q){ $q->orderBy('display_order'); },
                'addons.variants.mediaAssets' => function($q){ $q->orderBy('display_order'); },
            ])
            ->firstOrFail();

        return $product;
    }

    public function getRecommendations(Product $product, int $limit = 8): array
    {
        // First, try to get products from the same categories
        $related = Product::query()
            ->where('status', 'published')
            ->where('product_type', '!=', 'addon') // Exclude addon products from recommendations
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function($q) use ($product) {
                $q->whereIn('category_id', $product->categories->pluck('id'));
            })
            ->with($this->cardWithRelations())
            ->take($limit)
            ->get();

        // If we don't have enough products from the same categories, fill with other products
        if ($related->count() < $limit) {
            $needed = $limit - $related->count();
            $excludeIds = $related->pluck('id')->push($product->id)->toArray();
            
            $additional = Product::query()
                ->where('status', 'published')
                ->where('product_type', '!=', 'addon')
                ->whereNotIn('id', $excludeIds)
                ->with($this->cardWithRelations())
                ->take($needed)
                ->get();
                
            $related = $related->concat($additional);
        }

        return $related->map(fn($p) => $this->presentCard($p))->all();
    }

    // -------------------------
    // Orders (Customer-facing: web + API)
    // -------------------------
    /**
     * Return paginated order summaries for a user.
     */
    public function listUserOrders(int $userId, int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $q = \App\Models\Order::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->with(['items' => function($q){ $q->select('id','order_id','product_id','product_name','variant_display_name','quantity','unit_price','total_price'); }, 'items.addons:id,order_item_id,addon_name,quantity,unit_price,total_price']);
        $p = $q->paginate($perPage);

        // Batch-load primary images for all products referenced in this page (avoid N+1)
        $productIds = collect();
        foreach ($p->items() as $ord) {
            $productIds = $productIds->merge($ord->items->pluck('product_id')->filter());
        }
        $productIds = $productIds->unique()->values();
        $productImages = [];
        if ($productIds->isNotEmpty()) {
            $prods = \App\Models\Product::whereIn('id', $productIds)
                ->with(['generalImages' => function($q){ $q->orderBy('display_order'); }])
                ->get();
            foreach ($prods as $pdt) {
                $primary = $pdt->getPrimaryImage();
                $productImages[$pdt->id] = $primary ? ImageService::getImageUrl($primary->file_path ?: $primary->file_url) : null;
            }
        }

        $p->getCollection()->transform(function($order) use ($productImages){
            return $this->presentOrderSummary($order, $productImages);
        });
        return $p;
    }
    
    /**
     * Present a single order summary for listing.
     */
    public function presentOrderSummary(\App\Models\Order $order, array $productImages = []): array
    {
        // Total item count = sum of main items + addons quantities
        $mainQty = (int) ($order->items->sum('quantity'));
        $addonQty = (int) ($order->items->flatMap(fn($it) => $it->addons)->sum('quantity'));
        $totalItems = $mainQty + $addonQty;
        
        // Build a small preview of items (up to 3) with optional image URLs
        $preview = $order->items->take(3)->map(function($it) use ($productImages){
            return [
                'name' => $it->product_name,
                'variant' => $it->variant_display_name,
                'qty' => (int)$it->quantity,
                'image_url' => isset($productImages[$it->product_id]) ? $productImages[$it->product_id] : null,
            ];
        })->values()->all();
        
        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'placed_at' => optional($order->created_at)?->toDateTimeString(),
            'total_amount' => (float)$order->total_amount,
            'total_items' => $totalItems,
            'items_preview' => $preview,
        ];
    }
    
    /**
     * Return a full order detail ensuring it belongs to the given user.
     * Uses order_number instead of id.
     */
    public function getUserOrderDetail(int $userId, string $orderNumber): array
    {
        $order = \App\Models\Order::query()
            ->where('user_id', $userId)
            ->where('order_number', $orderNumber)
            ->with([
                'items' => function($q){
                    $q->select('id','order_id','product_id','product_name','variant_display_name','product_attributes','quantity','unit_price','total_price');
                },
                'items.addons:id,order_item_id,addon_name,quantity,unit_price,total_price,addon_product_id',
                'shippingAddress','billingAddress','payments','appliedVouchers'
            ])->firstOrFail();
        
        // Preload product images in bulk for listing visuals (main items)
        $productIds = $order->items->pluck('product_id')->filter()->unique()->values();
        $productImages = [];
        if ($productIds->isNotEmpty()) {
            $prods = \App\Models\Product::whereIn('id', $productIds)
                ->with(['generalImages' => function($q){ $q->orderBy('display_order'); }])
                ->get();
            foreach ($prods as $p) {
                $primary = $p->getPrimaryImage();
                $productImages[$p->id] = $primary ? ImageService::getImageUrl($primary->file_path ?: $primary->file_url) : null;
            }
        }

        // Preload addon product images
        $addonProductIds = $order->items->flatMap(fn($it) => $it->addons->pluck('addon_product_id'))
            ->filter()->unique()->values();
        $addonImages = [];
        if ($addonProductIds->isNotEmpty()) {
            $addons = \App\Models\Product::whereIn('id', $addonProductIds)
                ->with(['generalImages' => function($q){ $q->orderBy('display_order'); }])
                ->get();
            foreach ($addons as $ap) {
                $primary = $ap->getPrimaryImage();
                $addonImages[$ap->id] = $primary ? ImageService::getImageUrl($primary->file_path ?: $primary->file_url) : null;
            }
        }
        
        $mainQty = (int) $order->items->sum('quantity');
        $addonQty = (int) $order->items->flatMap(fn($it) => $it->addons)->sum('quantity');
        $totalItems = $mainQty + $addonQty;
        
        $items = $order->items->map(function($it) use ($productImages, $addonImages){
            return [
                'name' => $it->product_name,
                'variant' => $it->variant_display_name,
                'attributes' => is_array($it->product_attributes) ? $it->product_attributes : [],
                'qty' => (int) $it->quantity,
                'unit_price' => (float) $it->unit_price,
                'total_price' => (float) $it->total_price,
                'image_url' => $productImages[$it->product_id] ?? null,
                'addons' => $it->addons->map(function($ad) use ($addonImages){
                    return [
                        'name' => $ad->addon_name,
                        'qty' => (int)$ad->quantity,
                        'unit_price' => (float)$ad->unit_price,
                        'total_price' => (float)$ad->total_price,
                        'image_url' => isset($ad->addon_product_id) ? ($addonImages[$ad->addon_product_id] ?? null) : null,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        // Applied vouchers
        $appliedVouchers = $order->appliedVouchers->map(function($ov) {
            return [
                'code' => $ov->voucher_code,
                'type' => $ov->discount_type,
                'value' => (float)$ov->discount_value,
                'calculated_discount' => (float)$ov->calculated_discount,
                'stack_order' => $ov->stack_order,
                'stack_priority' => $ov->stack_priority,
            ];
        })->values()->all();

        // Payment display: show last successful payment if any
        $lastPayment = $order->payments->sortByDesc('id')->first();
        $paymentText = null;
        if ($lastPayment && $lastPayment->status === 'succeeded') {
            $last4 = $lastPayment->card_last4 ? ('**** ' . $lastPayment->card_last4) : null;
            $paymentText = trim('Paid with ' . ($last4 ? ('card ' . $last4) : ($lastPayment->payment_gateway ?? 'card')));
        }
        
        // Clean address data for mobile - remove sensitive fields
        $cleanBillingAddress = null;
        if ($order->billingAddress) {
            $cleanBillingAddress = [
                'id' => $order->billingAddress->id,
                'first_name' => $order->user->first_name ?? '',
                'last_name' => $order->user->last_name ?? '',
                'address_line_1' => $order->billingAddress->address_line1 ?? '',
                'address_line_2' => $order->billingAddress->address_line2 ?? '',
                'city' => '',
                'state' => '',
                'postal_code' => $order->billingAddress->postal_code ?? '',
                'country' => $order->billingAddress->country ?? '',
                'phone' => $order->user->phone ?? '',
                'address_type' => $order->billingAddress->label ?? 'Home',
            ];
        }
        
        $cleanShippingAddress = null;
        if ($order->shippingAddress) {
            $cleanShippingAddress = [
                'id' => $order->shippingAddress->id,
                'first_name' => $order->user->first_name ?? '',
                'last_name' => $order->user->last_name ?? '',
                'address_line_1' => $order->shippingAddress->address_line1 ?? '',
                'address_line_2' => $order->shippingAddress->address_line2 ?? '',
                'city' => '',
                'state' => '',
                'postal_code' => $order->shippingAddress->postal_code ?? '',
                'country' => $order->shippingAddress->country ?? '',
                'phone' => $order->user->phone ?? '',
                'address_type' => $order->shippingAddress->label ?? 'Home',
            ];
        }
        
        return [
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'estimated_delivery' => $order->estimated_delivery,
                'total_amount' => (float)$order->total_amount,
                'subtotal' => (float)$order->subtotal,
                'discount_amount' => (float)($order->discount_amount ?? 0),
                'shipping_amount' => (float)($order->shipping_amount ?? 0),
                'tax_amount' => (float)($order->tax_amount ?? 0),
                'applied_tax_rate' => (float)($order->applied_tax_rate ?? 0),
                'coupon_code' => $order->coupon_code,
                'total_items' => $totalItems,
                'placed_at' => optional($order->created_at)?->toDateTimeString(),
            ],
            'addresses' => [
                'billing' => $cleanBillingAddress,
                'shipping' => $cleanShippingAddress,
            ],
            'items' => $items,
            'applied_vouchers' => $appliedVouchers, // Add this line to include applied vouchers
            'payment' => [
                'display_text' => $paymentText,
                'history' => $order->payments->map(fn($p) => [
                    'id' => $p->id, // Add the ID field for invoice download
                    'status' => $p->status,
                    'amount' => (float)$p->amount,
                    'gateway' => $p->payment_gateway,
                    'transaction_id' => $p->transaction_id,
                    'invoice_id' => $p->invoice_id,
                    'invoice_url' => $p->invoice_url,
                    'invoice_pdf_url' => $p->invoice_pdf_url,
                    'invoice_number' => $p->invoice_number,
                ])->values()->all(),
            ],
        ];
    }

    /**
     * Return a full order detail by order number (authenticated access).
     * Uses order_number. Only accessible by the order owner.
     */
    public function getOrderDetailByNumber(string $orderNumber): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            abort(401, 'Unauthorized');
        }

        $order = \App\Models\Order::query()
            ->where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with([
                'items' => function($q){
                    $q->select('id','order_id','product_id','product_name','variant_display_name','product_attributes','quantity','unit_price','total_price');
                },
                'items.addons:id,order_item_id,addon_name,quantity,unit_price,total_price,addon_product_id',
                'shippingAddress','billingAddress','payments','appliedVouchers'
            ])->firstOrFail();

        // Preload product images in bulk for listing visuals (main items)
        $productIds = $order->items->pluck('product_id')->filter()->unique()->values();
        $productImages = [];
        if ($productIds->isNotEmpty()) {
            $prods = \App\Models\Product::whereIn('id', $productIds)
                ->with(['generalImages' => function($q){ $q->orderBy('display_order'); }])
                ->get();
            foreach ($prods as $p) {
                $primary = $p->getPrimaryImage();
                $productImages[$p->id] = $primary ? ImageService::getImageUrl($primary->file_path ?: $primary->file_url) : null;
            }
        }

        // Preload addon product images
        $addonProductIds = $order->items->flatMap(fn($it) => $it->addons->pluck('addon_product_id'))
            ->filter()->unique()->values();
        $addonImages = [];
        if ($addonProductIds->isNotEmpty()) {
            $addons = \App\Models\Product::whereIn('id', $addonProductIds)
                ->with(['generalImages' => function($q){ $q->orderBy('display_order'); }])
                ->get();
            foreach ($addons as $ap) {
                $primary = $ap->getPrimaryImage();
                $addonImages[$ap->id] = $primary ? ImageService::getImageUrl($primary->file_path ?: $primary->file_url) : null;
            }
        }

        $mainQty = (int) $order->items->sum('quantity');
        $addonQty = (int) $order->items->flatMap(fn($it) => $it->addons)->sum('quantity');
        $totalItems = $mainQty + $addonQty;

        $items = $order->items->map(function($it) use ($productImages, $addonImages){
            return [
                'name' => $it->product_name,
                'variant' => $it->variant_display_name,
                'attributes' => is_array($it->product_attributes) ? $it->product_attributes : [],
                'qty' => (int) $it->quantity,
                'unit_price' => (float) $it->unit_price,
                'total_price' => (float) $it->total_price,
                'image_url' => $productImages[$it->product_id] ?? null,
                'addons' => $it->addons->map(function($ad) use ($addonImages){
                    return [
                        'name' => $ad->addon_name,
                        'qty' => (int)$ad->quantity,
                        'unit_price' => (float)$ad->unit_price,
                        'total_price' => (float)$ad->total_price,
                        'image_url' => isset($ad->addon_product_id) ? ($addonImages[$ad->addon_product_id] ?? null) : null,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        // Applied vouchers
        $appliedVouchers = $order->appliedVouchers->map(function($ov) {
            return [
                'code' => $ov->voucher_code,
                'type' => $ov->discount_type,
                'value' => (float)$ov->discount_value,
                'calculated_discount' => (float)$ov->calculated_discount,
                'stack_order' => $ov->stack_order,
                'stack_priority' => $ov->stack_priority,
            ];
        })->values()->all();

        // Payment display: show last successful payment if any
        $lastPayment = $order->payments->sortByDesc('id')->first();
        $paymentText = null;
        if ($lastPayment && $lastPayment->status === 'succeeded') {
            $last4 = $lastPayment->card_last4 ? ('**** ' . $lastPayment->card_last4) : null;
            $paymentText = trim('Paid with ' . ($last4 ? ('card ' . $last4) : ($lastPayment->payment_gateway ?? 'card')));
        }
        
        // Clean address data for mobile - remove sensitive fields
        $cleanBillingAddress = null;
        if ($order->billingAddress) {
            $cleanBillingAddress = [
                'id' => $order->billingAddress->id,
                'first_name' => $order->user->first_name ?? '',
                'last_name' => $order->user->last_name ?? '',
                'address_line_1' => $order->billingAddress->address_line1 ?? '',
                'address_line_2' => $order->billingAddress->address_line2 ?? '',
                'city' => '',
                'state' => '',
                'postal_code' => $order->billingAddress->postal_code ?? '',
                'country' => $order->billingAddress->country ?? '',
                'phone' => $order->user->phone ?? '',
                'address_type' => $order->billingAddress->label ?? 'Home',
            ];
        }
        
        $cleanShippingAddress = null;
        if ($order->shippingAddress) {
            $cleanShippingAddress = [
                'id' => $order->shippingAddress->id,
                'first_name' => $order->user->first_name ?? '',
                'last_name' => $order->user->last_name ?? '',
                'address_line_1' => $order->shippingAddress->address_line1 ?? '',
                'address_line_2' => $order->shippingAddress->address_line2 ?? '',
                'city' => '',
                'state' => '',
                'postal_code' => $order->shippingAddress->postal_code ?? '',
                'country' => $order->shippingAddress->country ?? '',
                'phone' => $order->user->phone ?? '',
                'address_type' => $order->shippingAddress->label ?? 'Home',
            ];
        }
        
        return [
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'estimated_delivery' => $order->estimated_delivery,
                'total_amount' => (float)$order->total_amount,
                'subtotal' => (float)$order->subtotal,
                'discount_amount' => (float)($order->discount_amount ?? 0),
                'shipping_amount' => (float)($order->shipping_amount ?? 0),
                'tax_amount' => (float)($order->tax_amount ?? 0),
                'applied_tax_rate' => (float)($order->applied_tax_rate ?? 0),
                'coupon_code' => $order->coupon_code,
                'total_items' => $totalItems,
                'placed_at' => optional($order->created_at)?->toDateTimeString(),
            ],
            'addresses' => [
                'billing' => $cleanBillingAddress,
                'shipping' => $cleanShippingAddress,
            ],
            'items' => $items,
            'applied_vouchers' => $appliedVouchers, // Add this line to include applied vouchers
            'payment' => [
                'display_text' => $paymentText,
                'history' => $order->payments->map(fn($p) => [
                    'id' => $p->id, // Add the ID field for invoice download
                    'status' => $p->status,
                    'amount' => (float)$p->amount,
                    'gateway' => $p->payment_gateway,
                    'transaction_id' => $p->transaction_id,
                    'invoice_id' => $p->invoice_id,
                    'invoice_url' => $p->invoice_url,
                    'invoice_pdf_url' => $p->invoice_pdf_url,
                    'invoice_number' => $p->invoice_number,
                ])->values()->all(),
            ],
        ];
    }

    // -------------------------
    // Presenters
    // -------------------------
    public function presentCard(Product $product): array
    {
        // Resolve primary image via model helper
        $primary = $product->getPrimaryImage();
        $img = null;
        if ($primary) {
            $img = ImageService::getImageUrl($primary->file_path ?: $primary->file_url);
        }

        // Price text (min-max across variants)
        $min = $product->getMinPrice();
        $max = $product->getMaxPrice();
        $priceText = $min && $max && $min != $max
            ? ('$' . number_format($min, 2) . ' – $' . number_format($max, 2))
            : ('$' . number_format($min ?? 0, 2));

        // Figma-aligned sale info: compute the minimum compare_price across variants and discount percent
        $minCompare = null; // minimum non-null compare_price across variants
        foreach ($product->variants as $v) {
            if ($v->compare_price !== null && $v->compare_price !== '') {
                $cp = (float) $v->compare_price;
                if ($cp > 0 && ($minCompare === null || $cp < $minCompare)) {
                    $minCompare = $cp;
                }
            }
        }
        $discountPercent = 0;
        if ($min !== null && $min > 0 && $minCompare !== null && $minCompare > $min) {
            $discountPercent = (int) round((($minCompare - $min) / $minCompare) * 100);
        }
        $isOnSale = $discountPercent > 0;

        // Breadcrumbs: use primary category if available, else first attached; build path using Category model
        $primaryCat = $product->categories()->wherePivot('is_primary', true)->first() ?: $product->categories()->first();
        $breadcrumbs = [];
        if ($primaryCat) {
            // Build path up to root
            $stack = collect();
            $cur = $primaryCat;
            while ($cur) { $stack->prepend($cur); $cur = $cur->parent; }
            foreach ($stack as $c) {
                $breadcrumbs[] = [
                    'id' => $c->id,
                    'name' => $c->name,
                    'url' => route('shop.list', ['category_id' => $c->id]),
                ];
            }
        }

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'price_text' => $priceText,
            'price_min' => $min !== null ? (float) $min : null,
            'price_max' => $max !== null ? (float) $max : null,
            'compare_price_min' => $minCompare,
            'discount_percent' => $discountPercent,
            'is_on_sale' => $isOnSale,
            'image' => $img,
            'product_type' => $product->product_type,
            'url' => route('shop.product', $product->slug),
            'breadcrumbs' => $breadcrumbs,
        ];
    }

    public function presentDetail(Product $product): array
    {
        // Build gallery
        $gallery = [];
        if ($product->product_type === 'variant') {
            // General images shared across variants
            foreach ($product->generalImages()->images()->ordered()->get() as $media) {
                $gallery[] = ImageService::getImageUrl($media->file_path ?: $media->file_url);
            }
        } else {
            // Regular/addon: use variant media (primary + non-primary as a simple gallery)
            $variant = $product->variants()->first();
            if ($variant) {
                foreach ($variant->mediaAssets()->images()->orderBy('display_order')->get() as $media) {
                    $gallery[] = ImageService::getImageUrl($media->file_path ?: $media->file_url);
                }
            }
        }

        // Variants summary (for swatches) + gallery per variant
        $generalGallery = $gallery; // for variant-type products this contains product-level general images
        $variants = $product->variants()
            ->with(['mediaAssets' => function($q){ $q->orderBy('display_order'); }])
            ->get()
            ->map(function($v) use ($generalGallery){
                $primary = $v->getPrimaryImage();
                // Use model helper to combine general + variant + option images in correct order
                $display = $v->getDisplayImages();
                $gallery = [];
                foreach ($display as $media) {
                    $gallery[] = ImageService::getImageUrl($media->file_path ?: $media->file_url);
                }
                if (empty($gallery)) {
                    // Fallback to general gallery only
                    $gallery = $generalGallery;
                }

                // Use structured attributes only
                $attrs = $v->variant_attributes ?? [];

                $compare = $v->compare_price ? (float) $v->compare_price : null;
                $discount = ($compare && $compare > 0 && $compare > (float)$v->selling_price)
                    ? round((($compare - (float)$v->selling_price) / $compare) * 100)
                    : 0;
                return [
                    'id' => $v->id,
                    'name' => self::formatVariantName($attrs),
                    'sku' => $v->sku,
                    'price' => (float) $v->selling_price,
                    'compare_price' => $compare,
                    'discount_percent' => $discount,
                    'stock' => (int) $v->stock_quantity,
                    'available' => (int) $v->availableStock(),
                    'availability_label' => $v->availabilityLabel(),
                    'track_inventory' => (bool) $v->track_inventory,
                    'allow_backorders' => (bool) $v->allow_backorders,
                    'is_primary' => (bool) $v->is_primary,
                    'image' => $primary ? ImageService::getImageUrl($primary->file_path ?: $primary->file_url) : null,
                    'gallery' => $gallery,
                    'variant_attributes' => $attrs,
                ];
            })
            ->values()
            ->all();

        // Addons (include minimal variant info for selection on PDP)
        $addons = $product->addons()->with(['variants.mediaAssets'])->get()->map(function($addon){
            $card = $this->presentCard($addon);
            $card['is_required'] = (bool) optional($addon->pivot)->is_required;
            // Choose a primary variant (or first) to attach minimal info
            $v = $addon->variants()->orderByDesc('is_primary')->orderBy('id')->first();
            if ($v) {
                $card['variant'] = [
                    'id' => $v->id,
                    'name' => self::formatVariantName($v->variant_attributes ?? []),
                    'sku' => $v->sku,
                    'price' => (float) $v->selling_price,
                ];
            } else {
                $card['variant'] = null;
            }
            return $card;
        })->values()->all();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'short_description' => $product->short_description, // expose short description for tabs
            'price_min' => (float) ($product->getMinPrice() ?? 0),
            'price_max' => (float) ($product->getMaxPrice() ?? 0),
            'gallery' => $gallery,
            'variants' => $variants,
            'addons' => $addons,
            // SEO fields
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
            'shippable' => (bool) $product->shippable,
        ];
    }

    // -------------------------
    // Cart (session-based for guests)
    // -------------------------
    public function getCart(): array
    {
        // For authenticated users, rebuild the cart snapshot from DB to ensure accuracy
        if ($this->isAuthenticated()) {
            $this->syncSessionFromDb($this->getCurrentUser()->id);
        } else {
            // For unauthenticated users, check if there's a guest session token
            $sessionToken = Session::get('guest.session_token');
            if ($sessionToken) {
                // Sync session snapshot from guest cart items in DB
                $this->syncGuestSessionFromDb($sessionToken);
            } else {
                // No session token, ensure cart is empty
                Session::forget('cart.items');
                Session::put('cart.items', []);
                $this->recalculateCartTotal();
            }
        }

        $items = array_values(Session::get('cart.items', []));
        $total = (float) Session::get('cart.total', 0);

        // Defensive recompute: refresh availability snapshot; do NOT inflate reservation with global availability
        foreach ($items as &$it) {
            try {
                $variantId = (int)($it['variant_id'] ?? 0);
                if ($variantId <= 0) continue;
                $variant = ProductVariant::find($variantId);
                if (!$variant) continue;

                $qty = max(1, (int)($it['qty'] ?? 1));

                // Compute backorder using current availability snapshot; still no reservation at cart time
                $availableNow = (int) $variant->availableStock();
                $backorderPreview = ($variant->track_inventory && $variant->allow_backorders)
                    ? max(0, $qty - min($qty, $availableNow))
                    : 0;

                // Determine availability_status consistently using helper
                $newStatus = $this->calculateAvailabilityStatus($variant, $qty);

                $it['available'] = $availableNow;
                $it['availability_label'] = $variant->availabilityLabel();
                $it['availability_status'] = $newStatus;
                $it['track_inventory'] = (bool) $variant->track_inventory;
                $it['allow_backorders'] = (bool) $variant->allow_backorders;
                $it['shippable'] = (bool) $variant->product->shippable;
                $it['max_quantity_per_order'] = (int) ($variant->max_quantity_per_order ?? 0);
                $it['min_quantity_alert'] = (int) ($variant->min_quantity_alert ?? 0);
                $it['backorder_qty'] = (int) $backorderPreview;
            } catch (\Throwable $e) {
                // ignore per-item errors
            }
        }
        unset($it);

        $count = 0;
        foreach ($items as $it) { $count += (int) ($it['qty'] ?? 0); }
        $cart = [
            'items' => $items,
            'total' => $total,
            'count' => $count,
        ];

        // Store a user-facing message about shipping policy when backorders exist
        try {
            $hasBackorders = collect($items)->contains(fn($r) => ($r['backorder_qty'] ?? 0) > 0);
            if ($hasBackorders) {
                $cart['notice'] = 'In-stock items ship now; backordered items ship when available.';
            }
        } catch (\Throwable $e) {}

        return $cart;
    }

    public function addToCart(int $variantId, int $qty = 1, array $addons = []): array
    {
        $qty = max(1, (int)$qty);

        // Auth users: only validate and persist cart lines; do NOT reserve inventory here (non add-ons)
        if ($this->isAuthenticated()) {
            return DB::transaction(function () use ($variantId, $qty, $addons) {
                $variant = ProductVariant::with(['product.addons.variants'])->lockForUpdate()->findOrFail($variantId);

                // Validate stock and limits according to settings, but do not change product_variants here
                if ($variant->track_inventory) {
                    $available = $variant->availableStock();
                    if ($available <= 0 && !$variant->allow_backorders) {
                        throw new \RuntimeException('This item is out of stock.');
                    }
                    if (!$variant->allow_backorders) {
                        $qty = min($qty, $available);
                    }
                }
                if ($variant->max_quantity_per_order && $variant->max_quantity_per_order > 0) {
                    $qty = min($qty, (int)$variant->max_quantity_per_order);
                }

                // Upsert cart item (unique by user_id, variant_id)
                $userId = $this->getCurrentUser()->id;
                $cartItem = CartItem::query()->where('user_id', $userId)->where('variant_id', $variant->id)->first();
                $expiresAt = now()->addMinutes((int) config('sku.cart_reserve_minutes', 60));

                if ($cartItem) {
                    $finalQty = (int)$cartItem->quantity + $qty;
                    $availabilityStatus = $this->calculateAvailabilityStatus($variant, $finalQty);

                    $cartItem->quantity = $finalQty;
                    $cartItem->expires_at = $expiresAt;
                    $cartItem->availability_status = $availabilityStatus;
                    $cartItem->save();
                } else {
                    $availabilityStatus = $this->calculateAvailabilityStatus($variant, $qty);

                    $cartItem = CartItem::create([
                        'user_id' => $userId,
                        'product_id' => $variant->product_id,
                        'variant_id' => $variant->id,
                        'quantity' => $qty,
                        'expires_at' => $expiresAt,
                        'availability_status' => $availabilityStatus,
                    ]);
                }

                // Persist addons with is_required flag
                $addonsById = [];
                foreach ($addons as $ad) {
                    if (!isset($ad['product_id'], $ad['variant_id'])) continue;
                    $pid = (int)$ad['product_id'];
                    $isRequired = false;
                    // Check if this addon is required for the main product
                    foreach ($variant->product->addons as $addonRelation) {
                        if ($addonRelation->id === $pid) {
                            $isRequired = (bool) optional($addonRelation->pivot)->is_required;
                            break;
                        }
                    }
                    $addonsById[$pid] = [
                        'cart_item_id' => $cartItem->id,
                        'addon_product_id' => $pid,
                        'addon_variant_id' => (int)$ad['variant_id'],
                        'quantity' => max(1, (int)($ad['qty'] ?? 1)),
                        'is_required' => $isRequired,
                    ];
                }
                foreach ($variant->product->addons as $requiredAddon) {
                    $isRequired = (bool) optional($requiredAddon->pivot)->is_required;
                    if (!$isRequired) continue;
                    $pid = (int) $requiredAddon->id;
                    if (!isset($addonsById[$pid])) {
                        $v = $requiredAddon->variants()->orderByDesc('is_primary')->orderBy('id')->first();
                        if ($v) {
                            $addonsById[$pid] = [
                                'cart_item_id' => $cartItem->id,
                                'addon_product_id' => $pid,
                                'addon_variant_id' => $v->id,
                                'quantity' => 1,
                                'is_required' => true,
                            ];
                        }
                    }
                }
                if (!empty($addonsById)) {
                    $cartItem->addons()->delete();
                    CartAddon::insert(array_values($addonsById));
                }

                // Sync session snapshot for UI continuity
                $this->syncSessionFromDb($userId);
                return $this->getCart();
            });
        }

        // Guest flow: do NOT reserve stock on add-to-cart. Maintain session-only snapshot.
        $variant = ProductVariant::with(['product.addons.variants'])->findOrFail($variantId);

        $sessionToken = Session::get('guest.session_token');
        if (!$sessionToken) {
            $sessionToken = bin2hex(random_bytes(16));
            Session::put('guest.session_token', $sessionToken);
            \Log::info('ECommerceService: Created new guest session token', [
                'session_token' => $sessionToken,
                'session_id' => Session::getId(),
            ]);
        } else {
            \Log::info('ECommerceService: Using existing guest session token', [
                'session_token' => $sessionToken,
                'session_id' => Session::getId(),
            ]);
        }

        return DB::transaction(function () use ($variant, $qty, $addons, $sessionToken) {
            // Validate against stock rules without modifying inventory
            if ($variant->track_inventory) {
                $available = max(0, (int)$variant->stock_quantity);
                if ($available <= 0 && !$variant->allow_backorders) {
                    throw new \RuntimeException('This item is out of stock.');
                }
                if (!$variant->allow_backorders) {
                    $qty = min($qty, $available);
                }
            }
            if ($variant->max_quantity_per_order && $variant->max_quantity_per_order > 0) {
                $qty = min($qty, (int)$variant->max_quantity_per_order);
            }

            // Persist guest cart to DB for this session using atomic upsert (no inventory reservation)
            $expiresAt = now()->addMinutes((int) config('sku.cart_reserve_minutes', 60));
            $availabilityStatus = $this->calculateAvailabilityStatus($variant, $qty);

            // First, try to find existing guest cart item
            $guestCartItem = GuestCartItem::where('session_token', $sessionToken)
                ->where('variant_id', $variant->id)
                ->first();

            if ($guestCartItem) {
                // Update existing item
                $guestCartItem->quantity += $qty;
                $guestCartItem->expires_at = $expiresAt;
                $guestCartItem->availability_status = $this->calculateAvailabilityStatus($variant, $guestCartItem->quantity);
                $guestCartItem->save();
            } else {
                // Create new item
                $guestCartItem = GuestCartItem::create([
                    'session_token' => $sessionToken,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'quantity' => $qty,
                    'expires_at' => $expiresAt,
                    'availability_status' => $availabilityStatus,
                ]);
            }

            // Persist guest addons to DB with is_required flag
            $addonsById = [];
            foreach ($addons as $ad) {
                if (!isset($ad['product_id'], $ad['variant_id'])) continue;
                $pid = (int)$ad['product_id'];
                $isRequired = false;
                // Check if this addon is required for the main product
                foreach ($variant->product->addons as $addonRelation) {
                    if ($addonRelation->id === $pid) {
                        $isRequired = (bool) optional($addonRelation->pivot)->is_required;
                        break;
                    }
                }
                $addonsById[$pid] = [
                    'guest_cart_item_id' => $guestCartItem->id,
                    'addon_product_id' => $pid,
                    'addon_variant_id' => (int)$ad['variant_id'],
                    'quantity' => max(1, (int)($ad['qty'] ?? 1)),
                    'is_required' => $isRequired,
                ];
            }
            foreach ($variant->product->addons as $requiredAddon) {
                $isRequired = (bool) optional($requiredAddon->pivot)->is_required;
                if (!$isRequired) continue;
                $pid = (int) $requiredAddon->id;
                if (!isset($addonsById[$pid])) {
                    $v = $requiredAddon->variants()->orderByDesc('is_primary')->orderBy('id')->first();
                    if ($v) {
                        $addonsById[$pid] = [
                            'guest_cart_item_id' => $guestCartItem->id,
                            'addon_product_id' => $pid,
                            'addon_variant_id' => $v->id,
                            'quantity' => 1,
                            'is_required' => true,
                        ];
                    }
                }
            }
            if (!empty($addonsById)) {
                $guestCartItem->addons()->delete(); // Remove existing addons
                GuestCartAddon::insert(array_values($addonsById));
            }

            // Maintain session snapshot (no DB reservation for guests)
            $addonsById = [];
            foreach ($addons as $ad) {
                if (!isset($ad['product_id'])) continue;
                $pid = (int)$ad['product_id'];
                $isRequired = false;
                // Check if this addon is required for the main product
                foreach ($variant->product->addons as $addonRelation) {
                    if ($addonRelation->id === $pid) {
                        $isRequired = (bool) optional($addonRelation->pivot)->is_required;
                        break;
                    }
                }
                $ad['qty'] = max(1, (int)($ad['qty'] ?? 1));
                $ad['unit_price'] = (float)($ad['unit_price'] ?? 0);
                $ad['subtotal'] = $ad['unit_price'] * $ad['qty'];
                $ad['is_required'] = $isRequired;
                $addonsById[$pid] = $ad;
            }
            foreach ($variant->product->addons as $requiredAddon) {
                $isRequired = (bool) optional($requiredAddon->pivot)->is_required;
                if (!$isRequired) continue;
                $pid = (int) $requiredAddon->id;
                if (!isset($addonsById[$pid])) {
                    $v = $requiredAddon->variants()->orderByDesc('is_primary')->orderBy('id')->first();
                    if ($v) {
                        $addonsById[$pid] = [
                            'product_id' => $pid,
                            'variant_id' => $v->id,
                            'name' => $requiredAddon->name,
                            'variant_name' => self::formatVariantName($v->variant_attributes ?? []),
                            'sku' => $v->sku,
                            'is_required' => true,
                            'qty' => 1,
                            'unit_price' => (float) $v->selling_price,
                            'subtotal' => (float) $v->selling_price * 1,
                        ];
                    }
                }
            }
            $addons = array_values($addonsById);

            $price = (float) $variant->selling_price;
            $itemId = uniqid('ci_', true);

            $availableNow = (int) $variant->availableStock();
            $backorderPreview = ($variant->track_inventory && $variant->allow_backorders)
                ? max(0, $qty - min($qty, $availableNow))
                : 0;

            // Get primary image for the cart item
            $primaryImage = $variant->getPrimaryImage();
            $imageUrl = $primaryImage ? $primaryImage->file_url : null;

            $item = [
                'id' => $itemId,
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'name' => $variant->product->name,
                'variant_display_name' => self::formatVariantName($variant->variant_attributes ?? []),
                'qty' => $qty,
                'unit_price' => $price,
                'subtotal' => $price * $qty,
                'image_url' => $imageUrl,
                'addons' => $addons,
                'shippable' => (bool) $variant->product->shippable,
                'available' => $availableNow,
                'availability_label' => $variant->availabilityLabel(),
                'track_inventory' => (bool) $variant->track_inventory,
                'allow_backorders' => (bool) $variant->allow_backorders,
                'max_quantity_per_order' => (int) ($variant->max_quantity_per_order ?? 0),
                'min_quantity_alert' => (int) ($variant->min_quantity_alert ?? 0),
                'backorder_qty' => $backorderPreview,
            ];
            $items = Session::get('cart.items', []);
            $items[$itemId] = $item;
            Session::put('cart.items', $items);
            $this->recalculateCartTotal();
            return $this->getCart();
        });
    }

    public function updateCartItem(string $itemId, int $qty): array
    {
        $qty = max(1, (int)$qty);

        if ($this->isAuthenticated()) {
            return DB::transaction(function () use ($itemId, $qty) {
                $userId = $this->getCurrentUser()->id;
                // Find cart item by session snapshot id mapping
                $sessionItems = Session::get('cart.items', []);
                $snapshot = $sessionItems[$itemId] ?? null;
                if (!$snapshot) return $this->getCart();

                $cartItem = CartItem::query()
                    ->where('user_id', $userId)
                    ->where('variant_id', $snapshot['variant_id'] ?? 0)
                    ->first();
                if (!$cartItem) return $this->getCart();

                $variant = ProductVariant::lockForUpdate()->find($cartItem->variant_id);
                if (!$variant) return $this->getCart();

                // Validate limits; do not modify product_variants here
                if ($variant->track_inventory) {
                    $available = max(0, (int)$variant->stock_quantity);
                    $delta = $qty - (int)$cartItem->quantity;
                    if ($delta > 0 && !$variant->allow_backorders) {
                        $delta = min($delta, max(0, $available - (int)$cartItem->quantity));
                        $qty = (int)$cartItem->quantity + $delta;
                    }
                }
                if ($variant->max_quantity_per_order && $variant->max_quantity_per_order > 0) {
                    $qty = min($qty, (int)$variant->max_quantity_per_order);
                }

                $cartItem->quantity = $qty;
                $cartItem->expires_at = now()->addMinutes((int) config('sku.cart_reserve_minutes', 60));
                $cartItem->availability_status = $this->calculateAvailabilityStatus($variant, $qty);
                // No reserved quantity at cart time; we don't persist any reservation on cart rows
                $cartItem->save();

                $this->syncSessionFromDb($userId);

                // Cart changed: keep any existing checkout reservation; we'll adjust at checkout time.
                // Intentionally do not release or clear the reservation snapshot here.

                return $this->getCart();
            });
        }

        // Guest session flow (no reservation). Update DB row + session snapshot.
        $items = Session::get('cart.items', []);
        if (!isset($items[$itemId])) return $this->getCart();

        $cartItem = $items[$itemId];
        $variant = ProductVariant::lockForUpdate()->find($cartItem['variant_id'] ?? 0);
        if (!$variant) return $this->getCart();

        // Validate target qty against availability; allow exceeding available if backorders enabled
        if ($variant->track_inventory) {
            $available = max(0, (int)$variant->stock_quantity);
            $delta = $qty - (int)$cartItem['qty'];
            if ($delta > 0 && !$variant->allow_backorders) {
                $delta = min($delta, max(0, $available - (int)$cartItem['qty']));
                $qty = (int)$cartItem['qty'] + $delta;
            }
        }
        if ($variant->max_quantity_per_order && $variant->max_quantity_per_order > 0) {
            $qty = min($qty, (int)$variant->max_quantity_per_order);
        }

        // Persist guest row update aggregated per-variant across all session lines
        $sessionToken = Session::get('guest.session_token');
        if ($sessionToken) {
            $expiresAt = now()->addMinutes((int) config('sku.cart_reserve_minutes', 60));

            // Compute total qty for this variant after applying this line's change
            $itemsForVariant = array_filter($items, fn($it) => (int)($it['variant_id'] ?? 0) === (int)$variant->id);
            $itemsForVariant[$itemId]['qty'] = $qty; // ensure this line is reflected
            $totalQty = 0;
            foreach ($itemsForVariant as $it) { $totalQty += (int)($it['qty'] ?? 0); }

            $availabilityStatus = $this->calculateAvailabilityStatus($variant, $totalQty);

            DB::statement('
                INSERT INTO guest_cart_items (session_token, product_id, variant_id, quantity, expires_at, availability_status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, now(), now())
                ON CONFLICT (session_token, variant_id)
                DO UPDATE SET
                    quantity    = EXCLUDED.quantity,
                    expires_at  = EXCLUDED.expires_at,
                    availability_status= EXCLUDED.availability_status,
                    updated_at  = now()
            ', [
                $sessionToken,
                $variant->product_id,
                $variant->id,
                $totalQty,
                $expiresAt,
                $availabilityStatus,
            ]);
        }

        // Update session snapshot
        $items[$itemId]['qty'] = $qty;
        $items[$itemId]['subtotal'] = $items[$itemId]['unit_price'] * $items[$itemId]['qty'];
        $items[$itemId]['backorder_qty'] = ($variant->track_inventory && $variant->allow_backorders)
            ? max(0, $qty - min($qty, (int)$variant->availableStock()))
            : 0;
        $items[$itemId]['availability_status'] = $this->calculateAvailabilityStatus($variant, (int)$items[$itemId]['qty']);
        Session::put('cart.items', $items);

        // Cart changed: keep any existing checkout reservation; we'll adjust at checkout time.
        // Intentionally do not release or clear the reservation snapshot here.

        $this->recalculateCartTotal();
        return $this->getCart();
    }

    public function removeCartItem(string $itemId): array
    {
        if ($this->isAuthenticated()) {
            return DB::transaction(function () use ($itemId) {
                $userId = $this->getCurrentUser()->id;
                $sessionItems = Session::get('cart.items', []);
                $snapshot = $sessionItems[$itemId] ?? null;
                if ($snapshot) {
                    $cartItem = CartItem::query()
                        ->where('user_id', $userId)
                        ->where('variant_id', $snapshot['variant_id'] ?? 0)
                        ->first();
                    if ($cartItem) {
                        // New flow: no reservation at cart time, just delete cart row
                        $cartItem->delete();
                    }
                }
                $this->syncSessionFromDb($userId);
                return $this->getCart();
            });
        }

        // Guest session flow: just remove session snapshot (no reservation to release)
        $sessionToken = Session::get('guest.session_token');
        if ($sessionToken) {
            DB::transaction(function () use ($itemId, $sessionToken) {
                $sessionItems = Session::get('cart.items', []);
                $snapshot = $sessionItems[$itemId] ?? null;
                if (!$snapshot) return;

                $guest = \App\Models\GuestCartItem::query()
                    ->where('session_token', $sessionToken)
                    ->where('variant_id', $snapshot['variant_id'] ?? 0)
                    ->lockForUpdate()
                    ->first();
                if ($guest) {
                    $guest->delete();
                }
            });
        }

        $items = Session::get('cart.items', []);
        unset($items[$itemId]);
        Session::put('cart.items', $items);
        $this->recalculateCartTotal();
        return $this->getCart();
    }

    private function recalculateCartTotal(): void
    {
        $items = Session::get('cart.items', []);
        $total = 0;
        foreach ($items as $it) {
            $line = (float) $it['subtotal'];
            // Sum addon subtotals into line
            if (!empty($it['addons']) && is_array($it['addons'])) {
                foreach ($it['addons'] as $ad) {
                    $line += (float) ($ad['subtotal'] ?? 0);
                }
            }
            $total += $line;
        }
        Session::put('cart.total', $total);
        // set/refresh reservation expiry if configured
        $minutes = (int) config('sku.cart_reserve_minutes', 0);
        if ($minutes > 0) {
            Session::put('cart.reserved_until', now()->addMinutes($minutes)->toIso8601String());
        }
    }

    /**
     * Shared helper to map DB cart items (CartItem or GuestCartItem) into session snapshot.
     *
     * @param \Illuminate\Support\Collection|array $dbItems
     * @param string $idPrefix prefix to use for session item ids (e.g. 'db_' or 'guest_')
     * @return void
     */
    private function syncSessionFromDbItems($dbItems, string $idPrefix = 'db_'): void
    {
        $sessionItems = [];
        foreach ($dbItems as $ci) {
            $variant = $ci->variant;
            if (!$variant) continue;
            $price = (float) $variant->selling_price;
            $itemId = $idPrefix . $ci->id; // deterministic id mapping for session snapshot

            // Build addon pricing snapshot
            $addons = [];
            foreach ($ci->addons as $ad) {
                $v = $ad->variant;
                if (!$v) continue;

                // Add-on discount information based on compare_price vs selling_price
                $addonCompare = $v->compare_price ? (float) $v->compare_price : null;
                $addonPrice   = (float) $v->selling_price;
                $addonSaved   = 0;
                $addonDiscPct = 0;
                if ($addonCompare && $addonCompare > $addonPrice) {
                    $addonSaved   = ($addonCompare - $addonPrice) * (int)$ad->quantity;
                    $addonDiscPct = round((($addonCompare - $addonPrice) / $addonCompare) * 100);
                }

                $addons[] = [
                    'product_id' => $ad->addon_product_id,
                    'variant_id' => $ad->addon_variant_id,
                    'name' => optional($v->product)->name,
                    'variant_name' => self::formatVariantName($v->variant_attributes ?? []),
                    'sku' => $v->sku,
                    'qty' => (int) $ad->quantity,
                    'unit_price' => $addonPrice,
                    'subtotal' => $addonPrice * (int)$ad->quantity,
                    'compare_price' => $addonCompare,
                    'discount_percent' => $addonDiscPct,
                    'saved_amount' => $addonSaved,
                    'is_required' => (bool) $ad->is_required,
                ];
            }

            // Get primary image for the cart item
            $primaryImage = $variant->getPrimaryImage();
            $imageUrl = $primaryImage ? $primaryImage->file_url : null;

            // Calculate discount information
            $comparePrice = $variant->compare_price ? (float) $variant->compare_price : null;
            $discountPercent = 0;
            $savedAmount = 0;
            
            if ($comparePrice && $comparePrice > $price) {
                $discountPercent = round((($comparePrice - $price) / $comparePrice) * 100);
                $savedAmount = ($comparePrice - $price) * (int)$ci->quantity;
            }

            $sessionItems[$itemId] = [
                'id' => $itemId,
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'name' => optional($variant->product)->name,
                'variant_display_name' => self::formatVariantName($variant->variant_attributes ?? []),
                'qty' => (int)$ci->quantity,
                'unit_price' => $price,
                'subtotal' => $price * (int)$ci->quantity,
                'compare_price' => $comparePrice,
                'discount_percent' => $discountPercent,
                'saved_amount' => $savedAmount,
                'image_url' => $imageUrl,
                'addons' => $addons,
                'shippable' => (bool) $variant->product->shippable,
                'availability_status' => $this->calculateAvailabilityStatus($variant, (int)$ci->quantity),
                // Backorder indicator from DB; split computed from stock only (no reserved quantity tracked on cart rows)
                'is_backorder' => $this->calculateAvailabilityStatus($variant, (int)$ci->quantity) !== 'in_stock',
                'backorder_qty' => ($variant->track_inventory && $variant->allow_backorders)
                    ? max(0, (int)$ci->quantity - min((int)$ci->quantity, (int)$variant->availableStock()))
                    : 0,
            ];
        }

        Session::put('cart.items', $sessionItems);
        $this->recalculateCartTotal();
    }

    // Sync session snapshot from DB for authenticated user
    private function syncSessionFromDb(int $userId): void
    {
        $dbItems = CartItem::with(['variant.product', 'addons.variant.product'])
            ->where('user_id', $userId)
            ->get();

        $this->syncSessionFromDbItems($dbItems, 'db_');
    }

    // Sync session snapshot from DB for guest user
    private function syncGuestSessionFromDb(string $sessionToken): void
    {
        $dbItems = GuestCartItem::with(['variant.product', 'addons.variant.product'])
            ->where('session_token', $sessionToken)
            ->get();

        $this->syncSessionFromDbItems($dbItems, 'guest_');
    }

    // Merge session cart into DB after login
    public function mergeSessionCartIntoDb(int $userId): void
    {
        \Log::info('ECommerceService: mergeSessionCartIntoDb called', [
            'user_id' => $userId,
            'session_all_keys' => array_keys(Session::all()),
            'session_id' => Session::getId(),
        ]);

        $items = Session::get('cart.items', []);
        $sessionToken = Session::get('guest.session_token');
        $hasSessionItems = !empty($items);
        $hasGuestItems = false;

        \Log::info('ECommerceService: Session analysis', [
            'user_id' => $userId,
            'session_token_raw' => $sessionToken,
            'session_cart_items' => $items,
            'has_session_items' => $hasSessionItems,
            'guest_session_token_exists' => Session::has('guest.session_token'),
        ]);

        // Check if there are any guest cart items to migrate
        if ($sessionToken) {
            $guestCount = \App\Models\GuestCartItem::where('session_token', $sessionToken)->count();
            $hasGuestItems = $guestCount > 0;

            \Log::info('ECommerceService: Guest cart check', [
                'user_id' => $userId,
                'session_token' => $sessionToken,
                'guest_items_count' => $guestCount,
                'has_guest_items' => $hasGuestItems,
                'guest_items_sample' => \App\Models\GuestCartItem::where('session_token', $sessionToken)->take(3)->get(['id', 'variant_id', 'quantity'])->toArray(),
            ]);
        } else {
            \Log::info('ECommerceService: No session token found', [
                'user_id' => $userId,
                'all_session_keys' => array_keys(Session::all()),
            ]);
        }

        // If no session items and no guest items, nothing to do
        if (!$hasSessionItems && !$hasGuestItems) {
            \Log::info('ECommerceService: No items to merge, exiting', [
                'user_id' => $userId,
            ]);
            return;
        }

        \Log::info('ECommerceService: Starting cart merge', [
            'user_id' => $userId,
            'has_session_items' => $hasSessionItems,
            'has_guest_items' => $hasGuestItems,
            'session_token' => $sessionToken,
        ]);

        DB::transaction(function () use ($items, $userId, $hasSessionItems, $hasGuestItems) {
            $sessionToken = Session::get('guest.session_token');

            \Log::info('ECommerceService: Inside transaction', [
                'user_id' => $userId,
                'session_token' => $sessionToken,
            ]);

            // Aggregate session quantities per variant to avoid multiple additions
            $byVariant = [];
            if ($hasSessionItems) {
                \Log::info('ECommerceService: Processing session items', [
                    'user_id' => $userId,
                    'session_items_count' => count($items),
                ]);
                foreach ($items as $snap) {
                    $vid = (int)($snap['variant_id'] ?? 0);
                    $q = max(1, (int)($snap['qty'] ?? 1));
                    if ($vid <= 0) continue;
                    $byVariant[$vid] = ($byVariant[$vid] ?? 0) + $q;
                }
            }

            // If we have guest items but no session items, collect all guest cart items
            if (!$hasSessionItems && $hasGuestItems && $sessionToken) {
                \Log::info('ECommerceService: Processing guest items', [
                    'user_id' => $userId,
                    'session_token' => $sessionToken,
                ]);
                $guestItems = \App\Models\GuestCartItem::where('session_token', $sessionToken)->get();
                \Log::info('ECommerceService: Found guest items', [
                    'user_id' => $userId,
                    'guest_items_count' => $guestItems->count(),
                    'guest_items' => $guestItems->map(function($item) {
                        return [
                            'id' => $item->id,
                            'variant_id' => $item->variant_id,
                            'quantity' => $item->quantity,
                            'addons_count' => $item->addons->count(),
                        ];
                    })->toArray(),
                ]);
                foreach ($guestItems as $guestItem) {
                    $vid = (int)$guestItem->variant_id;
                    if ($vid <= 0) continue;
                    $byVariant[$vid] = ($byVariant[$vid] ?? 0) + (int)$guestItem->quantity;
                }
            }

            \Log::info('ECommerceService: Variants to process', [
                'user_id' => $userId,
                'variants_count' => count($byVariant),
                'variants' => $byVariant,
            ]);

            foreach ($byVariant as $variantId => $sessionQty) {
                $variant = ProductVariant::lockForUpdate()->find($variantId);
                if (!$variant) continue;

                // Authoritative quantity: prefer guest DB row; else use session aggregate
                $guest = null;
                $finalQty = max(1, (int)$sessionQty);
                if ($sessionToken) {
                    $guest = \App\Models\GuestCartItem::query()
                        ->where('session_token', $sessionToken)
                        ->where('variant_id', $variantId)
                        ->lockForUpdate()
                        ->first();
                    if ($guest) {
                        $finalQty = max(1, (int)$guest->quantity);
                    }
                }

                $cartItem = CartItem::query()->where('user_id', $userId)->where('variant_id', $variantId)->first();
                $expiresAt = now()->addMinutes((int) config('sku.cart_reserve_minutes', 60));

                \Log::info('ECommerceService: Cart item lookup', [
                    'user_id' => $userId,
                    'variant_id' => $variantId,
                    'existing_cart_item_id' => $cartItem ? $cartItem->id : null,
                ]);

                if ($cartItem) {
                    // Replace with authoritative guest quantity (do not sum)
                    $cartItem->quantity = $finalQty;
                    $cartItem->expires_at = $expiresAt;
                    $cartItem->availability_status = $this->calculateAvailabilityStatus($variant, $finalQty);
                    $cartItem->save();
                } else {
                    $cartItem = CartItem::create([
                        'user_id' => $userId,
                        'product_id' => $variant->product_id,
                        'variant_id' => $variantId,
                        'quantity' => $finalQty,
                        'expires_at' => $expiresAt,
                        'availability_status' => $this->calculateAvailabilityStatus($variant, $finalQty),
                    ]);

                    \Log::info('ECommerceService: Created new cart item', [
                        'user_id' => $userId,
                        'cart_item_id' => $cartItem->id,
                        'variant_id' => $variantId,
                        'quantity' => $finalQty,
                    ]);
                }

                if ($guest) {
                    // Migrate guest addons to cart addons
                    $guestAddons = $guest->addons;
                    \Log::info('ECommerceService: Migrating guest addons', [
                        'user_id' => $userId,
                        'variant_id' => $variantId,
                        'guest_addons_count' => $guestAddons->count(),
                        'cart_item_id' => $cartItem->id,
                    ]);

                    if ($guestAddons->isNotEmpty()) {
                        $cartItem->addons()->delete(); // Clear existing addons
                        foreach ($guestAddons as $guestAddon) {
                            CartAddon::create([
                                'cart_item_id' => $cartItem->id,
                                'addon_product_id' => $guestAddon->addon_product_id,
                                'addon_variant_id' => $guestAddon->addon_variant_id,
                                'quantity' => $guestAddon->quantity,
                                'is_required' => $guestAddon->is_required,
                            ]);

                            \Log::info('ECommerceService: Migrated guest addon', [
                                'user_id' => $userId,
                                'cart_item_id' => $cartItem->id,
                                'addon_product_id' => $guestAddon->addon_product_id,
                                'addon_variant_id' => $guestAddon->addon_variant_id,
                                'quantity' => $guestAddon->quantity,
                            ]);
                        }
                    }

                    // Remove guest row after migration
                    $guest->delete();
                }
            }
        });

        // Clear session cart after merge and rebuild from DB
        Session::forget('cart');
        $this->syncSessionFromDb($userId);

        \Log::info('ECommerceService: Cart merge completed successfully', [
            'user_id' => $userId,
            'session_token_cleared' => $sessionToken,
        ]);
    }

    // -------------------------
    // Guest upsert helpers (DB only, do not touch session)
    // -------------------------
    public function upsertGuestVariantQty(int $variantId, int $qty): void
    {
        $qty = max(1, (int)$qty);
        $variant = ProductVariant::find($variantId);
        if (!$variant) return;
        $sessionToken = Session::get('guest.session_token');
        if (!$sessionToken) return;

        $expiresAt = now()->addMinutes((int) config('sku.cart_reserve_minutes', 60));
        $availabilityStatus = $this->calculateAvailabilityStatus($variant, $qty);

        DB::statement('
            INSERT INTO guest_cart_items (session_token, product_id, variant_id, quantity, expires_at, availability_status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, now(), now())
            ON CONFLICT (session_token, product_id, variant_id)
            DO UPDATE SET
                quantity    = EXCLUDED.quantity,
                expires_at  = EXCLUDED.expires_at,
                availability_status= EXCLUDED.availability_status,
                updated_at  = now()
        ', [
            $sessionToken,
            $variant->product_id,
            $variant->id,
            $qty,
            $expiresAt,
            $availabilityStatus,
        ]);
    }

    // -------------------------
    // Helpers
    // -------------------------
    private function baseProductQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Only list actual sellable catalog items by default (exclude add-ons)
        // Add-ons are attached to parent products and should not appear in listing/landing grids.
        return Product::query()
            ->where('status', 'published')
            ->where('product_type', '!=', 'addon');
    }

    private function cardWithRelations(): array
    {
        return [
            'variants.mediaAssets' => function($q){ $q->orderBy('display_order'); },
            'generalImages' => function($q){ $q->orderBy('display_order'); },
        ];
    }

    // Build breadcrumb path for a category id (root → ... → selected)
    public function buildCategoryBreadcrumbs(?int $categoryId): array
    {
        if (!$categoryId) return [];
        $cat = \App\Models\Category::with('parent')->find($categoryId);
        if (!$cat) return [];
        $stack = collect();
        $cur = $cat;
        // Climb to root
        while ($cur) { $stack->prepend($cur); $cur = $cur->parent; }
        $out = [];
        foreach ($stack as $c) {
            $out[] = [
                'id' => $c->id,
                'name' => $c->name,
                'url' => route('shop.list', ['category_id' => $c->id]),
            ];
        }
        return $out;
    }

    // Attribute meta for current product's attribute types/values (e.g., color_hex)
    public function getAttributeMeta(Product $product): array
    {
         // Only include attribute_meta for variant products
        if ($product->product_type !== 'variant') {
            return [];
        }
        // Determine which attribute types/values are actually used by this product's variants
        $usedByType = []; // [TypeName => [values]]
        foreach ($product->variants as $v) {
            $attrs = (array) ($v->attributes ?? []);
            foreach ($attrs as $k => $val) {
                if ($val === null || $val === '') continue;
                $usedByType[$k] = $usedByType[$k] ?? [];
                if (!in_array($val, $usedByType[$k], true)) {
                    $usedByType[$k][] = $val;
                }
            }
        }

        // If product has configured attribute type IDs, prefer those; else derive from used keys
        $typeQuery = VariantAttributeType::query()->with(['values' => function($q){ $q->orderBy('sort_order'); }])
            ->orderBy('display_order');
        if (!empty($product->variant_attribute_type_ids) && is_array($product->variant_attribute_type_ids)) {
            $typeQuery->whereIn('id', $product->variant_attribute_type_ids);
        }
        $types = $typeQuery->get();

        $meta = [];
        foreach ($types as $type) {
            $tname = $type->name;
            $allowedValues = $usedByType[$tname] ?? null; // if null, include all defined values
            foreach ($type->values as $val) {
                if (is_array($allowedValues) && !in_array($val->value, $allowedValues, true)) continue;
                
                // Start with the base metadata
                $valueMeta = [
                    'image_url' => $val->image_url,
                ];

                // Conditionally include 'color_hex' only if it's not null
                if ($val->color_hex !== null) {
                    $valueMeta['color_hex'] = $val->color_hex;
                }
                
                $meta[$tname][$val->value] = $valueMeta;
            }
        }
        return $meta;
    }

   private function calculateAvailabilityStatus(ProductVariant $variant, int $qty): string
   {
       if (!$variant->track_inventory) {
           return 'in_stock';
       }
       $available = $variant->availableStock();
       if ($qty <= $available) {
           return 'in_stock';
       }
       if (!$variant->allow_backorders) {
           \Log::warning('Invalid state: qty exceeds available but backorders not allowed', [
            'variant_id' => $variant->id,
            'qty' => $qty,
            'available' => $available
            ]);
            return 'out_of_stock';
       }
       return $available > 0 ? 'partially_backordered' : 'backordered';
   }
}