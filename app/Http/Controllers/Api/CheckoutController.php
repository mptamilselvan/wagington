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
            'applied_vouchers' => $evaluation['applied_vouchers'],
            'summary' => $evaluation['summary'],
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
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Coupons evaluated",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Coupon PROMO-10 applied"),
     *       @OA\Property(property="coupon_codes", type="array", @OA\Items(type="string")),
     *       @OA\Property(property="summary", type="object", description="Updated checkout summary totals"),
     *       @OA\Property(property="applied_vouchers", type="array", @OA\Items(type="object")),
     *       @OA\Property(property="stackability_message", type="string", nullable=true)
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

        if (!empty($newCode)) {
            if ($existingCodes->count() >= config('app.max_coupons', 5)) {
                return response()->json([
                    'status' => 'error',
                    'message' => sprintf('You can apply up to %d coupons at once.', config('app.max_coupons', 5)),
                ], 422);
            }

            $existingCodes->push($newCode);
        }

        $codes = $existingCodes
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (count($codes) > config('app.max_coupons', 5)) {
            return response()->json([
                'status' => 'error',
                'message' => sprintf('You can apply up to %d coupons at once.', config('app.max_coupons', 5)),
            ], 422);
        }

        if (empty($codes)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No coupon codes supplied',
            ], 422);
        }

        $subtotal = $this->checkout->calculateCartSubtotal($cart);

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
     *       @OA\Property(property="shipping_address_id", type="integer", description="ID of user's saved shipping address", required={"true"}),
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
        $data = $request->validate([
            'shipping_address_id' => 'required|integer|exists:addresses,id',
            'billing_address_id' => 'required|integer|exists:addresses,id',
            'payment_method_id' => 'nullable|string',
            'coupon_codes' => 'nullable|array',
            'coupon_codes.*' => 'string|max:100',
        ]);

        $cart = $this->shop->getCart();
        if (empty($cart['items'])) {
            return response()->json(['status' => 'error', 'message' => 'Cart is empty'], 422);
        }

        $user = Auth::user();

        // Prepare address data for coupon codes
        $address = [];
        if (isset($data['coupon_codes']) && is_array($data['coupon_codes']) && !empty($data['coupon_codes'])) {
            $address['coupon_codes'] = array_map('trim', array_filter($data['coupon_codes']));
        }

        try {
            \Log::info('CheckoutController.placeOrder: invoking unified checkout', [
                'user_id' => $user?->id,
                'shipping_address_id' => $data['shipping_address_id'],
                'billing_address_id' => $data['billing_address_id'],
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'coupon_codes' => $data['coupon_codes'] ?? null,
                'cart_items_count' => count($cart['items'] ?? []),
            ]);

            $result = $this->checkout->checkoutUnified(
                $cart,
                $user,
                $data['shipping_address_id'],
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