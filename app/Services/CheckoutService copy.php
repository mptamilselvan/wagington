<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAddon;
use App\Models\OrderVoucher;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\CartItem;
use App\Models\GuestCartItem;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use RuntimeException;
use Stripe\Customer;
use Stripe\PaymentMethod;
use App\Services\VoucherService;

class CheckoutService
{
    public function __construct(
        private PaymentService $payments,
        private ECommerceService $shop,
        private ShippingService $shipping
    ) {}

    /**
     * Build a unified checkout summary with all calculations
     * 
     * @param array $cart Cart snapshot from ECommerceService->getCart()
     * @param array $couponCodes List of coupon codes to apply
     * @param int|null $shippingAddressId User's saved shipping address ID
     * @param int|null $billingAddressId User's saved billing address ID
     * @param string|null $paymentMethodId Optional Stripe payment method ID
     * @param int|null $userId User ID for coupon validation
     * @return array Checkout summary with all calculations
     */
    public function buildUnifiedSummary(
        array $cart,
        array $couponCodes,
        ?int $shippingAddressId,
        ?int $billingAddressId,
        ?string $paymentMethodId,
        ?int $userId
    ): array {
        // Calculate subtotal
        $subtotal = $this->calculateCartSubtotal($cart);
        
        // Get shipping address if available
        $shippingAddress = null;
        if ($shippingAddressId && $userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $shippingAddress = $user->addresses()->find($shippingAddressId);
            }
        }
        
        // Calculate shipping amount
        $shippingAmount = 0.0;
        if ($shippingAddress) {
            $regionContext = $this->deriveRegionFromAddress([
                'country' => $shippingAddress->country,
                'state' => $shippingAddress->state ?? null,
                'region' => $shippingAddress->region ?? null,
                'postal_code' => $shippingAddress->postal_code,
            ]);
            
            $shippingAmount = $this->calculateShippingForCart($cart, $regionContext);
        }
        
        // Prepare tax context
        $regionCode = null;
        if ($shippingAddress) {
            $regionCode = $this->deriveRegionFromAddress([
                'country' => $shippingAddress->country,
                'state' => $shippingAddress->state ?? null,
                'region' => $shippingAddress->region ?? null,
                'postal_code' => $shippingAddress->postal_code,
            ]);
        }
        
        $taxContext = ['region' => $regionCode];
        
        // If we have coupon codes, use evaluateCoupons to process them
        if (!empty($couponCodes)) {
            return $this->evaluateCoupons(
                $cart,
                $couponCodes,
                $subtotal,
                $shippingAmount,
                $regionCode,
                $taxContext,
                $userId
            );
        }
        
        // Calculate tax without coupons
        $taxCalculation = $this->calculateCartTax($subtotal, $taxContext);
        
