<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use App\Services\ECommerceService;
use App\Services\PaymentService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAddon;
use App\Models\ProductVariant;
use App\Services\CustomerService;

class CheckoutController extends Controller
{
    public function __construct(private PaymentService $payments, private ECommerceService $shop, private \App\Services\CheckoutService $checkout) {}

    /**
     * @OA\Get(
     *   path="/api/ecommerce/checkout/summary",
     *   summary="Get checkout summary",
     *   tags={"E-commerce"},
     *   security={
     *     {"bearerAuth": {}}
     *   },
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Parameter(
     *     name="shipping_address_id",
     *     in="query",
     *     description="Optional shipping address ID to recalculate shipping and tax",
     *     required=false,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="billing_address_id",
     *     in="query",
     *     description="Optional billing address ID for summary context",
     *     required=false,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="payment_method_id",
     *     in="query",
     *     description="Optional Stripe payment method ID selected by the shopper",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="coupon_codes[]",
     *     in="query",
     *     description="Optional array of coupon codes to evaluate",
     *     required=false,
     *     @OA\Schema(type="array", @OA\Items(type="string"))
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Checkout summary retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Checkout summary retrieved successfully."),
     *       @OA\Property(property="cart", type="object"),
     *       @OA\Property(property="addresses", type="array", @OA\Items(type="object")),
     *       @OA\Property(property="payment_methods", type="array", @OA\Items(type="object")),
     *       @OA\Property(property="default_payment_method", type="object"),
     *       @OA\Property(property="summary", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Unauthenticated.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Cart is empty",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Cart is empty")
     *     )
     *   )
     * )
     */
    public function summary(Request $request)
    {
        $user = Auth::user();

        $cart = $this->shop->getCart();
        if (empty($cart['items'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart is empty',
            ], 422);
        }

        $defaultPm = null;
        $paymentMethods = [];

        $pmResponse = $this->payments->getPaymentMethods($user);
        if (($pmResponse['status'] ?? null) === 'success') {
            $paymentMethods = $pmResponse['payment_methods'] ?? ($pmResponse['data']['payment_methods'] ?? []);
        }

        $defaultResponse = $this->payments->getDefaultPaymentMethod($user);
        if (($defaultResponse['status'] ?? null) === 'success') {
            $defaultPm = $defaultResponse['default_payment_method']
                ?? ($defaultResponse['data']['default_payment_method'] ?? null);
        }

        /** @var \App\Services\CustomerService $customerService */
        $customerService = app(CustomerService::class);
        $addressResponse = $customerService->getCustomerAddresses($user->id);
        $addresses = [];
        if (($addressResponse['status'] ?? null) === 'success') {
            $addresses = collect($addressResponse['addresses'] ?? [])
                ->map(function ($address) {
                    return [
                        'id' => $address->id,
                        'label' => $address->label ?? null,
                        'address_type_id' => $address->address_type_id,
                        'address_type' => optional($address->addressType)->name,
                        'address_line1' => $address->address_line1,
                        'address_line2' => $address->address_line2,
                        'postal_code' => $address->postal_code,
                        'city' => $address->city,
                        'state' => $address->state,
                        'country' => $address->country,
                        'is_shipping_address' => (bool) $address->is_shipping_address,
                        'is_billing_address' => (bool) $address->is_billing_address,
                        'phone' => $address->phone,
                    ];
                })
                ->values()
                ->all();
        }

        $selectedShippingId = $request->query('shipping_address_id');
        $selectedBillingId = $request->query('billing_address_id');
        $selectedPaymentMethodId = $request->query('payment_method_id');

        if ($selectedShippingId !== null) {
            $selectedShippingId = (int) $selectedShippingId;
        }
        if ($selectedBillingId !== null) {
            $selectedBillingId = (int) $selectedBillingId;
        }

        $shippingAddress = null;
        if ($selectedShippingId) {
            $shippingAddress = $user->addresses()->find($selectedShippingId);
            if (!$shippingAddress) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Shipping address not found',
                ], 404);
            }
        }

        $billingAddress = null;
        if ($selectedBillingId) {
            $billingAddress = $user->addresses()->find($selectedBillingId);
            if (!$billingAddress) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Billing address not found',
                ], 404);
            }
        }

        $shippingRequired = $this->checkout->cartRequiresShipping($cart);
        $shippingAmount = 0.0;
        $regionContext = null;

        // Only calculate shipping if shipping is required AND a shipping address is explicitly selected
        if ($shippingRequired && $shippingAddress) {
            $regionContext = $this->checkout->deriveRegionFromAddress([
                'country' => $shippingAddress->country,
                'state' => $shippingAddress->state ?? null,
                'region' => $shippingAddress->region ?? null,
                'postal_code' => $shippingAddress->postal_code,
            ]);
            $shippingAmount = $this->checkout->calculateShippingForCart($cart, $regionContext);
        }

        $subtotal = $this->checkout->calculateCartSubtotal($cart);

        $couponCodes = [];
        if ($request->has('coupon_codes')) {
            $couponCodes = array_filter(array_map('trim', (array) $request->query('coupon_codes')));
        }

        $evaluation = $this->checkout->evaluateCheckoutSummary(
            $cart,
            $couponCodes,
            $subtotal,
            $shippingAmount,
            $regionContext,
            $user->id
        );

        // Format applied vouchers with detailed discount information
        $formattedVouchers = array_map(function ($voucher) {
            return [
                'voucher_code' => $voucher['voucher_code'],
                'discount_type' => $voucher['discount_type'],
                'discount_value' => $voucher['discount_value'],
                'calculated_discount' => $voucher['calculated_discount'] ?? 0,
                'running_total_after' => $voucher['running_total_after'] ?? 0,
                'stack_order' => $voucher['stack_order'] ?? 0,
                'stack_priority' => $voucher['stack_priority'] ?? 0,
                'applied_amount' => $voucher['applied_amount'] ?? $voucher['calculated_discount'] ?? 0,
                'stackable' => $voucher['stackable'] ?? false
            ];
        }, $evaluation['applied_vouchers'] ?? []);

        // Calculate total discount impact
        $totalDiscount = (float)($evaluation['summary']['discount_total'] ?? 0.0);
        
        // Format the response consistently with the web interface
        return response()->json([
            'status' => 'success',
            'message' => 'Checkout summary retrieved successfully.',
            'cart' => $cart,
            'addresses' => $addresses,
            'payment_methods' => $paymentMethods,
            'default_payment_method' => $defaultPm,
            'selected_shipping_address_id' => $shippingAddress?->id,
            'selected_billing_address_id' => $billingAddress?->id,
            'selected_payment_method_id' => $selectedPaymentMethodId,
            'requires_shipping' => $shippingRequired,
            'shipping_amount' => $evaluation['shipping_amount'],
            'tax' => $evaluation['tax'],
            'applied_vouchers' => $formattedVouchers,
            'summary' => array_merge($evaluation['summary'], [
                'formatted_subtotal' => 'S$' . number_format($subtotal, 2),
                'formatted_discount' => 'S$' . number_format($totalDiscount, 2),
                'formatted_total' => 'S$' . number_format($evaluation['summary']['total'], 2),
                'has_discounts' => $totalDiscount > 0,
                'discount_percentage' => $subtotal > 0 ? round(($totalDiscount / $subtotal) * 100, 1) : 0
            ]),
            'errors' => $evaluation['errors'] ?? [],
            'stackability_message' => $evaluation['stackability_message'] ?? null,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/ecommerce/checkout/apply-coupon",
     *   summary="Validate one or more coupons against the current cart",
     *   tags={"E-commerce"},
     *   security={
     *     {"bearerAuth": {}}
     *   },
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="coupon_code",
     *         type="string",
     *         example="PROMO-10",
     *         description="Optional new coupon code to evaluate together with existing ones"
     *       ),
     *       @OA\Property(
     *         property="coupon_codes",
     *         type="array",
     *         description="Existing coupon codes that are already applied on the client",
     *         @OA\Items(type="string"),
     *         example={"PROMO-10", "SAVE5"}
     *       ),
     *       @OA\Property(
     *         property="force_apply",
     *         type="boolean",
     *         example=false,
     *         description="Bypass stackability conflict checks and apply coupon directly. Used after user confirmation."
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Coupons evaluated or conflict detected",
     *     @OA\JsonContent(
     *       oneOf={
     *         @OA\Schema(
     *           @OA\Property(property="status", type="string", example="success"),
     *           @OA\Property(property="message", type="string", example="Coupon PROMO-10 applied"),
     *           @OA\Property(property="coupon_codes", type="array", @OA\Items(type="string")),
     *           @OA\Property(property="summary", type="object", description="Updated checkout summary totals"),
     *           @OA\Property(property="applied_vouchers", type="array", @OA\Items(type="object")),
     *           @OA\Property(property="stackability_message", type="string", nullable=true)
     *         ),
     *         @OA\Schema(
     *           @OA\Property(property="status", type="string", example="confirmation_required"),
     *           @OA\Property(property="message", type="string", example="You are trying to apply a non-stackable coupon. This will remove all previously applied stackable coupons. Do you want to continue?"),
     *           @OA\Property(property="conflict_type", type="string", example="non_stackable_over_stackable"),
     *           @OA\Property(property="new_coupon", type="string", example="NEW_COUPON"),
     *           @OA\Property(property="existing_coupons", type="array", @OA\Items(type="string")),
     *           @OA\Property(property="coupons_to_remove", type="array", @OA\Items(type="string"))
     *         )
     *       }
     *     )
     *   ),
     *   @OA\Response(response=400, description="Invalid coupons"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'coupon_code' => 'nullable|string|max:100',
            'coupon_codes' => 'nullable|array',
            'coupon_codes.*' => 'string|max:100',
            'force_apply' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        $cart = $this->shop->getCart();
        if (empty($cart['items'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart is empty',
            ], 422);
        }

        $existingCodes = collect($data['coupon_codes'] ?? [])->filter()->map('trim');
        $newCode = !empty($data['coupon_code']) ? trim($data['coupon_code']) : null;
        $forceApply = (bool) ($data['force_apply'] ?? false);

        // Check if coupon already applied
        if (!empty($newCode) && $existingCodes->contains(strtoupper($newCode))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Coupon already applied',
            ], 422);
        }

        // Check max coupons before adding new code
        if (!empty($newCode)) {
            if ($existingCodes->count() >= config('app.max_coupons', 5)) {
                return response()->json([
                    'status' => 'error',
                    'message' => sprintf('You can apply up to %d coupons at once.', config('app.max_coupons', 5)),
                ], 422);
            }
        }

        $subtotal = $this->checkout->calculateCartSubtotal($cart);

        // Get current summary with existing coupons to calculate current discounts
        $currentAppliedVouchers = [];
        $currentTotalDiscount = 0.0;
        if (!$existingCodes->isEmpty()) {
            $currentSummary = $this->checkout->evaluateCheckoutSummary(
                $cart,
                $existingCodes->all(),
                $subtotal,
                0.0,
                null,
                $user->id
            );
            $currentAppliedVouchers = $currentSummary['applied_vouchers'] ?? [];
            $currentTotalDiscount = $currentSummary['summary']['discount_total'] ?? 0.0;
        }

        // Check for stackability conflicts BEFORE applying (unless force_apply is true)
        if (!$forceApply && !empty($newCode)) {
            $conflictCheck = $this->checkStackabilityConflict(
                $existingCodes->all(), 
                $newCode, 
                $user->id, 
                $subtotal,
                $currentAppliedVouchers,
                $currentTotalDiscount
            );
            
            if ($conflictCheck['has_conflict']) {
                return response()->json([
                    'status' => 'confirmation_required',
                    'message' => $conflictCheck['message'],
                    'conflict_type' => $conflictCheck['conflict_type'],
                    'new_coupon' => $newCode,
                    'existing_coupons' => $conflictCheck['existing_coupons'],
                    'coupons_to_remove' => $conflictCheck['coupons_to_remove'],
                    'old_coupons_discount' => $conflictCheck['old_coupons_discount'] ?? [],
                    'new_coupon_discount' => $conflictCheck['new_coupon_discount'] ?? 0.0,
                ], 200);
            }
        }

        // Build final codes array: existing codes + new code (if provided)
        if (!empty($newCode)) {
            $existingCodes->push($newCode);
        }

        // Process codes: filter empty, deduplicate, and prepare final array
        $codes = $existingCodes
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($codes)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No coupon codes supplied',
            ], 422);
        }

        $evaluation = $this->checkout->evaluateCheckoutSummary(
            $cart,
            $codes,
            $subtotal,
            0.0,
            null,
            $user->id
        );

        $errors = $evaluation['errors'] ?? [];
        if (!empty($errors)) {
            $message = collect($errors)->first() ?: 'Unable to apply the provided coupons';

            return response()->json([
                'status' => 'error',
                'message' => $message,
                'errors' => $errors,
                'applied_vouchers' => $evaluation['applied_vouchers'] ?? [],
                'summary' => $evaluation['summary'] ?? [],
                'coupon_codes' => $codes,
                'stackability_message' => $evaluation['stackability_message'] ?? null,
            ], 400);
        }

        $appliedCodes = collect($evaluation['applied_vouchers'] ?? [])
            ->map(fn ($voucher) => $voucher['voucher_code'] ?? $voucher['code'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $discountTotal = (float) ($evaluation['summary']['discount_total'] ?? 0.0);
        $message = !empty($newCode)
            ? sprintf('Coupon %s validated%s', $newCode, $discountTotal > 0 ? ' with S$' . number_format($discountTotal, 2) . ' discount' : '')
            : 'Coupons evaluated successfully';

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'coupon_codes' => $appliedCodes,
            'applied_vouchers' => $evaluation['applied_vouchers'] ?? [],
            'summary' => $evaluation['summary'] ?? [],
            'shipping_amount' => $evaluation['shipping_amount'] ?? 0.0,
            'tax' => $evaluation['tax'] ?? [],
            'stackability_message' => $evaluation['stackability_message'] ?? null,
        ]);
    }

    /**
     * Check for stackability conflicts between new and existing coupons.
     * Provides enhanced cost comparison data for better user decision-making.
     */
    private function checkStackabilityConflict(
        array $existingCodes, 
        string $newCode, 
        int $userId, 
        float $subtotal,
        array $currentAppliedVouchers = [],
        float $currentTotalDiscount = 0.0
    ): array
    {
        if (empty($existingCodes)) {
            return ['has_conflict' => false];
        }

        $voucherService = app(\App\Services\VoucherService::class);
        
        // Validate the new coupon
        try {
            $newVoucher = $voucherService->validateVoucher(['voucher_code' => strtoupper($newCode)], $userId);
            $newVoucherData = $newVoucher['data'];
        } catch (\Exception $e) {
            return ['has_conflict' => false]; // Invalid coupon, will fail in main validation
        }

        // Validate existing coupons
        $existingVouchers = [];
        foreach ($existingCodes as $code) {
            try {
                $result = $voucherService->validateVoucher(['voucher_code' => strtoupper($code)], $userId);
                $existingVouchers[] = $result['data'];
            } catch (\Exception $e) {
                // Skip invalid existing coupons
            }
        }

        if (empty($existingVouchers)) {
            return ['has_conflict' => false];
        }

        $newIsStackable = (bool) ($newVoucherData['stackable'] ?? false);
        $existingStackable = array_filter($existingVouchers, fn($v) => $v['stackable']);
        $existingNonStackable = array_filter($existingVouchers, fn($v) => !$v['stackable']);
        
        // Helper function to calculate discount for a voucher
        $calculateDiscount = function($voucher, $amount) {
            if ($voucher['discount_type'] === 'percentage') {
                return round(($amount * $voucher['discount_value']) / 100, 2);
            } else {
                return round(min($voucher['discount_value'], $amount), 2);
            }
        };
        
        // Calculate new coupon discount (on current subtotal after removing conflicting discounts)
        $newCouponDiscount = $calculateDiscount($newVoucherData, max(0, $subtotal));
        $currentTotal = $subtotal - $currentTotalDiscount;
        
        // Helper to get detailed discount info for coupons being removed
        $getOldCouponsDiscount = function($couponsToRemove) use ($currentAppliedVouchers) {
            $discountInfo = [];
            $totalOldDiscount = 0.0;
            foreach ($currentAppliedVouchers as $voucher) {
                if (in_array($voucher['voucher_code'], $couponsToRemove)) {
                    $discountInfo[] = [
                        'code' => $voucher['voucher_code'],
                        'type' => $voucher['discount_type'],
                        'value' => $voucher['discount_value'],
                        'discount' => $voucher['calculated_discount'] ?? 0
                    ];
                    $totalOldDiscount += (float)($voucher['calculated_discount'] ?? 0);
                }
            }
            return [
                'details' => $discountInfo,
                'total' => $totalOldDiscount
            ];
        };

        // Helper to format currency consistently
        $formatCurrency = fn($amount) => 'S$' . number_format($amount, 2);
        
        // Helper to build conflict response with cost comparison
        $buildConflictResponse = function($couponsToRemove, $message) use (
            $subtotal, 
            $getOldCouponsDiscount, 
            $newCouponDiscount, 
            $currentTotal,
            $formatCurrency,
            $existingVouchers,
            $newCode
        ) {
            $oldCouponsDiscount = $getOldCouponsDiscount($couponsToRemove);
            $newTotal = $subtotal - $newCouponDiscount;
            
            // Calculate net impact on total
            $netImpact = ($currentTotal) - $newTotal;
            $impactText = $netImpact > 0 
                ? "You'll save an additional " . $formatCurrency($netImpact)
                : "You'll pay " . $formatCurrency(abs($netImpact)) . " more";
                
            $detailedMessage = $message . "\n\n" .
                "Current total: " . $formatCurrency($currentTotal) . "\n" .
                "New total with " . strtoupper($newCode) . ": " . $formatCurrency($newTotal) . "\n" .
                $impactText;

            return [
                'has_conflict' => true,
                'existing_coupons' => array_map(fn($v) => $v['voucher_code'], $existingVouchers),
                'coupons_to_remove' => $couponsToRemove,
                'message' => $detailedMessage,
                'old_coupons_discount' => $oldCouponsDiscount['details'],
                'new_coupon_discount' => $newCouponDiscount,
                'current_total' => $currentTotal,
                'new_total' => $newTotal,
                'net_impact' => $netImpact,
                'subtotal' => $subtotal,
                'old_total_discount' => $oldCouponsDiscount['total']
            ];
        };

        // Scenario 1: Applying non-stackable when stackable coupons exist
        if (!$newIsStackable && !empty($existingStackable)) {
            $couponsToRemove = array_map(fn($v) => $v['voucher_code'], $existingStackable);
            return array_merge(
                $buildConflictResponse(
                    $couponsToRemove,
                    'You are trying to apply a non-stackable coupon. This will remove all previously applied stackable coupons.'
                ),
                ['conflict_type' => 'non_stackable_over_stackable']
            );
        }

        // Scenario 2: Applying stackable when non-stackable coupon exists
        if ($newIsStackable && !empty($existingNonStackable)) {
            $couponsToRemove = array_map(fn($v) => $v['voucher_code'], $existingNonStackable);
            return array_merge(
                $buildConflictResponse(
                    $couponsToRemove,
                    'A non-stackable coupon is already applied. Stackable coupons cannot be combined with it. Adding this coupon will remove all currently applied non-stackable coupons.'
                ),
                ['conflict_type' => 'stackable_over_non_stackable']
            );
        }

        // Scenario 3: Applying non-stackable when non-stackable coupon exists
        if (!$newIsStackable && !empty($existingNonStackable)) {
            $couponsToRemove = array_map(fn($v) => $v['voucher_code'], $existingNonStackable);
            return array_merge(
                $buildConflictResponse(
                    $couponsToRemove,
                    'A non-stackable coupon is already applied. Applying this new coupon will replace the existing non-stackable coupon.'
                ),
                ['conflict_type' => 'non_stackable_over_non_stackable']
            );
        }

        return ['has_conflict' => false];
    }

    /**
     * @OA\Post(
     *   path="/api/ecommerce/checkout/place-order",
     *   summary="Place order",
     *   tags={"E-commerce"},
     *   security={
     *     {"bearerAuth": {}}
     *   },
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="shipping_address_id", type="integer", description="ID of user's saved shipping address (required only if cart contains shippable items)"),
     *       @OA\Property(property="billing_address_id", type="integer", description="ID of user's saved billing address", required={"true"}),
     *       @OA\Property(property="payment_method_id", type="string", description="Optional Stripe payment method ID"),
     *       @OA\Property(property="coupon_codes", type="array", @OA\Items(type="string"), description="Optional array of coupon codes")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Order placed successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="order_id", type="integer", example=123),
     *       @OA\Property(property="order_number", type="string", example="ORD-2024-001"),
     *       @OA\Property(property="total", type="number", example=99.99),
     *       @OA\Property(property="shipping", type="number", example=5.99)
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Order placement failed",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Order placement failed.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Unauthenticated.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Cart is empty",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Cart is empty")
     *     )
     *   )
     * )
     */
    public function placeOrder(Request $request)
    {
        $cart = $this->shop->getCart();
        if (empty($cart['items'])) {
            return response()->json(['status' => 'error', 'message' => 'Cart is empty'], 422);
        }

        // Check if shipping is required using the same service method as web checkout
        $shippingRequired = $this->checkout->cartRequiresShipping($cart);

        // Build validation rules
        $validationRules = [
            'billing_address_id' => 'required|integer|exists:addresses,id',
            'payment_method_id' => 'nullable|string',
            'coupon_codes' => 'nullable|array',
            'coupon_codes.*' => 'string|max:100',
        ];
        
        // Only require shipping address if shipping is needed (consistent with web checkout)
        if ($shippingRequired) {
            $validationRules['shipping_address_id'] = 'required|integer|exists:addresses,id';
        }

        $data = $request->validate($validationRules);

        $user = Auth::user();

        // Prepare address data for coupon codes
        $address = [];
        if (isset($data['coupon_codes']) && is_array($data['coupon_codes']) && !empty($data['coupon_codes'])) {
            $address['coupon_codes'] = array_map('trim', array_filter($data['coupon_codes']));
        }

        try {
            \Log::info('CheckoutController.placeOrder: invoking unified checkout', [
                'user_id' => $user?->id,
                'shipping_address_id' => $data['shipping_address_id'] ?? null,
                'billing_address_id' => $data['billing_address_id'],
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'coupon_codes' => $data['coupon_codes'] ?? null,
                'cart_items_count' => count($cart['items'] ?? []),
            ]);

            $result = $this->checkout->checkoutUnified(
                $cart,
                $user,
                $data['shipping_address_id'] ?? null,
                $data['billing_address_id'],
                $data['payment_method_id'] ?? null,
                session()->get('guest.session_token'),
                $address
            );
        } catch (\Throwable $e) {
            \Log::error('CheckoutController.placeOrder: checkout failed', [
                'error' => $e->getMessage(),
                'trace_top' => collect(explode("\n", $e->getTraceAsString()))->take(3)->implode(" | ")
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }

        $order = $result['order'];
        return response()->json([
            'status' => 'success',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total' => $order->total_amount,
            'shipping' => $order->shipping_amount,
        ]);
    }
}