<?php

namespace App\Livewire\Frontend\Ecommerce;

use Livewire\Component;
use App\Services\ECommerceService;
use App\Services\CustomerService;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Auth;
use App\Services\PaymentService;

class CheckoutPage extends Component
{
    // Cart and user
    public array $cart = [];
    public string $email = '';
    public string $name = '';

    // Step state
    public int $activeStep = 1; // 1=Address, 2=Payment, 3=Review
    public bool $placed = false;
    public bool $loading = false;
    public ?string $orderNumber = null;

    // Address selection
    public array $addresses = [];
    public ?int $selectedShippingAddressId = null;
    public ?int $selectedBillingAddressId = null;
    public bool $billingAddressSameAsShipping = false; // Changed default to false for explicit selection
    public bool $requiresShipping = true; // Will be determined based on cart items
    
    // Section states for collapsible design
    public bool $addressSectionOpen = true;
    public bool $paymentSectionOpen = false;

    // Payment selection
    public array $paymentMethods = [];
    public ?string $selectedPaymentMethodId = null;

    // Multi-coupon functionality
    public string $currentCouponInput = '';
    public array $appliedCoupons = [];
    public array $couponCodes = [];
    public float $totalCouponDiscount = 0.0;
    public string $couponMessage = '';
    public int $maxCoupons = 5;

    // Derived, read-only at service level; Livewire passes code explicitly to service

    // Tax and shipping calculations
    public float $shippingAmount = 0.0;
    public float $taxAmount = 0.0;
    public float $taxRate = 0.0;

    protected CheckoutService $checkoutService;

    public array $checkoutSummary = [
        'summary' => [
            'subtotal' => 0.0,
            'discount_total' => 0.0,
            'total' => 0.0,
        ],
        'shipping_amount' => 0.0,
        'tax' => [
            'amount' => 0.0,
            'rate' => 0.0,
        ],
        'applied_vouchers' => [],
        'errors' => [],
        'stackability_message' => null,
    ];

    public function boot(CheckoutService $checkoutService): void
    {
        $this->checkoutService = $checkoutService;
    }

    public function mount(ECommerceService $svc, CustomerService $customers, PaymentService $payments)
    {
        // Set maxCoupons from config or default to 5
        $this->maxCoupons = config('app.max_coupons', 5);
        
        $this->cart = $svc->getCart();

        if (Auth::check()) {
            $user = Auth::user();
            $this->email = $user->email ?? '';
            $this->name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            // Load addresses and pick default selections
            $addrResp = $customers->getCustomerAddresses($user->id);
            $this->addresses = $addrResp['addresses']->toArray() ?? [];
            $addrCollection = collect($addrResp['addresses'] ?? []);
            $shipping = $addrCollection->firstWhere('is_shipping_address', true);
            $billing = $addrCollection->firstWhere('is_billing_address', true);
            $first = $addrCollection->first();
            
            // Set default shipping address (prefer dedicated shipping, else billing, else first)
            $this->selectedShippingAddressId = $shipping->id ?? ($billing->id ?? ($first?->id ?? null));
            
            // Set default billing address (prefer dedicated billing, else shipping, else first) 
            $this->selectedBillingAddressId = $billing->id ?? ($shipping->id ?? ($first?->id ?? null));
            
            // Keep billingAddressSameAsShipping as false for explicit selection in new unified interface
            $this->billingAddressSameAsShipping = false;

            // Load payment methods and pick default
            $pmResp = $payments->getPaymentMethods($user);
            $methods = $pmResp['payment_methods'] ?? ($pmResp['data']['payment_methods'] ?? []);

            // Mark default based on Stripe customer invoice_settings
            $defaultResp = $payments->getDefaultPaymentMethod($user);
            $defaultId = $defaultResp['default_payment_method']['id'] ?? null;
            $this->paymentMethods = collect($methods)
                ->map(function ($m) use ($defaultId) {
                    $m['is_default'] = ($m['id'] ?? null) === $defaultId;
                    return $m;
                })
                ->values()
                ->all();

            $default = collect($this->paymentMethods)->firstWhere('is_default', true);
            $this->selectedPaymentMethodId = $default['id'] ?? ($this->paymentMethods[0]['id'] ?? null);
        }

        // Check if any items in cart require shipping
        $this->checkShippingRequirement();

        $this->hydrateCheckoutSummary();
    }