        // Return summary structure
        return [
            'summary' => [
                'subtotal' => $subtotal,
                'discount_total' => 0.0,
                'total' => $subtotal + $shippingAmount + ($taxCalculation['amount'] ?? 0),
            ],
            'shipping_amount' => $shippingAmount,
            'tax' => $taxCalculation,
            'applied_vouchers' => [],
            'errors' => [],
            'stackability_message' => null,
            'cart' => $cart,
        ];
    }

    /**
     * Normalise region context to extract region code
     * 
     * @param array|string|null $regionContext
     * @return string|null
     */
    private function normaliseRegionContext(array|string|null $regionContext): ?string
    {
        if (is_string($regionContext)) {
            return $regionContext;
        }
        
        if (is_array($regionContext) && isset($regionContext['region'])) {
            return $regionContext['region'];
        }
        
        return null;
    }

    /**
     * Unified checkout method that accepts address IDs and optional payment method ID.
     * Fetches address data from database to calculate shipping rates.
     *
     * @param array $cart Cart snapshot from ECommerceService->getCart()
     * @param \App\Models\User $user Authenticated user (required for checkout)
     * @param int|null $shippingAddressId User's saved shipping address ID (null if no shipping required)
     * @param int $billingAddressId User's saved billing address ID
     * @param string|null $paymentMethodId Optional Stripe payment method ID (defaults to user's default)
     * @param string|null $sessionToken Guest session token for clearing guest cart
     * @param array|null $address Optional additional address data (e.g., coupon_code)
     * @return array { order: Order }
     * @throws RuntimeException on validation or payment failure
     */
    public function evaluateCheckoutSummary(
        array $cart,
        array $couponCodes,
        float $subtotal,
        float $shippingAmount,
        array|string|null $regionContext = null,
        ?int $userId = null
    ): array {
        $regionCode = $this->normaliseRegionContext($regionContext);
        $taxContext = ['region' => $regionCode];

        // Only recalculate shipping if a region code is provided
        if ($this->cartRequiresShipping($cart) && $regionCode) {
            $shippingAmount = $this->calculateShippingForCart($cart, $regionCode);
        } elseif (!$this->cartRequiresShipping($cart)) {
            $shippingAmount = 0.0;
        }

        return $this->evaluateCoupons(
            $cart,
            $couponCodes,
            $subtotal,
            $shippingAmount,
            $regionCode,
            $taxContext,
            $userId
        );
    }

    public function evaluateCoupons(
        array $cart,
        array $couponCodes,
        float $subtotal,
        float $shippingAmount,
        ?string $regionCode,
        array $taxContext,
        ?int $userId
    ): array {
        $cartRequiresShipping = $this->cartRequiresShipping($cart);
        // Only recalculate shipping if we have a region code and shipping is required
        if ($cartRequiresShipping && empty($shippingAmount) && $regionCode) {
            $shippingAmount = $this->calculateShippingForCart($cart, $regionCode);
        } elseif (!$cartRequiresShipping) {
            $shippingAmount = 0.0;
        }

        $voucherService = app(VoucherService::class);
        $validatedCoupons = $voucherService->validateMultipleVouchers($couponCodes, $userId ?? 0);

        $validVouchers = $validatedCoupons['valid'] ?? [];
        $errors = $validatedCoupons['errors'] ?? [];

        if (!empty($validVouchers)) {
            $validVouchers = array_map(function ($voucher) use ($subtotal) {
                $redeemable = $voucher['redeemable_amount'] ?? $voucher['discount_value'] ?? 0;
                if (($voucher['discount_type'] ?? '') === 'fixed') {
                    $redeemable = min((float) $redeemable, $subtotal);
                }
                $voucher['redeemable_amount'] = (float) max(0.0, $redeemable);
                return $voucher;
            }, $validVouchers);
        }

        $stackabilityResult = $voucherService->checkStackability($validVouchers, $subtotal);
        $applicableVouchers = $stackabilityResult['valid'];

        usort($applicableVouchers, function ($a, $b) {
            $priorityA = $this->calculateStackPriority($a);
            $priorityB = $this->calculateStackPriority($b);
            return $priorityB <=> $priorityA;
        });

        $discountResult = $voucherService->calculateStackedDiscount($applicableVouchers, $subtotal);
        $discountTotal = min((float) $discountResult['total_discount'], $subtotal);

        $taxCalculation = $this->calculateCartTax(max(0.0, $subtotal - $discountTotal), $taxContext);

        $usableVouchers = array_map(function ($voucher) use (&$discountResult) {
            $code = $voucher['voucher_code'] ?? $voucher['code'] ?? null;
            if (!$code) {
                return $voucher;
            }

            $appliedBreakdown = collect($discountResult['breakdown'] ?? [])
                ->first(fn ($row) => ($row['voucher_code'] ?? $row['code'] ?? null) === $code);

            if ($appliedBreakdown) {
                $voucher['applied_amount'] = (float) ($appliedBreakdown['discount'] ?? 0.0);
            }

            return $voucher;
        }, $applicableVouchers);

        return [
            'summary' => [
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'total' => max(0.0, $subtotal - $discountTotal) + $shippingAmount + ($taxCalculation['amount'] ?? 0),
            ],
            'tax' => $taxCalculation,
            'shipping_amount' => $shippingAmount,
            'applied_vouchers' => $discountResult['breakdown'] ?? $usableVouchers,
            'errors' => $errors,
            'stackability_message' => $stackabilityResult['message'] ?? null,
        ];
    }

    public function checkoutUnified(
        array $cart,
        \App\Models\User $user,
        ?int $shippingAddressId,
        int $billingAddressId,
        ?string $paymentMethodId = null,
        ?string $sessionToken = null,
        ?array $address = null
    ): array {
        \Log::info('CheckoutService.checkoutUnified: Method called', [
            'user_id' => $user->id,
            'cart_items_count' => count($cart['items'] ?? []),
            'shipping_address_id' => $shippingAddressId,
            'billing_address_id' => $billingAddressId,
            'payment_method_id' => $paymentMethodId,
        ]);

        if (empty($cart['items'])) {
            throw new RuntimeException('Cart is empty');
        }

        // Validate addresses belong to user
        $shippingAddress = $shippingAddressId ? $user->addresses()->find($shippingAddressId) : null;
        $billingAddress = $user->addresses()->find($billingAddressId);

        if ($shippingAddressId && !$shippingAddress) {
            throw new RuntimeException('Invalid shipping address ID');
        }
        if (!$billingAddress) {
            throw new RuntimeException('Invalid billing address ID');
        }

        // Calculate shipping amount based on shipping requirements
        // If no shipping address provided (for non-shippable products), shipping amount should be 0
        if ($shippingAddress) {
            $derivedRegion = $this->deriveRegionFromAddress([
                'country' => $shippingAddress->country,
                'state' => $shippingAddress->state ?? null,
                'region' => $shippingAddress->region ?? null,
                'postal_code' => $shippingAddress->postal_code,
            ]);

            \Log::info('CheckoutService.checkoutUnified: region derivation', [
                'shipping_country' => $shippingAddress->country,
                'shipping_postal_code' => $shippingAddress->postal_code,
                'derived_region' => $derivedRegion,
            ]);

            $shippingAmount = $this->calculateShippingForCart($cart, $derivedRegion);
        } else {
            // No shipping address means no shippable products, so shipping amount is 0
            $shippingAmount = 0.0;
            \Log::info('CheckoutService.checkoutUnified: no shipping required, setting shipping amount to 0');
        }
        \Log::info('CheckoutService.checkoutUnified: shipping computed', [
            'shipping_amount' => $shippingAmount,
        ]);

        // Phase 1: Always perform a fresh reservation, discarding any session-based data.
        $phase1 = $this->reserveCart($cart);
        $reservedMap = $phase1['reservedMap'];
        $hasBackorder = (bool) $phase1['hasBackordered'];
        $subtotal = (float) $phase1['subtotal'];
        $updatedCart = $phase1['updatedCart'];

         // Correctly determine the status based on the flags returned by reserveCart.
        $orderStatus = 'processing';
        if ($phase1['hasBackordered']) {
            $orderStatus = 'backordered';
        } elseif ($phase1['hasPartiallyBackordered']) {
            $orderStatus = 'partially_backordered';
        }
        \Log::info('CheckoutService.checkoutUnified: Order status determined', ['order_status' => $orderStatus]);


        \Log::info('Checkout: checkoutUnified reserveCart path, addons after reservation', [
            'cart_addons' => array_map(function($it) {
                return array_map(function($ad) {
                    return ['qty' => $ad['qty'] ?? 1, 'reserved_quantity' => $ad['reserved_quantity'] ?? null];
                }, $it['addons'] ?? []);
            }, $updatedCart['items']),
        ]);

        // Compute discount, tax and final total for persistence and payment
        $discountAmount = 0.0;
        $appliedVouchers = [];
        $couponCodes = [];
        if ($address && isset($address['coupon_codes']) && is_array($address['coupon_codes'])) {
            $couponCodes = $address['coupon_codes'];
        }

        if (!empty($couponCodes)) {
            $evaluation = $this->evaluateCheckoutSummary(
                $updatedCart,
                $couponCodes,
                $subtotal,
                $shippingAmount,
                $shippingAddress ? $this->deriveRegionFromAddress([
                    'country' => $shippingAddress->country,
                    'state' => $shippingAddress->state ?? null,
                    'region' => $shippingAddress->region ?? null,
                    'postal_code' => $shippingAddress->postal_code,
                ]) : null,
                $user?->id
            );

            $discountAmount = $evaluation['summary']['discount_total'];
            $appliedVouchers = $evaluation['applied_vouchers'];
            $shippingAmount = $evaluation['shipping_amount'];
            $taxCalc = $evaluation['tax'];
        } else {
            $taxCalc = $this->calculateCartTax(max(0.0, $subtotal - $discountAmount), ['region' => $shippingAddress?->region]);
        }

        $taxAmount = (float)($taxCalc['amount'] ?? 0);
        $totalAmount = max(0.0, $subtotal - $discountAmount) + $shippingAmount + $taxAmount;
        \Log::info('CheckoutService.checkoutUnified: amounts computed', [
            'subtotal' => $subtotal,
            'shipping' => $shippingAmount,
            'discount' => $discountAmount,
            'tax' => $taxAmount,
            'total' => $totalAmount,
        ]);

        // Phase 2: Create order first, then handle payment with retries
        $order = $this->createPendingOrder($updatedCart, $user, $reservedMap, $hasBackorder, $subtotal, $discountAmount, $taxAmount, $totalAmount, $appliedVouchers);

        // Set the address IDs on the order
        $order->update([
            'shipping_address_id' => $shippingAddressId,
            'billing_address_id' => $billingAddressId,
            'shipping_amount' => $shippingAmount,
        ]);

        // Phase 3: Payment with retry logic (only if user present)
        $paymentIntentId = null;
        $maxRetries = config('app.payment_max_retries', 3);
        $paymentSuccess = false;

        if ($user) {
            $paymentResult = $this->attemptPaymentWithRetries($user, $order, $totalAmount, $maxRetries, $paymentMethodId);

            if ($paymentResult['success']) {
                $paymentSuccess = true;
                $paymentIntentId = $paymentResult['payment_intent_id'];

                \Log::info('CheckoutService.checkoutUnified: Payment succeeded after retries', [
                    'order_number' => $order->order_number,
                    'attempts' => $paymentResult['attempts'],
                    'payment_intent_id' => $paymentIntentId,
                    'payment_method_id' => $paymentMethodId,
                ]);
            } else {
                $this->releaseReservation($reservedMap);
                throw new RuntimeException($paymentResult['message'] ?? 'Payment failed after maximum retries');
            }
        }

        // Phase 4: Update order status and finalize inventory if payment succeeded
        if ($paymentSuccess) {
            $order->update(['status' => $orderStatus]);
            $this->finalizeInventoryAfterPayment($order, $updatedCart, $reservedMap, $hasBackorder, $orderStatus);

            // Increment voucher usage for all applied vouchers
            $orderVouchers = OrderVoucher::where('order_id', $order->id)->get();
            if ($orderVouchers->isNotEmpty()) {
                $voucherService = app(VoucherService::class);
                foreach ($orderVouchers as $orderVoucher) {
                    try {
                        $voucherService->incrementVoucherUsage($orderVoucher->voucher_id);
                        \Log::info('Incremented voucher usage', [
                            'voucher_id' => $orderVoucher->voucher_id,
                            'voucher_code' => $orderVoucher->voucher_code,
                            'order_id' => $order->id,
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Failed to increment voucher usage', [
                            'voucher_id' => $orderVoucher->voucher_id,
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // Attach order details to PaymentIntent for Stripe dashboard
        if ($user && !empty($paymentIntentId)) {
            try {
                $this->payments->attachOrderDetailsToPaymentIntent($user, $paymentIntentId, $order->order_number, $order->id);
            } catch (\Throwable $e) {
                \Log::warning('Failed to update Stripe PaymentIntent with order details', [
                    'payment_intent_id' => $paymentIntentId,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clear authenticated cart if user present
        if ($user && Auth::check()) {
            CartItem::where('user_id', $user->id)->delete();
        }

        // Clear session cart
        Session::forget('cart');

        // Clear guest cart rows for this session token if present
        if ($sessionToken) {
            GuestCartItem::where('session_token', $sessionToken)->delete();
        }

        return ['order' => $order];
    }
    
    /**
     * Reserve inventory for the items in the given cart.
     * Performs locking and moves quantities from stock_quantity -> reserved_stock.
     * Returns [updatedCart, reservedMap, hasBackorder, subtotal]
     * Modifies $cart by reference to set reserved_quantity on addons.
     */
    public function reserveCart(array $cart): array
    {
        // Use an internal copy of the cart that we will modify and return
        $updatedCart = $cart;
        
        return DB::transaction(function () use (&$updatedCart) {
            $variantIds = collect($updatedCart['items'])
                ->flatMap(function($it) {
                    $ids = [];
                    if (!empty($it['variant_id'])) { $ids[] = (int)$it['variant_id']; }
                    foreach (($it['addons'] ?? []) as $ad) {
                        if (!empty($ad['variant_id'])) { $ids[] = (int)$ad['variant_id']; }
                    }
                    return $ids;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $variants = ProductVariant::whereIn('id', $variantIds)->lockForUpdate()->get()->keyBy('id');

            $invalidItems = [];
            $reservedMap = [];
            $subtotal = 0.0;
            $transactionalStock = [];

            $hasInStock = false;
            $hasBackordered = false;
            $hasPartiallyBackordered = false;

            foreach ($updatedCart['items'] as &$it) {
                $variant = isset($it['variant_id']) ? ($variants[$it['variant_id']] ?? null) : null;
                if (!$variant) { $invalidItems[] = $it; continue; }

                $qty = (int) ($it['qty'] ?? 1);
                $inStock = $qty;
                if ($variant->track_inventory) {
                    $onHand = max(0, (int)$variant->stock_quantity - (int)$variant->reserved_stock);
                    $inStock = min($qty, $onHand);
                    if (!$variant->allow_backorders && $inStock < $qty) {
                        $invalidItems[] = $it; continue;
                    }
                    $reservedMap[$variant->id] = (int)(($reservedMap[$variant->id] ?? 0) + $inStock);
                    if ($inStock < $qty) { 
                        \Log::info('ReserveCart: Backorder detected for main item', ['variant_id' => $variant->id, 'in_stock' => $inStock, 'requested_qty' => $qty]);
                        // Updated logic to set the correct flag
                        if ($inStock > 0) {
                            $hasPartiallyBackordered = true;
                        } else {
                            $hasBackordered = true;
                        }
                    }
                } else {
                    $reservedMap[$variant->id] = (int)($reservedMap[$variant->id] ?? 0);
                    $hasInStock = true;
                }
                $it['reserved_quantity'] = $inStock;
                
                foreach (($it['addons'] ?? []) as &$ad) {
                    $adVarId = (int)($ad['variant_id'] ?? 0);
                    if ($adVarId <= 0) { continue; }
                    $adVariant = $variants[$adVarId] ?? null;
                    if (!$adVariant) { $invalidItems[] = ['addon' => $ad, 'parent' => $it]; continue; }
                    
                    \Log::info('ReserveCart: Addon inventory status check', [
                        'addon_variant_id' => $adVarId,
                        'stock_quantity' => (int)($adVariant->stock_quantity ?? 0),
                        'reserved_stock' => (int)($adVariant->reserved_stock ?? 0),
                        'on_hand' => max(0, (int)($adVariant->stock_quantity ?? 0) - (int)($adVariant->reserved_stock ?? 0)),
                        'requested_qty' => (int)($ad['qty'] ?? 1),
                    ]);

                    $adQty = (int)($ad['qty'] ?? 1);
                    $adInStock = $adQty;
                    
                    if ($adVariant->track_inventory) {
                        if (!isset($transactionalStock[$adVarId])) {
                            $transactionalStock[$adVarId] = max(0, (int)$adVariant->stock_quantity - (int)$adVariant->reserved_stock);
                        }
                        
                        $adInStock = min($adQty, $transactionalStock[$adVarId]);
                        
                        if (!$adVariant->allow_backorders && $adInStock < $adQty) {
                            $invalidItems[] = ['addon' => $ad, 'parent' => $it];
                            continue;
                        }
                        
                        $transactionalStock[$adVarId] -= $adInStock;
                        
                        $reservedMap[$adVarId] = (int)(($reservedMap[$adVarId] ?? 0) + $adInStock);
                        if ($adInStock < $adQty) { 
                            \Log::info('ReserveCart: Backorder detected for addon', ['addon_variant_id' => $adVarId, 'in_stock' => $adInStock, 'requested_qty' => $adQty]);
                            // Updated logic to set the correct flag
                             if ($adInStock > 0) {
                                $hasPartiallyBackordered = true;
                            } else {
                                $hasBackordered = true;
                            }
                        }
                    } else {
                        $reservedMap[$adVarId] = (int)($reservedMap[$adVarId] ?? 0);
                        $hasInStock = true;
                    }
                    
                    $ad['reserved_quantity'] = $adInStock;
                }

                $line = (float)($it['subtotal'] ?? 0);
                foreach (($it['addons'] ?? []) as $ad) {
                    $line += (float) ($ad['subtotal'] ?? 0);
                }
                $subtotal += $line;
            }

            if (!empty($invalidItems)) {
                $this->releaseReservation($reservedMap);
                throw new RuntimeException('Some items are no longer available or have expired.');
            }

            foreach ($reservedMap as $variantId => $inStock) {
                if ($inStock <= 0) continue;
                $variant = $variants[$variantId] ?? null;
                if (!$variant) continue;
                
                $oldReserved = (int)$variant->reserved_stock;
                $oldStock = (int)$variant->stock_quantity;
                
                $variant->stock_quantity = max(0, (int)$variant->stock_quantity - (int)$inStock);
                $variant->reserved_stock = (int)$variant->reserved_stock + (int)$inStock;
                $variant->save();

                // Log the reservation
                InventoryLog::create([
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'quantity' => $inStock,
                    'action' => 'stock_out',
                    'reason' => 'Inventory reserved during checkout.',
                    'reference_id' => null,
                    'reference_type' => null,
                    'user_id' => Auth::id() ?: null,
                    'stock_after' => (int)($variant->stock_quantity ?? 0),
                    'reserved_after' => (int)($variant->reserved_stock ?? 0)
                ]);
            }
            \Log::info('ReserveCart: Reservation completed successfully', [
                'reserved_map' => $reservedMap,
                'has_in_stock' => $hasInStock,
                'has_backordered' => $hasBackordered,
                'has_partially_backordered' => $hasPartiallyBackordered,
                'subtotal' => $subtotal
            ]);
            return compact('updatedCart', 'reservedMap', 'subtotal', 'hasInStock', 'hasBackordered', 'hasPartiallyBackordered');
        });
    }

    /**
     * Finalize inventory after successful payment
     * Moves reserved stock to sold stock and creates inventory logs
     */
    private function finalizeInventoryAfterPayment(\App\Models\Order $order, array $cart, array $reservedMap, bool $hasBackorder, string $orderStatus): void
    {
        \Log::info('CheckoutService: finalizeInventoryAfterPayment called', [
            'order_number' => $order->order_number,
            'order_status' => $orderStatus,
            'reserved_map_count' => count($reservedMap),
            'has_backorder' => $hasBackorder,
        ]);

        DB::transaction(function () use ($order, $cart, $reservedMap, $hasBackorder, $orderStatus) {
            \Log::info('CheckoutService: Starting inventory finalization transaction', [
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'reserved_map' => $reservedMap,
                'has_backorder' => $hasBackorder,
                'order_status' => $orderStatus,
            ]);

            foreach ($reservedMap as $variantId => $inStock) {
                if ($inStock <= 0) continue;
                $variant = ProductVariant::lockForUpdate()->find($variantId);
                if (!$variant || !$variant->track_inventory) {
                    \Log::info('CheckoutService: Skipping inventory finalization', [
                        'variant_id' => $variantId,
                        'variant_found' => !!$variant,
                        'track_inventory' => $variant ? $variant->track_inventory : null,
                    ]);
                    continue;
                }
                
                $oldReserved = (int)$variant->reserved_stock;
                $oldSold = (int)($variant->sold_stock ?? 0);
                $variant->reserved_stock = max(0, (int)$variant->reserved_stock - (int)$inStock);
                $variant->sold_stock = (int)($variant->sold_stock ?? 0) + (int)$inStock;
                $variant->save();

                \Log::info('CheckoutService: Finalized inventory for in-stock items', [
                    'variant_id' => $variantId,
                    'in_stock_quantity' => $inStock,
                    'expected_change' => [
                        'reserved_stock' => $oldReserved . ' → ' . $variant->reserved_stock . ' (-' . $inStock . ')',
                        'sold_stock' => $oldSold . ' → ' . $variant->sold_stock . ' (+' . $inStock . ')',
                    ],
                    'order_number' => $order->order_number,
                ]);

                InventoryLog::create([
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'quantity' => -$inStock,
                    'action' => 'sale',
                    'reason' => 'Order '.$order->order_number.' (in-stock portion) - Status: '.$orderStatus,
                    'reference_id' => $order->id,
                    'reference_type' => get_class($order),
                    'user_id' => $order->user_id ?: null,
                    'stock_after' => (int)($variant->stock_quantity ?? 0) - (int)($variant->reserved_stock ?? 0),
                ]);
            }

            $orderedByVariant = [];
            foreach ($cart['items'] as $ci) {
                $vid = (int)($ci['variant_id'] ?? 0);
                if ($vid > 0) {
                    $orderedByVariant[$vid] = ($orderedByVariant[$vid] ?? 0) + (int)($ci['qty'] ?? 0);
                }
                foreach (($ci['addons'] ?? []) as $ad) {
                    $adVid = (int)($ad['variant_id'] ?? 0);
                    if ($adVid > 0) {
                        $orderedByVariant[$adVid] = ($orderedByVariant[$adVid] ?? 0) + (int)($ad['qty'] ?? 1);
                    }
                }
            }
            foreach ($orderedByVariant as $variantId => $orderedQty) {
                $reserved = (int)($reservedMap[$variantId] ?? 0);
                $backordered = max(0, (int)$orderedQty - $reserved);
                if ($backordered <= 0) continue;

                $variant = ProductVariant::lockForUpdate()->find($variantId);
                if (!$variant || !$variant->track_inventory) {
                    \Log::info('CheckoutService: Skipping backorder inventory finalization', [
                        'variant_id' => $variantId,
                        'variant_found' => !!$variant,
                        'track_inventory' => $variant ? $variant->track_inventory : null,
                    ]);
                    continue;
                }

                $oldSold = (int)($variant->sold_stock ?? 0);
                $variant->sold_stock = (int)($variant->sold_stock ?? 0) + (int)$backordered;
                $variant->save();

                \Log::info('CheckoutService: Finalized inventory for backordered items', [
                    'variant_id' => $variantId,
                    'backordered_quantity' => $backordered,
                    'expected_change' => [
                        'sold_stock' => $oldSold . ' → ' . $variant->sold_stock . ' (+' . $backordered . ')',
                        'note' => 'Backordered items only update sold_stock (no reserved_stock changes)',
                    ],
                    'order_number' => $order->order_number,
                ]);

                InventoryLog::create([
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'quantity' => 0,
                    'action' => 'sale_backorder',
                    'reason' => 'Order '.$order->order_number.' (backordered qty '.$backordered.') - Status: '.$orderStatus,
                    'reference_id' => $order->id,
                    'reference_type' => get_class($order),
                    'user_id' => $order->user_id ?: null,
                    'stock_after' => (int)($variant->stock_quantity ?? 0) - (int)($variant->reserved_stock ?? 0),
                ]);
            }

            \Log::info('CheckoutService: Inventory finalization transaction completed successfully', [
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'order_status' => $orderStatus,
            ]);
        });

        \Log::info('CheckoutService: finalizeInventoryAfterPayment completed', [
            'order_number' => $order->order_number,
            'order_status' => $orderStatus,
        ]);
    }
    
    /**
     * Release a previously created reservation map.
     */
    public function releaseReservation(array $reservedMap): void
    {
        DB::transaction(function () use ($reservedMap) {
            foreach ($reservedMap as $variantId => $inStock) {
                if ($inStock <= 0) continue;
                $variant = ProductVariant::lockForUpdate()->find($variantId);
                if (!$variant) continue;
                $release = min((int)$inStock, max(0, (int)$variant->reserved_stock));
                if ($release <= 0) continue;
                $variant->reserved_stock = (int)$variant->reserved_stock - $release;
                $variant->stock_quantity = (int)$variant->stock_quantity + $release;
                $variant->save();
            }
        });
    }

    /**
     * Map an address payload to a region code understood by ShippingService.
     */
    public function deriveRegionFromAddress(?array $address): ?string
    {
        if (!is_array($address)) return null;
        $country = strtolower(trim((string)($address['country'] ?? '')));
        $state = strtolower(trim((string)($address['state'] ?? ($address['region'] ?? ''))));
        if ($country === '') return null;

        if (($country === 'us' || $country === 'ca') && $state !== '') {
            return $country.'-'.$state;
        }
        return $country;
    }

    public function cartRequiresShipping(array $cart): bool
    {
        foreach (($cart['items'] ?? []) as $item) {
            $variantId = (int)($item['variant_id'] ?? 0);
            if ($variantId <= 0) {
                continue;
            }

            $variant = ProductVariant::with('product:id,shippable')->find($variantId);
            if ($variant && $variant->product && $variant->product->shippable) {
                return true;
            }
        }

        return false;
    }

    public function calculateShippingForCart(array $cart, ?string $region): float
    {
        if (!$this->cartRequiresShipping($cart)) {
            return 0.0;
        }

        $regionCode = $region ?? 'default';
        return $this->shipping->calculate($regionCode, $cart);
    }

    public function calculateCartSubtotal(array $cart): float
    {
        $subtotal = 0.0;
        foreach (($cart['items'] ?? []) as $item) {
            $subtotal += (float)($item['subtotal'] ?? 0);
            foreach (($item['addons'] ?? []) as $addon) {
                $subtotal += (float)($addon['subtotal'] ?? 0);
            }
        }

        return $subtotal;
    }

    public function calculateCartTax(float $taxableAmount, array $context = []): array
    {
        $taxService = app(\App\Services\TaxService::class);

        $region = $context['region_code'] ?? ($context['region'] ?? null);
        if (is_array($region)) {
            $region = $region['code'] ?? null;
        }

        return $taxService->calculateTax($taxableAmount, $region);
    }

    /**
     * Calculate stack priority for a voucher
     */
    private function calculateStackPriority(array $voucher): int
    {
        if ($voucher['discount_type'] === 'percentage') {
            $base = 80; // Middle of 70-100 range
            return $base + (int)$voucher['discount_value'];
        } else {
            $base = 40; // Middle of 10-60 range
            return $base + (int)$voucher['discount_value'];
        }
    }

    private function createPendingOrder(array $cart, ?\App\Models\User $user, array $reservedMap, bool $hasBackorder, float $subtotal, float $discountAmount, float $taxAmount, float $totalAmount, array $appliedVouchers = []): \App\Models\Order
    {
        return DB::transaction(function () use ($cart, $user, $reservedMap, $hasBackorder, $subtotal, $discountAmount, $taxAmount, $totalAmount, $appliedVouchers) {
            // Calculate strikethrough discount amount (saved_amount from cart items)
            $strikethroughDiscountAmount = 0.0;
            foreach ($cart['items'] ?? [] as $item) {
                if (!empty($item['saved_amount']) && $item['saved_amount'] > 0) {
                    $strikethroughDiscountAmount += (float) $item['saved_amount'];
                }
                if (!empty($item['addons']) && is_array($item['addons'])) {
                    foreach ($item['addons'] as $addon) {
                        if (!empty($addon['saved_amount']) && $addon['saved_amount'] > 0) {
                            $strikethroughDiscountAmount += (float) $addon['saved_amount'];
                        }
                    }
                }
            }

            $order = Order::create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'user_id' => $user?->id ?? 0,
                'shipping_address_id' => null,
                'billing_address_id' => null,
                'status' => 'pending',
                'payment_failed_attempts' => 0,
                'shipping_method' => $cart['selected_shipping_method'] ?? null,
                'tracking_number' => null,
                'estimated_delivery' => null,
                'subtotal' => $subtotal,
                'coupon_discount_amount' => $discountAmount,
                'strikethrough_discount_amount' => $strikethroughDiscountAmount,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $cart['shipping_amount'] ?? 0,
                'total_amount' => $totalAmount,
            ]);

            // Store applied vouchers in order_vouchers table
            foreach ($appliedVouchers as $voucher) {
                OrderVoucher::create([
                    'order_id' => $order->id,
                    'voucher_id' => $voucher['voucher_id'],
                    'voucher_code' => $voucher['voucher_code'],
                    'discount_type' => $voucher['discount_type'],
                    'discount_value' => $voucher['discount_value'],
                    'calculated_discount' => $voucher['calculated_discount'],
                    'running_total_after' => $voucher['running_total_after'],
                    'stack_order' => $voucher['stack_order'],
                    'stack_priority' => $voucher['stack_priority'],
                ]);
            }

            // Create a temporary map to track reserved quantities for each variant as we iterate
            $tempReservedMap = $reservedMap;

            foreach ($cart['items'] as $it) {
                $variant = ProductVariant::with('product')->find($it['variant_id']);
                $reservedPart = (int)($it['reserved_quantity'] ?? 0);

                $oi = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $variant?->product_id,
                    'variant_id' => $variant?->id,
                    'product_name' => $it['name'],
                    'variant_display_name' => $it['variant_display_name'] ?? \App\Services\ECommerceService::formatVariantName($variant?->variant_attributes ?? []),
                    'sku' => $variant?->sku,
                    'product_attributes' => $variant?->variant_attributes ?? [],
                    'quantity' => $it['qty'],
                    'reserved_quantity' => $reservedPart,
                    'fulfilled_quantity' => 0,
                    'unit_price' => $it['unit_price'],
                    'total_price' => (float)$it['unit_price'] * (int)$it['qty'],
                ]);

                foreach (($it['addons'] ?? []) as $ad) {
                    $addonVariantId = (int)($ad['variant_id'] ?? 0);
                    $addonQty = (int)($ad['qty'] ?? 1);
                    
                    $reservedForThisAddon = 0;
                    if (isset($tempReservedMap[$addonVariantId]) && $tempReservedMap[$addonVariantId] > 0) {
                        $reservedForThisAddon = min($addonQty, $tempReservedMap[$addonVariantId]);
                        $tempReservedMap[$addonVariantId] -= $reservedForThisAddon;
                    }

                    \Log::info('Creating OrderAddon', [
                        'addon_variant_id' => $addonVariantId,
                        'qty' => $addonQty,
                        'reserved_quantity' => $reservedForThisAddon,
                    ]);
                    
                    OrderAddon::create([
                        'order_item_id' => $oi->id,
                        'addon_product_id' => $ad['product_id'] ?? null,
                        'addon_variant_id' => $addonVariantId,
                        'addon_name' => $ad['name'] ?? 'Addon',
                        'addon_variant_display_name' => $ad['variant_name'] ?? null,
                        'addon_sku' => $ad['sku'] ?? null,
                        'was_required' => (bool)($ad['is_required'] ?? false),
                        'quantity' => $addonQty,
                        'reserved_quantity' => $reservedForThisAddon,
                        'fulfilled_quantity' => 0,
                        'unit_price' => (float)($ad['unit_price'] ?? 0),
                        'total_price' => (float)($ad['subtotal'] ?? 0),
                    ]);
                }

                \Log::info('CheckoutService: Main order item created', [
                    'order_item_id' => $oi->id,
                    'variant_id' => $it['variant_id'],
                    'quantity' => $it['qty'],
                    'reserved_quantity' => $reservedPart,
                ]);
            }

            \Log::info('CheckoutService: Pending order created successfully', [
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'reserved_map' => $reservedMap,
                'has_backorder' => $hasBackorder,
            ]);

            return $order;
        });
    }

    private function attemptPaymentWithRetries(\App\Models\User $user, \App\Models\Order $order, float $totalAmount, int $maxRetries, ?string $paymentMethodId): array
    {
        $attempts = 0;
        $lastError = null;

        while ($attempts < $maxRetries) {
            $attempts++;

            try {
                $idempotencyKey = 'checkout_' . $order->id . '_attempt_' . $attempts . '_' . time();

                $result = $this->payments->chargePaymentMethod(
                    $user,
                    $totalAmount,
                    'sgd', // currency
                    'Order ' . $order->order_number, // description
                    $paymentMethodId,
                    $idempotencyKey,
                    [
                        'order_id' => (string) $order->id,
                        'order_number' => $order->order_number,
                    ]
                );

                if (($result['status'] ?? null) === 'error') {
                    $lastError = $result['message'];
                    // For card declines, we might want to retry with different method, but for now, fail immediately
                    if (str_contains($lastError, 'Card declined')) {
                        break;
                    }
                    continue;
                }

                // Create payment record in the database
                $paymentIntent = $result['payment_intent'];
                
                // Create the payment record first
                $payment = \App\Models\Payment::create([
                    'order_id' => $order->id,
                    'transaction_id' => $paymentIntent->id,
                    'invoice_id' => $paymentIntent->invoice_id ?? null,
                    'invoice_url' => $paymentIntent->invoice_url ?? null,
                    'invoice_pdf_url' => $paymentIntent->invoice_pdf_url ?? null,
                    'invoice_number' => $paymentIntent->invoice_number ?? null,
                    'payment_gateway' => 'stripe',
                    'status' => 'succeeded',
                    'amount' => $totalAmount,
                    'card_last4' => $paymentIntent->charges->data[0]->payment_method_details->card->last4 ?? null,
                ]);
                
                // If Stripe didn't provide invoice information, generate our own
                if (empty($payment->invoice_url) || empty($payment->invoice_pdf_url)) {
                    try {
                        $invoiceService = app(\App\Services\InvoiceService::class);
                        $invoiceService->saveInvoiceAndUpdatePayment($order, $payment);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to generate invoice for order', [
                            'order_id' => $order->id,
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage()
                        ]);
                        // Continue without invoice generation - don't fail the payment
                    }
                }

                return [
                    'success' => true,
                    'message' => $result['message'],
                    'attempts' => $attempts,
                    'payment_intent_id' => $result['payment_intent']->id,
                ];

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                \Log::warning('Payment attempt failed', [
                    'attempt' => $attempts,
                    'order_id' => $order->id,
                    'error' => $lastError,
                ]);
                
                // Create payment record for failed payment
                \App\Models\Payment::create([
                    'order_id' => $order->id,
                    'transaction_id' => 'failed_' . $order->id . '_' . time(),
                    'invoice_id' => null,
                    'invoice_url' => null,
                    'invoice_pdf_url' => null,
                    'invoice_number' => null,
                    'payment_gateway' => 'stripe',
                    'status' => 'failed',
                    'amount' => $totalAmount,
                    'card_last4' => null,
                ]);

            }
        }

        return [
            'success' => false,
            'message' => $lastError ?? 'Payment failed after ' . $maxRetries . ' attempts',
            'attempts' => $attempts,
        ];
    }

}
