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
                'user_id' => $userId,
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

        // Add the coupon to applied coupons
        $this->appliedCoupons[] = [
            'voucher_code' => $code,
            'calculated_discount' => $result['discount'],
            'discount_type' => $result['discount_type'] ?? 'fixed',
            'discount_value' => $result['discount_value'] ?? $result['discount'],
        ];

        // Recompute stored total discount
        $this->totalDiscount = $this->calculateTotalDiscount();

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

        // Ensure totalDiscount is computed
        $totalDiscount = $this->calculateTotalDiscount();

        $this->totalDiscount = $totalDiscount;

        $computed = max(0.0, round($total - $totalDiscount, 2));
        $this->cart['total'] = $computed;
        // Persist the full cart so other components and requests see updated items and totals.
        // Also keep the legacy cart.total key for backward compatibility.
        Session::put('cart', $this->cart);
        Session::put('cart.total', $computed);
    }

    public function increment(string $id)
    {
        if (!is_array($this->cart) || !array_key_exists('items', $this->cart)) {
            return;
        }

        $items = $this->cart['items'];
        if (!is_iterable($items) || collect($items)->isEmpty()) {
            return;
        }

        $idx = collect($this->cart['items'])->search(fn($it) => $it['id'] === $id);
        if ($idx === false) return;
        $this->cart['items'][$idx]['qty'] = (int)$this->cart['items'][$idx]['qty'] + 1;
        $this->recalcTotals();
    }

    public function decrement(string $id)
    {
        // Guard: ensure items exist and are iterable
        if (!is_array($this->cart) || !isset($this->cart['items']) || !is_array($this->cart['items']) || empty($this->cart['items'])) {
            return;
        }

        $idx = collect($this->cart['items'])->search(fn($it) => $it['id'] === $id);
        if ($idx === false) return;
        $this->cart['items'][$idx]['qty'] = max(1, (int)$this->cart['items'][$idx]['qty'] - 1);
        $this->recalcTotals();
    }

    public function remove(string $id, ECommerceService $svc)
    {
        $this->cart = $svc->removeCartItem($id);
    }

    public function saveAndProceed(ECommerceService $svc)
    {
        // Persist all current quantities, then go to checkout
        foreach (($this->cart['items'] ?? []) as $it) {
            $svc->updateCartItem($it['id'], (int)$it['qty']);
        }
        $this->cart = $svc->getCart();
        return redirect()->route('shop.checkout');
    }

    /**
     * Calculate the total discount from all applied coupons
     */
    private function calculateTotalDiscount(): float
    {
        return array_sum(array_map(function($c) {
            return (float)($c['calculated_discount'] ?? $c['applied_amount'] ?? 0);
        }, $this->appliedCoupons));
    }

    public function render()
    {
        return view('livewire.frontend.ecommerce.cart-page');
    }
}