<?php

namespace App\Livewire\Frontend\Ecommerce;

use Livewire\Component;
use App\Services\ECommerceService;

class MiniCart extends Component
{
    public array $cart = [];

    public function mount(ECommerceService $svc)
    {
        $this->cart = $svc->getCart();
    }


    public function refreshCart(ECommerceService $svc)
    {
        $this->cart = $svc->getCart();
    }

    private function recalcTotals(): void
    {
        $items = $this->cart['items'] ?? [];

        // Recompute base subtotals (product only)
        foreach ($items as &$it) {
            $baseSubtotal = (float)($it['unit_price'] ?? 0) * (int)($it['qty'] ?? 1);
            $it['subtotal'] = $baseSubtotal; // keep item subtotal as base (UI shows addons separately)
        }
        unset($it);

        // Compute cart total = sum of base + addons
        $total = 0.0;
        foreach ($items as $it) {
            $line = (float)($it['subtotal'] ?? 0);
            if (!empty($it['addons']) && is_array($it['addons'])) {
                foreach ($it['addons'] as $ad) {
                    // Prefer given addon subtotal; else compute from unit_price * qty
                    $line += (float)($ad['subtotal'] ?? ((float)($ad['unit_price'] ?? 0) * (int)($ad['qty'] ?? 1)));
                }
            }
            $total += $line;
        }

        $this->cart['items'] = $items;
        $this->cart['total'] = $total;
    }


    public function increment(string $id)
    {
        $items = $this->cart['items'] ?? [];
        if (!is_array($items) || $items === []) {
            return;
        }

        $idx = collect($items)->search(fn($it) => ($it['id'] ?? null) === $id);
        if ($idx === false || !isset($this->cart['items'][$idx])) {
            return;
        }

        $this->cart['items'][$idx]['qty'] = (int)($this->cart['items'][$idx]['qty'] ?? 0) + 1;
        $this->recalcTotals();
    }

    public function decrement(string $id)
    {
        $items = $this->cart['items'] ?? [];
        if (!is_array($items) || $items === []) {
            return;
        }

        $idx = collect($items)->search(fn($it) => ($it['id'] ?? null) === $id);
        if ($idx === false || !isset($this->cart['items'][$idx])) {
            return;
        }

        $currentQty = (int)($this->cart['items'][$idx]['qty'] ?? 1);
        $this->cart['items'][$idx]['qty'] = max(1, $currentQty - 1);
        $this->recalcTotals();
    }


    public function remove(string $id, ECommerceService $svc)
    {
        $this->cart = $svc->removeCartItem($id);
    }

    public function proceed(ECommerceService $svc)
    {
        // Authenticated flow: persist then go to checkout
        foreach (($this->cart['items'] ?? []) as $it) {
            $svc->updateCartItem($it['id'], (int)$it['qty']);
        }
        $this->cart = $svc->getCart();
        return redirect()->route('shop.checkout');
    }

    public function guestProceed(ECommerceService $svc)
    {
        // Guest flow: aggregate by variant and persist once to avoid drift
        $byVariant = [];
        foreach (($this->cart['items'] ?? []) as $it) {
            $vid = (int)($it['variant_id'] ?? 0);
            if ($vid <= 0) continue;
            $byVariant[$vid] = ($byVariant[$vid] ?? 0) + (int)$it['qty'];
        }
        foreach ($byVariant as $variantId => $qty) {
            // Persist aggregated quantity to guest_cart_items without touching session lines
            $svc->upsertGuestVariantQty($variantId, $qty);
        }
        // Keep session snapshot as-is to avoid visual jumps; do not rebuild here
        $this->dispatch('show-auth-prompt');
    }

    public function render()
    {
        return view('livewire.frontend.ecommerce.mini-cart');
    }
}