    public function hydrateCheckoutSummary(): void
    {
        $summary = $this->buildCheckoutSummary();

        if (empty($summary)) {
            $summary = $this->emptySummary();
        }

        $this->applySummary($summary);
    }

    private function buildCheckoutSummary(): array
    {
        if (empty($this->cart['items'])) {
            return $this->emptySummary();
        }

        $codes = $this->couponCodes;
        return $this->checkoutService->buildUnifiedSummary(
            $this->cart,
            $codes,
            $this->selectedShippingAddressId,
            $this->selectedBillingAddressId,
            $this->selectedPaymentMethodId,
            Auth::id() ?? 0
        );
    }

    private function emptySummary(): array
    {
        return [
            'summary' => [
                'subtotal' => 0.0,
                'discount_total' => 0.0,
                'total' => 0.0,
            ],
            'shipping_amount' => 0.0,
            'tax' => [
                'amount' => 0.0,
                'rate' => 0.0,
            ],
            'applied_vouchers' => [],
            'errors' => [],
            'stackability_message' => null,
        ];
    }

    /**
     * Check if any cart items require shipping based on product's shippable field
     */
    private function checkShippingRequirement(): void
    {
        $this->requiresShipping = false;
        
        if (!empty($this->cart['items'])) {
            $variantIds = collect($this->cart['items'])->pluck('variant_id')->filter();
            
            if ($variantIds->isNotEmpty()) {
                // Load product variants with their products to check shippable field
                $variants = \App\Models\ProductVariant::with('product')
                    ->whereIn('id', $variantIds)
                    ->get();
                
                // Check if any product is shippable
                foreach ($variants as $variant) {
                    if ($variant->product && $variant->product->shippable) {
                        $this->requiresShipping = true;
                        break;
                    }
                }
            }
        }
        
        // If no shipping is required, clear shipping address selection
        if (!$this->requiresShipping) {
            $this->selectedShippingAddressId = null;
        }
    }

    private function buildRegionContext(): ?array
    {
        if (!$this->requiresShipping || !$this->selectedShippingAddressId) {
            return null;
        }

        $address = collect($this->addresses)->firstWhere('id', $this->selectedShippingAddressId);

        if (!$address) {
            return null;
        }

        return [
            'country' => $address['country'] ?? null,
            'state' => $address['state'] ?? null,
            'region' => $address['region'] ?? null,
            'postal_code' => $address['postal_code'] ?? null,
        ];
    }

    public function updatedSelectedShippingAddressId()
    {
        $this->hydrateCheckoutSummary();
    }

    public function updatedSelectedBillingAddressId()
    {
        // Re-render to update the selection summary
        // This ensures the address selection summary updates reactively
    }

    public function updatedBillingAddressSameAsShipping()
    {
        // This method is kept for compatibility but will be unused in the new unified interface
        if ($this->billingAddressSameAsShipping) {
            $this->selectedBillingAddressId = $this->selectedShippingAddressId;
        }
    }

    public function toggleAddressSection()
    {
        $this->addressSectionOpen = !$this->addressSectionOpen;
        if ($this->addressSectionOpen) {
            $this->paymentSectionOpen = false;
        }
    }

    public function togglePaymentSection()
    {
        // Updated validation: billing address is always required, shipping address only when shipping is required
        $addressesValid = $this->selectedBillingAddressId && (!$this->requiresShipping || $this->selectedShippingAddressId);
        
        if (!$addressesValid) {
            return; // Cannot open payment section without required addresses selected
        }
        
        $this->paymentSectionOpen = !$this->paymentSectionOpen;
        if ($this->paymentSectionOpen) {
            $this->addressSectionOpen = false;
        }
    }

    public function addCoupon()
    {
        // Pre-validation checks
        $code = trim($this->currentCouponInput);
        if (empty($code)) {
            $this->couponMessage = 'Please enter a voucher code';
            return;
        }

        $code = strtoupper($code);

        // Check if already applied
        if (in_array($code, $this->couponCodes, true)) {
            $this->couponMessage = 'Already applied';
            return;
        }

        // Check maximum limit
        if (count($this->couponCodes) >= $this->maxCoupons) {
            $this->couponMessage = "Max {$this->maxCoupons} coupons allowed";
            return;
        }

        $proposedCodes = array_values(array_unique(array_merge($this->couponCodes, [$code])));
        $summary = $this->evaluateSummary($proposedCodes);

        if (!empty($summary['errors'])) {
            $this->couponMessage = collect($summary['errors'])->first();
            return;
        }

        $this->applySummary($summary, $this->formatCouponAppliedMessage($summary, $code, count($proposedCodes)));
        $this->currentCouponInput = '';
    }

