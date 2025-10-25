<?php

namespace App\Livewire\Frontend\Ecommerce;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class ThankYouPage extends Component
{
    public string $orderNumber;
    public ?Order $order = null;

    public function mount(string $orderNumber)
    {
        $this->orderNumber = $orderNumber;
        // Bind the lookup to the signed-in customer to prevent arbitrary access.
        $user = Auth::user();
        abort_unless($user, 401, 'Unauthorized');

        $this->order = Order::with([
            'items.addons',
            'shippingAddress',
            'billingAddress',
            'payments', // Add payments relationship to load invoice information
            'appliedVouchers' // Use appliedVouchers relationship instead of orderVouchers
        ])->where('order_number', $orderNumber)
          ->where('user_id', $user->id)
          ->firstOrFail();
    }

    public function getTotalSavings()
    {
        if (!$this->order) {
            return 0;
        }

        // Use the strikethrough discount amount stored in the order
        $strikethroughSavings = (float) ($this->order->strikethrough_discount_amount ?? 0);
        
        // Add coupon discount amount
        $couponSavings = (float) ($this->order->coupon_discount_amount ?? 0);
        
        return $strikethroughSavings + $couponSavings;
    }

    private function getCouponAndTax(): array
    {
        if (!$this->order) return ['tax' => 0.0, 'tax_rate' => 0.0, 'coupon' => null, 'coupon_discount' => 0.0];
        // Tax already stored on order; expose rate if available
        $tax = (float) ($this->order->tax_amount ?? 0);
        // Use the tax rate that was applied when the order was placed, fallback to current tax service rate
        $taxRate = (float) ($this->order->applied_tax_rate ?? app(\App\Services\TaxService::class)->getActiveRate());

        // Coupon: if we persisted discount and code on order, surface it
        $coupon = null; $couponDiscount = 0.0;
        if (!empty($this->order->discount_amount) && $this->order->discount_amount > 0) {
            $couponDiscount = (float)$this->order->discount_amount;
            $coupon = $this->order->coupon_code ?? null;
        }
        return ['tax' => $tax, 'tax_rate' => $taxRate, 'coupon' => $coupon, 'coupon_discount' => $couponDiscount];
    }

    /**
     * Get the primary image for a product variant
     */
    private function getVariantImage($variantId)
    {
        try {
            $variant = \App\Models\ProductVariant::find($variantId);
            if ($variant) {
                $primaryImage = $variant->getPrimaryImage();
                return $primaryImage ? $primaryImage->file_url : null;
            }
        } catch (\Exception $e) {
            \Log::warning('Error getting variant image', [
                'variant_id' => $variantId,
                'error' => $e->getMessage()
            ]);
        }
        return null;
    }

    /**
     * Get images for all order items and addons
     */
    private function getOrderItemImages(): array
    {
        $images = ['items' => [], 'addons' => []];
        
        if (!$this->order) {
            return $images;
        }

        foreach ($this->order->items as $item) {
            $images['items'][$item->id] = $this->getVariantImage($item->variant_id);
            
            foreach ($item->addons as $addon) {
                $images['addons'][$addon->id] = $this->getVariantImage($addon->addon_variant_id);
            }
        }

        return $images;
    }

    public function render()
    {
        $meta = $this->getCouponAndTax();
        $images = $this->getOrderItemImages();
        
        return view('livewire.frontend.ecommerce.thank-you-page', [
            'order' => $this->order,
            'totalSavings' => $this->getTotalSavings(),
            'taxAmount' => $meta['tax'],
            'taxRate' => $meta['tax_rate'],
            'couponCode' => $meta['coupon'],
            'couponDiscount' => $meta['coupon_discount'],
            'itemImages' => $images['items'],
            'addonImages' => $images['addons'],
        ])->layout('layouts.frontend.index');
    }
}