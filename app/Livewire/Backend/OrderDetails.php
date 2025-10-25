<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\Order;
use App\Models\Product;
use App\Services\ImageService;

class OrderDetails extends Component
{
    public Order $order;
    public array $productImages = [];
    public array $addonImages = [];

    public bool $showBreakdown = false;

    // Accept the bound Order model (bound by order_number in the route)
    public function mount(Order $order)
    {
        // Ensure all needed relations are loaded
        $this->order = $order->load([
            'user',
            'billingAddress',
            'shippingAddress',
            'items.addons',
            'payments' => fn($q) => $q->latest(),
        ]);
        
        $this->loadImages();
    }
    
    private function loadImages()
    {
        // Load product images for main items
        $productIds = $this->order->items->pluck('product_id')->filter()->unique()->values();
        if ($productIds->isNotEmpty()) {
            $products = Product::whereIn('id', $productIds)
                ->with(['generalImages' => function($q){ $q->orderBy('display_order'); }])
                ->get();
            foreach ($products as $product) {
                $primary = $product->getPrimaryImage();
                $this->productImages[$product->id] = $primary ? ImageService::getImageUrl($primary->file_path ?: $primary->file_url) : null;
            }
        }

        // Load addon product images
        $addonProductIds = $this->order->items->flatMap(fn($item) => $item->addons->pluck('addon_product_id'))
            ->filter()->unique()->values();
        if ($addonProductIds->isNotEmpty()) {
            $addonProducts = Product::whereIn('id', $addonProductIds)
                ->with(['generalImages' => function($q){ $q->orderBy('display_order'); }])
                ->get();
            foreach ($addonProducts as $addonProduct) {
                $primary = $addonProduct->getPrimaryImage();
                $this->addonImages[$addonProduct->id] = $primary ? ImageService::getImageUrl($primary->file_path ?: $primary->file_url) : null;
            }
        }
    }

    public function toggleBreakdown(): void
    {
        $this->showBreakdown = !$this->showBreakdown;
    }

    public function render()
    {
        return view('livewire.backend.order-details')->layout('layouts.backend.index');
    }
}