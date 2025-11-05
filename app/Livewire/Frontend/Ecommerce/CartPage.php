<?php

namespace App\Livewire\Frontend\Ecommerce;

use Livewire\Component;
use App\Services\ECommerceService;
use App\Services\VoucherService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\CartRoomDetail;

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
                $this->couponMessage = "Saved S$" . number_format($this->totalDiscount, 2) . " with {$code}";
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
        if (empty($this->cart['items'])) {
            \Log::info('CartPage::recalcTotals - cart is empty');
            // Replace entire cart array to ensure Livewire detects the change
            $this->cart = array_merge($this->cart, [
                'items' => [],
                'total' => 0,
            ]);
            Session::put('cart', $this->cart);
            Session::put('cart.total', 0);
            return;
        }

        \Log::info('CartPage::recalcTotals - cart is not empty');
        $total = 0.0;
        $updatedItems = []; // Create new array so Livewire detects changes
        
        // Calculate totals for each item
        foreach ($this->cart['items'] as $item) {
            $catalogId = (int)($item['catalog_id'] ?? 1);
            \Log::info('CartPage::recalcTotals - item', [
                'item' => $item,
                'catalog_id' => $catalogId,
            ]);
            // Default calculations
            $qty = (int)($item['qty'] ?? 1);
            $unitPrice = (float)($item['unit_price'] ?? 0);
            $itemSubtotal = $qty * $unitPrice;
            $lineTotal = $itemSubtotal;
            $updatedItem = $item;

            if ($catalogId === 3) {
                // Room booking: take total from cart_room_details.total_price by cart_item_id
                $cartItemId = $item['cart_item_id'] ?? null; // use real DB cart_item_id
                $detail = $cartItemId ? CartRoomDetail::where('cart_item_id', $cartItemId)->first() : null;
                $roomTotal = (float)($detail->total_price ?? ($item['subtotal'] ?? 0));
                \Log::info('CartPage::recalcTotals - room booking total', [
                    'cart_item_id' => $cartItemId,
                    'detail' => $detail,
                    'room_total' => $roomTotal,
                ]);
                $updatedItem = array_merge($item, [
                    'subtotal' => $roomTotal,
                ]);
                $lineTotal = $roomTotal;
                // Do not sum addons for room booking here; total_price should already include addons
                $updatedItem['addons'] = $item['addons'] ?? [];
            } else {
                // Ecommerce product: compute subtotal and addons normally
                $updatedItem = array_merge($item, [
                    'subtotal' => $itemSubtotal,
                ]);
                if (!empty($item['addons']) && is_array($item['addons'])) {
                    $updatedAddons = [];
                    foreach ($item['addons'] as $addon) {
                        $addonQty = (int)($addon['qty'] ?? 1);
                        $addonPrice = (float)($addon['unit_price'] ?? 0);
                        $addonSubtotal = $addonQty * $addonPrice;
                        $updatedAddon = array_merge($addon, [
                            'subtotal' => $addonSubtotal,
                        ]);
                        $updatedAddons[] = $updatedAddon;
                        $lineTotal += $addonSubtotal;
                    }
                    $updatedItem['addons'] = $updatedAddons;
                } else {
                    $updatedItem['addons'] = $item['addons'] ?? [];
                }
            }

            $updatedItems[] = $updatedItem;
            $total += $lineTotal;
        }

        // Recalculate discounts if we have any vouchers
        if (!empty($this->appliedCoupons)) {
            try {
                $this->recalculateDiscounts(app(VoucherService::class));
            } catch (\Throwable $e) {
                \Log::error('Failed to recalculate discounts', ['error' => $e->getMessage()]);
                // Clear invalid coupons to prevent repeated failures
                $this->appliedCoupons = [];
                $this->totalDiscount = 0.0;
            }
        }

        // Calculate final total
        $computed = max(0.0, round($total - $this->totalDiscount, 2));
        
        // Replace entire cart array to ensure Livewire detects the total change
        $this->cart = array_merge($this->cart, [
            'items' => $updatedItems,
            'total' => $computed,
        ]);
        
        // Persist the cart data
        Session::put('cart', $this->cart);
        Session::put('cart.total', $computed);
    }

    public function remove(string $id, int $catalogId, ECommerceService $svc)
    {
        $this->cart = $svc->removeCartItem($id, $catalogId);
    }
    
    public function removeAddon(int $itemIndex, int $addonIndex, ECommerceService $svc)
    {
        $items = array_values($this->cart['items'] ?? []);
        if (!isset($items[$itemIndex])) return;
        
        $item = $items[$itemIndex];
        if (!isset($item['addons'][$addonIndex])) return;
        
        $addon = $item['addons'][$addonIndex];
        
        // Don't allow removing required add-ons
        if (!empty($addon['is_required'])) {
            return;
        }
        
        $this->cart = $svc->removeAddonFromCart($item['id'], $addon);
        $this->recalcTotals();
    }
    
    public function updated($propertyName, ECommerceService $svc)
    {
        // Handle cart.items.X.qty updates
        if (preg_match('/^cart\.items\.(\d+)\.qty$/', $propertyName, $matches)) {
            $itemIndex = (int)$matches[1];
            
            // Get the current items array
            $currentItems = array_values($this->cart['items'] ?? []);
            
            if (!isset($currentItems[$itemIndex])) {
                return;
            }
            
            $item = $currentItems[$itemIndex];
            $itemId = $item['id'] ?? null;
            
            if (!$itemId) {
                return;
            }
            
            // Get the new quantity from the updated property (Livewire has already updated it)
            $newQty = max(1, (int)($currentItems[$itemIndex]['qty'] ?? 1));
            $oldQty = (int)($item['qty'] ?? 1);
            
            \Log::info('CartPage::updated - main item qty change', [
                'item_id' => $itemId,
                'item_index' => $itemIndex,
                'old_qty' => $oldQty,
                'new_qty' => $newQty,
                'current_items_qty' => $currentItems[$itemIndex]['qty'] ?? 'missing',
            ]);
            
            // If quantity hasn't actually changed, skip update
            if ($newQty === $oldQty) {
                \Log::info('CartPage::updated - qty unchanged, skipping', ['qty' => $newQty]);
                return;
            }
            
            // Update the item in the database/session via service
            $updatedCart = $svc->updateCartItem($itemId, $newQty);
            
            \Log::info('CartPage::updated - after service call', [
                'returned_cart_item_count' => count($updatedCart['items'] ?? []),
                'returned_cart_total' => $updatedCart['total'] ?? 'missing',
            ]);
            
            // CRITICAL: Preserve the quantity the user just entered
            // The service might return old data, so we need to ensure the new quantity is set
            $updatedItems = array_values($updatedCart['items'] ?? []);
            $itemFoundInReturnedCart = false;
            foreach ($updatedItems as $idx => $updatedItem) {
                if (($updatedItem['id'] ?? null) === $itemId) {
                    $itemFoundInReturnedCart = true;
                    $returnedQty = (int)($updatedItem['qty'] ?? 1);
                    \Log::info('CartPage::updated - checking returned item', [
                        'item_id' => $itemId,
                        'returned_qty' => $returnedQty,
                        'expected_qty' => $newQty,
                        'match' => $returnedQty === $newQty,
                    ]);
                    
                    // Ensure the quantity matches what the user just entered
                    if ($returnedQty !== $newQty) {
                        \Log::warning('CartPage::updated - quantity mismatch, fixing', [
                            'expected' => $newQty,
                            'returned' => $returnedQty,
                        ]);
                    }
                    $updatedItems[$idx]['qty'] = $newQty;
                    // Recalculate subtotal with the correct quantity
                    $unitPrice = (float)($updatedItems[$idx]['unit_price'] ?? 0);
                    $updatedItems[$idx]['subtotal'] = $unitPrice * $newQty;
                    break;
                }
            }
            
            if (!$itemFoundInReturnedCart) {
                \Log::error('CartPage::updated - item not found in returned cart', ['item_id' => $itemId]);
            }
            
            // Create a completely new items array to ensure Livewire detects the change
            // This ensures the quantity is definitely correct and Livewire sees the update
            $finalItems = [];
            foreach ($updatedItems as $finalIdx => $finalItem) {
                if (($finalItem['id'] ?? null) === $itemId) {
                    // Ensure quantity is definitely correct - override with user's input
                    $finalItems[] = array_merge($finalItem, [
                        'qty' => $newQty,
                        'subtotal' => (float)($finalItem['unit_price'] ?? 0) * $newQty,
                    ]);
                } else {
                    $finalItems[] = $finalItem;
                }
            }
            
            // Update cart with completely new structure to ensure Livewire detects change
            // Include all other cart properties to preserve them
            $this->cart = array_merge($updatedCart, [
                'items' => $finalItems,
            ]);
            
            // Recalculate all totals and persist
            $this->recalcTotals();
            
            \Log::info('CartPage::updated - completed', [
                'final_cart_total' => $this->cart['total'] ?? 'missing',
            ]);
        }
        
        // Handle cart.items.X.addons.Y.qty updates
        if (preg_match('/^cart\.items\.(\d+)\.addons\.(\d+)\.qty$/', $propertyName, $matches)) {
            $itemIndex = (int)$matches[1];
            $addonIndex = (int)$matches[2];
            
            // CRITICAL: Livewire has already updated cart.items.X.addons.Y.qty BEFORE this method is called
            // We need to read the NEW value from the component property, not the old array
            $currentItems = array_values($this->cart['items'] ?? []);
            
            if (!isset($currentItems[$itemIndex]) || !isset($currentItems[$itemIndex]['addons'][$addonIndex])) {
                return;
            }
            
            $item = $currentItems[$itemIndex];
            $itemId = $item['id'] ?? null;
            
            if (!$itemId) {
                return;
            }
            
            // Get the UPDATED quantity from Livewire's property (already updated)
            // CRITICAL: Read from $currentItems which has the updated value from Livewire
            $newQty = (int)($currentItems[$itemIndex]['addons'][$addonIndex]['qty'] ?? 1);
            
            // Get the old quantity from the addon data (before Livewire updated it)
            // We need to get this from the addon reference before it was updated
            // Since Livewire already updated it, we'll skip the check or get it from session
            $addon = $currentItems[$itemIndex]['addons'][$addonIndex];
            
            $newQty = max(1, $newQty); // Ensure minimum of 1
            
            // Update the addon via service - this updates DB and session
            $updatedCart = $svc->updateAddonInCart($itemId, $addon, $newQty);
            
            // IMPORTANT: Only update the specific addon we changed to prevent triggering updates for other addons
            // Find the item and addon by IDs in the returned cart (indices may have changed)
            $updatedItems = array_values($updatedCart['items'] ?? []);
            $addonVariantId = $addon['variant_id'] ?? null;
            $itemFound = false;
            $addonFound = false;
            $updatedAddonData = null;
            
            foreach ($updatedItems as $itemIdx => $updatedItem) {
                if (($updatedItem['id'] ?? null) === $itemId) {
                    $itemFound = true;
                    // Find the addon in this item's addons array
                    if (isset($updatedItem['addons']) && is_array($updatedItem['addons'])) {
                        foreach ($updatedItem['addons'] as $addIdx => $updatedAddon) {
                            if (($updatedAddon['variant_id'] ?? null) === $addonVariantId) {
                                // Ensure the quantity matches what we just updated
                                if ((int)($updatedAddon['qty'] ?? 1) !== $newQty) {
                                    $updatedItems[$itemIdx]['addons'][$addIdx]['qty'] = $newQty;
                                    $updatedItems[$itemIdx]['addons'][$addIdx]['subtotal'] = 
                                        ($updatedAddon['unit_price'] ?? 0) * $newQty;
                                }
                                $updatedAddonData = $updatedItems[$itemIdx]['addons'][$addIdx];
                                $addonFound = true;
                                break;
                            }
                        }
                    }
                    break;
                }
            }
            
            // Only update the specific addon we changed to prevent triggering updates for other items/addons
            if ($itemFound && $addonFound && $updatedAddonData !== null) {
                // Find the item and addon in the current cart to update only that specific nested property
                $currentCartItems = array_values($this->cart['items'] ?? []);
                foreach ($currentCartItems as $currItemIdx => $currItem) {
                    if (($currItem['id'] ?? null) === $itemId) {
                        // Find the addon in this item
                        if (isset($currItem['addons']) && is_array($currItem['addons'])) {
                            foreach ($currItem['addons'] as $currAddIdx => $currAddon) {
                                if (($currAddon['variant_id'] ?? null) === $addonVariantId) {
                                    // Update only the specific addon to prevent triggering updates for other addons
                                    $this->cart['items'][$currItemIdx]['addons'][$currAddIdx] = $updatedAddonData;
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
                
                // Also update totals from the returned cart
                if (isset($updatedCart['total'])) {
                    $this->cart['total'] = $updatedCart['total'];
                }
                if (isset($updatedCart['count'])) {
                    $this->cart['count'] = $updatedCart['count'];
                }
                
                // Recalculate totals to ensure consistency
                $this->recalcTotals();
            } else {
                // Fallback: if item/addon not found, use the full cart update
                $this->cart = $updatedCart;
                $this->recalcTotals();
            }
        }
    }
    public function saveAndProceed(ECommerceService $svc, VoucherService $voucherService)
    {
        // Validate cart is not empty
        if (empty($this->cart['items'])) {
            $this->couponMessage = 'Your cart is empty';
            return;
        }
        
        // Check for quantity errors
        if ($this->hasQuantityErrors()) {
            $this->couponMessage = 'Some items exceed maximum quantity limits';
            return;
        }
        
        // Revalidate discounts before proceeding
        try {
            $this->recalculateDiscounts($voucherService);
        } catch (\Throwable $e) {
            \Log::error('Discount validation failed during checkout', ['error' => $e->getMessage()]);
            $this->couponMessage = 'Unable to validate coupons. Please try again.';
            return;
        }

        // Persist all current quantities, then go to checkout
        foreach (($this->cart['items'] ?? []) as $it) {
            try {
                $svc->updateCartItem($it['id'], (int)$it['qty']);
            } catch (\Throwable $e) {
                \Log::error('Failed to update cart item', ['id' => $it['id'], 'error' => $e->getMessage()]);
                $this->couponMessage = 'Failed to update cart. Please try again.';
                return;
            }
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