    public function removeCoupon($code)
    {
        // Remove the specified coupon
        $remainingCodes = array_values(array_diff($this->couponCodes, [$code]));
        if (empty($remainingCodes)) {
            $this->resetCoupons();
            return;
        }

        $summary = $this->evaluateSummary($remainingCodes);
        $message = empty($summary['errors'])
            ? 'Coupon removed'
            : collect($summary['errors'])->first();

        $this->applySummary($summary, $message);
    }

    private function resetCoupons(): void
    {
        $this->couponCodes = [];
        $this->appliedCoupons = [];
        $this->totalCouponDiscount = 0.0;
        $this->couponMessage = 'All coupons removed';
        $this->hydrateCheckoutSummary();
    }

    private function evaluateSummary(array $codes): array
    {
        if (empty($this->cart['items'])) {
            return $this->emptySummary();
        }

        return $this->checkoutService->buildUnifiedSummary(
            $this->cart,
            $codes,
            $this->selectedShippingAddressId,
            $this->selectedBillingAddressId,
            $this->selectedPaymentMethodId,
            Auth::id() ?? 0
        );
    }

    private function applySummary(array $summary, ?string $message = null): void
    {
        $this->couponCodes = array_column($summary['applied_vouchers'] ?? [], 'voucher_code');
        $this->appliedCoupons = $this->normaliseAppliedCoupons($summary['applied_vouchers'] ?? []);
        $this->checkoutSummary = $summary;
        $this->shippingAmount = (float)($summary['shipping_amount'] ?? 0.0);
        $this->taxAmount = (float)($summary['tax']['amount'] ?? 0.0);
        $this->taxRate = (float)($summary['tax']['rate'] ?? 0.0);
        $this->cart['items'] = $summary['cart']['items'] ?? ($this->cart['items'] ?? []);
        $this->totalCouponDiscount = (float)($summary['summary']['discount_total'] ?? 0.0);

        if (!empty($summary['errors'])) {
            $this->couponMessage = collect($summary['errors'])->first();
        } elseif (!empty($summary['stackability_message'])) {
            $this->couponMessage = $summary['stackability_message'];
        } elseif ($message !== null) {
            $this->couponMessage = $message;
        } elseif (!empty($this->couponCodes)) {
            $this->couponMessage = 'Applied: ' . implode(', ', $this->couponCodes);
        } else {
            $this->couponMessage = '';
        }

    }

    public function recalculateDiscounts(array $codes = []): void
    {
        $codesToEvaluate = !empty($codes) ? array_values(array_unique($codes)) : $this->couponCodes;
        $summary = $this->evaluateSummary($codesToEvaluate);

        $message = empty($summary['errors'])
            ? null
            : collect($summary['errors'])->first();

        $this->applySummary($summary, $message);
    }

    private function formatCouponAppliedMessage(array $summary, string $code, int $proposedCouponsCount = null): string
    {
        $discountTotal = $summary['summary']['discount_total'] ?? 0.0;
        $appliedCouponsCount = $proposedCouponsCount ?? count($this->couponCodes ?? []);
        
        if ($discountTotal > 0) {
            if ($appliedCouponsCount > 1) {
                $base = "Saved S$" . number_format($discountTotal, 2) . " with applied coupons";
            } else {
                $base = "Saved S$" . number_format($discountTotal, 2) . " with {$code}";
            }
        } else {
            $base = "Coupon {$code} applied";
        }

        if (!empty($summary['stackability_message'])) {
            $base .= '. ' . $summary['stackability_message'];
        }

        return trim($base);
    }

    private function calculateCartSubtotal(array $cart): float
    {
        $subtotal = 0.0;
        foreach ($cart['items'] ?? [] as $item) {
            $subtotal += (float)($item['subtotal'] ?? 0);
            if (!empty($item['addons']) && is_array($item['addons'])) {
                foreach ($item['addons'] as $addon) {
                    $subtotal += (float)($addon['subtotal'] ?? 0);
                }
            }
        }

        return $subtotal;
    }

