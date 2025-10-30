<?php

namespace App\Livewire\Frontend\Ecommerce;

use Livewire\Component;
use App\Services\ECommerceService;
use App\Services\VoucherService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartPage extends Component
{
    public array $cart = [];
    public string $couponCode = '';
    public array $appliedCoupons = [];
    public float $totalDiscount = 0.0;
    public string $couponMessage = '';
    public ?string $stackabilityMessage = null;

    public function mount(ECommerceService $svc)
    {
        $this->cart = $svc->getCart();
        // Load any existing applied coupons if needed
    }

    public function applyCoupon(VoucherService $voucherService, ECommerceService $svc)
    {
        $code = trim($this->couponCode);
        if (empty($code)) {
            $this->couponMessage = 'Please enter a coupon code';
            return;
        }

        $code = strtoupper($code);

        if (!Auth::check()) {
            $this->couponMessage = 'Please log in to apply coupons';
            return;
        }
        
        $userId = Auth::id();
        
        // Check if cart is empty
        $cart = $svc->getCart();
        if (empty($cart['items'])) {
            $this->couponMessage = 'Your cart is empty. Add items before applying a coupon.';
            return;
        }
        
        try {
            $result = $voucherService->validateVoucher($code, $userId);
        } catch (\Throwable $e) {
            \Log::error('Coupon validation failed', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);
            $this->couponMessage = 'Invalid coupon code';
            return;
        }

        if (!$result['valid']) {
            $this->couponMessage = $result['message'] ?? 'Invalid coupon code';
            return;
        }

        // Check if coupon is already applied
        $isAlreadyApplied = collect($this->appliedCoupons)->contains('voucher_code', $code);
        if ($isAlreadyApplied) {
            $this->couponMessage = 'Coupon already applied';
            return;
        }

        // Store only voucher metadata, not calculated values
        $this->appliedCoupons[] = [
            'voucher_code' => $code,
            'discount_type' => $result['discount_type'] ?? 'fixed',
            'discount_value' => $result['discount_value'] ?? 0,
        ];

        // Recalculate discounts against current cart
        $this->recalculateDiscounts($voucherService);
        
        $appliedCouponsCount = count($this->appliedCoupons);

        // Set success message
        if ($this->totalDiscount > 0) {
            if ($appliedCouponsCount > 1) {
                $this->couponMessage = "Saved S$" . number_format($this->totalDiscount, 2) . " with applied coupons";
            } else {
                $this->couponMessage = "Saved S$" . number_format($result['discount'], 2) . " with {$code}";
            }
        } else {
            $this->couponMessage = "Coupon {$code} applied";
        }

        $this->couponCode = ''; // Clear the input

        // Recalculate totals and persist; do not mutate cart.total directly here
        $this->recalcTotals();
    }

    public function removeCoupon(string $code)
    {
        $this->appliedCoupons = collect($this->appliedCoupons)->filter(
            fn($coupon) => $coupon['voucher_code'] !== $code
        )->values()->all();
        // Recompute stored total discount and update message
        $this->totalDiscount = $this->calculateTotalDiscount();
        $appliedCouponsCount = count($this->appliedCoupons);

        if ($this->totalDiscount > 0) {
            $this->couponMessage = "Saved S$" . number_format($this->totalDiscount, 2) . " with applied coupons";
        } else {
            $this->couponMessage = 'Coupon removed';
        }

        // Recalculate totals after removal
        $this->recalcTotals();
    }

    private function recalcTotals(): void
    {
        $items = $this->cart['items'] ?? [];
        $total = 0.0;
        foreach ($items as &$it) {
            $line = (float)($it['unit_price'] ?? 0) * (int)($it['qty'] ?? 1);
            // include addons subtotal when present
            if (!empty($it['addons']) && is_array($it['addons'])) {
                foreach ($it['addons'] as $ad) {
                    $line += (float)($ad['subtotal'] ?? 0);
                }
            }
            $it['subtotal'] = $line;
            $total += $line;
        }
        $this->cart['items'] = $items;

        // Recalculate discounts if we have any vouchers
        if (!empty($this->appliedCoupons)) {
            $this->recalculateDiscounts(app(VoucherService::class));
        }

        $computed = max(0.0, round($total - $this->totalDiscount, 2));
        $this->cart['total'] = $computed;
        // Persist the full cart so other components and requests see updated items and totals.
        // Also keep the legacy cart.total key for backward compatibility.
        Session::put('cart', $this->cart);
        Session::put('cart.total', $computed);
    }

    public function remove(string $id, ECommerceService $svc)
    {
        $this->cart = $svc->removeCartItem($id);
    }

    public function saveAndProceed(ECommerceService $svc, VoucherService $voucherService)
    {
        // Revalidate discounts before proceeding
        $this->recalculateDiscounts($voucherService);

        // Persist all current quantities, then go to checkout
        foreach (($this->cart['items'] ?? []) as $it) {
            $svc->updateCartItem($it['id'], (int)$it['qty']);
        }

        // Refresh the cart data after updates
        $this->cart = $svc->getCart();

        // Store validated voucher codes for checkout
        $voucherCodes = collect($this->appliedCoupons)
            ->pluck('voucher_code')
            ->filter()
            ->values()
            ->all();
            
        // Store voucher codes in session for checkout
        Session::put('checkout.coupon_codes', $voucherCodes);

        return redirect()->route('shop.checkout');
    }

    /**
     * Recalculate all applied voucher discounts against current cart
     */
    private function recalculateDiscounts(VoucherService $voucherService): void
    {
        $userId = Auth::id();
        $totalDiscount = 0.0;

        // Revalidate each voucher against current cart state
        foreach ($this->appliedCoupons as &$coupon) {
            try {
                $result = $voucherService->validateVoucher($coupon['voucher_code'], $userId);
                if ($result['valid']) {
                    $totalDiscount += (float)($result['discount'] ?? 0);
                    // Update metadata but don't store calculated values
                    $coupon['discount_type'] = $result['discount_type'] ?? 'fixed';
                    $coupon['discount_value'] = $result['discount_value'] ?? 0;
                }
            } catch (\Throwable $e) {
                // Voucher became invalid - skip it
                continue;
            }
        }

        $this->totalDiscount = $totalDiscount;
    }

    /**
     * Calculate the total discount from all applied coupons
     */
    private function calculateTotalDiscount(): float
    {
        // This should only be called after recalculateDiscounts()
        return $this->totalDiscount;
    }

    /**
     * Check if there are any quantity errors in the cart
     */
    public function hasQuantityErrors(): bool
    {
        foreach (($this->cart['items'] ?? []) as $item) {
            $maxQtyPerOrder = (int)($item['max_quantity_per_order'] ?? 0);
            $currentQty = (int)($item['qty'] ?? 0);
            
            if ($maxQtyPerOrder > 0 && $currentQty > $maxQtyPerOrder) {
                return true;
            }
        }
        
        return false;
    }

    public function render()
    {
        return view('livewire.frontend.ecommerce.cart-page');
    }
}