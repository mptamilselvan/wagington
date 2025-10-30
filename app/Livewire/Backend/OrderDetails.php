<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\Order;
use App\Models\Product;
use App\Services\ImageService;
use App\Services\OrderFulfillmentService;
use Illuminate\Support\Facades\Log;

class OrderDetails extends Component
{
    public Order $order;
    public array $productImages = [];
    public array $addonImages = [];

    public bool $showBreakdown = false;
    
    // Fulfillment management properties
    public array $selectedItems = [];
    public string $trackingNumber = '';
    public string $carrier = '';
    public bool $showFulfillmentModal = false;
    public string $fulfillmentAction = 'processing'; // processing, shipped, delivered, handed_over

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
        $addonProductIds = $this->order->items->flatMap(fn($item) => $item->addons ? $item->addons->pluck('addon_product_id') : collect())
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

    // Fulfillment management methods
    public function openFulfillmentAction(string $action): void
    {
        \Log::info('openFulfillmentAction called', ['action' => $action]);
        $this->fulfillmentAction = $action;
        $this->showFulfillmentModal = true;
        $this->reset(['trackingNumber', 'carrier', 'selectedItems']);
    }

    public function closeFulfillmentAction(): void
    {
        \Log::info('closeFulfillmentAction called');
        $this->showFulfillmentModal = false;
        $this->reset(['trackingNumber', 'carrier', 'selectedItems']);
    }

    public function toggleItemSelection(int $itemId): void
    {
        if (in_array($itemId, $this->selectedItems)) {
            $this->selectedItems = array_diff($this->selectedItems, [$itemId]);
        } else {
            $this->selectedItems[] = $itemId;
        }
    }

    public function selectAllShippableItems(): void
    {
        $selectedItems = [];
        
        // Select main items that are eligible for the current action
        foreach ($this->order->items as $item) {
            if ($this->isItemEligibleForAction($item, $this->fulfillmentAction)) {
                $selectedItems[] = "item_{$item->id}";
            }
            
            // Select addons that are eligible for the current action
            foreach ($item->addons as $addon) {
                if ($this->isItemEligibleForAction($addon, $this->fulfillmentAction)) {
                    $selectedItems[] = "addon_{$addon->id}";
                }
            }
        }
        
        $this->selectedItems = $selectedItems;
    }

    public function clearSelection(): void
    {
        $this->selectedItems = [];
    }

    public function processFulfillment(): void
    {
        Log::info('processFulfillment called', [
            'action' => $this->fulfillmentAction,
            'selectedItems' => $this->selectedItems,
            'trackingNumber' => $this->trackingNumber,
            'carrier' => $this->carrier
        ]);
        
        if (empty($this->selectedItems)) {
            Log::warning('No items selected');
            $this->addError('selectedItems', 'Please select at least one item.');
            return;
        }

        if ($this->fulfillmentAction === 'shipped' && (empty($this->trackingNumber) || empty($this->carrier))) {
            Log::warning('Missing tracking info', [
                'trackingNumber' => $this->trackingNumber,
                'carrier' => $this->carrier
            ]);
            $this->addError('trackingNumber', 'Tracking number and carrier are required for shipped items.');
            return;
        }

        try {
            $fulfillmentService = new OrderFulfillmentService();
            
            switch ($this->fulfillmentAction) {
                case 'processing':
                    $results = $fulfillmentService->markItemsAsProcessing($this->selectedItems);
                    break;
                case 'shipped':
                    $results = $fulfillmentService->markItemsAsShipped($this->selectedItems, $this->trackingNumber, $this->carrier);
                    break;
                case 'delivered':
                    $results = $fulfillmentService->markItemsAsDelivered($this->selectedItems);
                    break;
                case 'handed_over':
                    $results = $fulfillmentService->markItemsAsHandedOver($this->selectedItems);
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid fulfillment action');
            }

            // Check if all operations were successful
            $allSuccessful = collect($results)->every(fn($result) => $result['success']);
            
            if ($allSuccessful) {
                $this->closeFulfillmentAction();
                $this->order->refresh()->load([
                    'items.addons',
                    'user',
                    'billingAddress',
                    'shippingAddress',
                    'payments'
                ]);
                session()->flash('success', 'Fulfillment status updated successfully.');
            } else {
                $errors = collect($results)->where('success', false)->pluck('message')->toArray();
                $this->addError('fulfillment', implode(', ', $errors));
            }

        } catch (\Exception $e) {
            Log::error('Fulfillment processing failed', [
                'order_id' => $this->order->id,
                'action' => $this->fulfillmentAction,
                'items' => $this->selectedItems,
                'error' => $e->getMessage()
            ]);
            
            $this->addError('fulfillment', 'Failed to update fulfillment status: ' . $e->getMessage());
        }
    }

    public function getFulfillmentSummary()
    {
        $summary = $this->order->getFulfillmentSummary();
        \Log::info('Fulfillment summary', $summary);
        return $summary;
    }

    /**
     * Check if an item (OrderItem or OrderAddon) is eligible for the current fulfillment action
     *
     * @param mixed $item OrderItem or OrderAddon
     * @param string $action The fulfillment action (processing, shipped, delivered, handed_over)
     * @return bool
     */
    public function isItemEligibleForAction($item, string $action): bool
    {
        // Check eligibility based on current status and requested action
        switch ($action) {
            case 'processing':
                // Only pending items can be marked as processing
                return $item->fulfillment_status === 'pending';
                
            case 'shipped':
                // Only processing items can be marked as shipped
                return $item->fulfillment_status === 'processing';
                
            case 'delivered':
                // Only shipped items can be marked as delivered
                return $item->fulfillment_status === 'shipped';
                
            case 'handed_over':
                // Only awaiting_handover items can be marked as handed over
                return $item->fulfillment_status === 'awaiting_handover';
                
            default:
                return false;
        }
    }

    public function render()
    {
        return view('livewire.backend.order-details')->layout('layouts.backend.index');
    }
}