    private function normaliseAppliedCoupons(array $vouchers): array
    {
        return array_map(function ($voucher) {
            return [
                'code' => $voucher['voucher_code'],
                'type' => $voucher['discount_type'],
                'value' => $voucher['discount_value'],
                'discount' => $voucher['calculated_discount'],
                'stack_order' => $voucher['stack_order'] ?? null,
            ];
        }, $vouchers);
    }

    public function goToStep(int $step)
    {
        // Enforce gating: step 2 requires billing address and shipping address (if shipping required); step 3 requires payment
        $hasValidAddresses = $this->selectedBillingAddressId && (!$this->requiresShipping || $this->selectedShippingAddressId);
        
        if ($step === 2 && !$hasValidAddresses) return;
        if ($step === 3 && (!$hasValidAddresses || !$this->selectedPaymentMethodId)) return;
        $this->activeStep = $step;
    }

    public function placeOrder()
    {
        $this->loading = true;

        if (Auth::check()) {
            $user = Auth::user();
            $this->email = $this->email ?: ($user->email ?? '');
            $this->name = $this->name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        }

        $validationRules = [
            'selectedBillingAddressId' => 'required|integer',
            'selectedPaymentMethodId' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
        ];
        
        // Only require shipping address if shipping is needed
        if ($this->requiresShipping) {
            $validationRules['selectedShippingAddressId'] = 'required|integer';
        }
        
        $this->validate($validationRules);

        try {
            if (empty($this->cart['items'])) {
                $this->addError('checkout', 'Cart is empty');
                return;
            }

            $address = null;
            if (Auth::check()) {
                $addr = collect($this->addresses)->firstWhere('id', $this->selectedShippingAddressId);
                if ($addr) {
                    $address = [
                        'country' => $addr['country'] ?? null,
                        'postal_code' => $addr['postal_code'] ?? null,
                    ];
                }
            }

            // Call unified checkout service (no HTTP). This ensures parity with API.
            \Log::info('CheckoutPage: Calling checkoutUnified', [
                'user_id' => Auth::id(),
                'cart_items_count' => count($this->cart['items'] ?? []),
                'selected_shipping_address_id' => $this->selectedShippingAddressId,
                'selected_billing_address_id' => $this->selectedBillingAddressId,
                'selected_payment_method_id' => $this->selectedPaymentMethodId,
            ]);

            // Re-validate applied coupons before placing order
            if (!empty($this->appliedCoupons)) {
                $codesToValidate = array_column($this->appliedCoupons, 'code');
                $validationResult = $this->checkoutService->validateMultipleVouchers($codesToValidate, Auth::id());

                if (!empty($validationResult['errors'])) {
                    // Remove invalid coupons
                    $validCodes = array_diff($codesToValidate, array_keys($validationResult['errors']));
                    $this->appliedCoupons = array_filter($this->appliedCoupons, function ($coupon) use ($validCodes) {
                        return in_array($coupon['code'], $validCodes);
                    });

                    if (empty($this->appliedCoupons)) {
                        $this->totalCouponDiscount = 0;
                        $this->addError('checkout', 'Applied coupons are no longer valid');
                        return;
                    }

                    // Recalculate with valid coupons
                    $this->recalculateDiscounts(array_column($this->appliedCoupons, 'code'));
                    $this->addError('checkout', 'Some coupons were invalid and removed');
                    return;
                }
            }

            // Pass coupon codes array to service via address payload
            if (!is_array($address)) { $address = []; }
            if (!empty($this->appliedCoupons)) {
                $address['coupon_codes'] = array_column($this->appliedCoupons, 'code');
            }

            $result = app(\App\Services\CheckoutService::class)->checkoutUnified(
                $this->cart,
                Auth::user(),
                $this->requiresShipping ? $this->selectedShippingAddressId : null,
                $this->selectedBillingAddressId,
                $this->selectedPaymentMethodId, // Use selected payment method
                session()->get('guest.session_token'),
                $address
            );

            $order = $result['order'];

            \Log::info('CheckoutPage: Checkout completed successfully', [
                'order_number' => $order->order_number,
                'order_id' => $order->id,
                'order_status' => $order->status,
                'payment_status' => $order->payment_status,
            ]);

            // Clear session cart already handled by service; just mark UI state
            $this->placed = true;
            $this->orderNumber = $order->order_number;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('checkout', 'Checkout failed: ' . ($e->getMessage()));
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.frontend.ecommerce.checkout-page')->layout('layouts.frontend.index');
    }
}