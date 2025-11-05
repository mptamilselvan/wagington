<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\Order;
use App\Models\Product;
use App\Services\ImageService;
use App\Services\OrderFulfillmentService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Inject;

class OrderDetails extends Component
{
    public Order $order;
    public array $productImages = [];
    public array $addonImages = [];

    // Initialize the service directly instead of using #[Inject]
    private ?OrderFulfillmentService $fulfillmentService = null;

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
        
        // Initialize the fulfillment service
        $this->fulfillmentService = app(OrderFulfillmentService::class);
        
        $this->loadImages();
    }
    
    // Getter method to ensure the service is always available
    private function getFulfillmentService(): OrderFulfillmentService
    {
        if ($this->fulfillmentService === null) {
            $this->fulfillmentService = app(OrderFulfillmentService::class);
        }
        return $this->fulfillmentService;
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
        
        // Clear all previous error messages
        $this->resetErrorBag();
        
        // For shipped action, automatically select all eligible items (only processing items)
        if ($action === 'shipped') {
            $this->selectAllShippableItems();
            
            // If no items were selected, check if there are any shippable items at all
            if (empty($this->selectedItems)) {
                // First check if all shippable items are already shipped
                if ($this->areAllShippableItemsShipped()) {
                    // Don't show error when all items are already shipped, just let the modal open
                    // The "Mark Shipped" button inside will be disabled
                    return;
                }
                
                // Check if there are any items that could be shipped (in processing status)
                $hasShippableItems = false;
                foreach ($this->order->items as $item) {
                    if ($this->isItemEligibleForAction($item, 'shipped')) {
                        $hasShippableItems = true;
                        break;
                    }
                    // Check addons
                    foreach ($item->addons ?? [] as $addon) {
                        if ($this->isItemEligibleForAction($addon, 'shipped')) {
                            $hasShippableItems = true;
                            break 2;
                        }
                    }
                }
                
                // If there are shippable items but none are in processing status, show error
                if (!$hasShippableItems) {
                    // Check if there are any items that could potentially be shipped but are in wrong status
                    $hasItemsInWrongStatus = false;
                    $wrongStatusItems = [];
                    
                    foreach ($this->order->items as $item) {
                        // Skip non-shippable items
                        if (in_array($item->fulfillment_status, ['awaiting_handover', 'handed_over'])) {
                            continue;
                        }
                        
                        if ($item->fulfillment_status !== 'processing') {
                            $hasItemsInWrongStatus = true;
                            $wrongStatusItems[] = $item->product_name . ' (' . ucwords(str_replace('_', ' ', $item->fulfillment_status)) . ')';
                        }
                    }
                    
                    // Check addons
                    foreach ($this->order->items as $item) {
                        foreach ($item->addons ?? [] as $addon) {
                            // Skip non-shippable addons
                            if (in_array($addon->fulfillment_status, ['awaiting_handover', 'handed_over'])) {
                                continue;
                            }
                            
                            if ($addon->fulfillment_status !== 'processing') {
                                $hasItemsInWrongStatus = true;
                                $wrongStatusItems[] = $addon->addon_product_name . ' - Addon (' . ucwords(str_replace('_', ' ', $addon->fulfillment_status)) . ')';
                            }
                        }
                    }
                    
                    if ($hasItemsInWrongStatus) {
                        $itemsList = implode(', ', array_slice($wrongStatusItems, 0, 5)); // Limit to first 5 items
                        if (count($wrongStatusItems) > 5) {
                            $itemsList .= ' and ' . (count($wrongStatusItems) - 5) . ' more items';
                        }
                        $this->addError('fulfillment', "No items are ready for shipping. The following items must be marked as 'Processing' before they can be shipped:\n\nItems not ready: " . $itemsList . "\n\nPlease mark these items as 'Processing' first, then try again.");
                    } else {
                        $this->addError('fulfillment', "No items are available for shipping. All items in this order are either non-shippable or have already been fulfilled.");
                    }
                }
            }
        }
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
            foreach ($item->addons ?? [] as $addon) {
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
        
        // Check if items are selected first
        if (empty($this->selectedItems)) {
            // For shipped action, provide a more specific error message
            if ($this->fulfillmentAction === 'shipped') {
                // Check if all shippable items are already shipped
                if ($this->areAllShippableItemsShipped()) {
                    $this->addError('fulfillment', 'All shippable items in this order are already shipped or delivered.');
                    return;
                }
                $this->addError('selectedItems', 'No items selected for shipping. Please ensure items are in "Processing" status before marking as shipped.');
            } else {
                $this->addError('selectedItems', 'Please select at least one item.');
            }
            return;
        }

        // Special validation for shipped action - validate selected SHIPPABLE items FIRST
        if ($this->fulfillmentAction === 'shipped') {
            // 1. First check if selected SHIPPABLE items are ready
            $validation = $this->validateShippableItemsReady($this->selectedItems);
            if (!$validation['ready']) {
                $this->addError('fulfillment', $validation['message']);
                return;
            }
            
            // 2. Then check tracking info
            if (empty($this->trackingNumber)) {
                $this->addError('trackingNumber', 'Tracking number is required.');
                return;
            }
            
            if (empty($this->carrier)) {
                $this->addError('carrier', 'Carrier is required.');
                return;
            }
        }
        
        // Special validation for delivered action - validate selected items are SHIPPED
        if ($this->fulfillmentAction === 'delivered') {
            $validation = $this->validateItemsReadyForDelivered($this->selectedItems);
            if (!$validation['ready']) {
                $this->addError('fulfillment', $validation['message']);
                return;
            }
        }

        try {
            switch ($this->fulfillmentAction) {
                case 'processing':
                    $results = $this->getFulfillmentService()->markItemsAsProcessing($this->selectedItems);
                    break;
                case 'shipped':
                    $results = $this->getFulfillmentService()->markItemsAsShipped($this->selectedItems, $this->trackingNumber, $this->carrier);
                    break;
                case 'delivered':
                    $results = $this->getFulfillmentService()->markItemsAsDelivered($this->selectedItems);
                    break;
                case 'handed_over':
                    $results = $this->getFulfillmentService()->markItemsAsHandedOver($this->selectedItems);
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
                // Use success message instead of error message for success
                session()->flash('success', 'Fulfillment status updated successfully.');
            } else {
                $errors = collect($results)->where('success', false)->pluck('message')->unique()->toArray();
                $errorMessage = implode(' ', $errors);
                $this->addError('fulfillment', $errorMessage);
                // Don't close the modal if there are errors
                return;
            }

        } catch (\Exception $e) {
            Log::error('Fulfillment processing failed', [
                'order_id' => $this->order->id,
                'action' => $this->fulfillmentAction,
                'items' => $this->selectedItems,
                'error' => $e->getMessage()
            ]);
            
            $this->addError('fulfillment', 'Failed to update fulfillment status: ' . $e->getMessage());
            // Don't close the modal if there are errors
            return;
        }
    }

    public function getFulfillmentSummary()
    {
        $summary = $this->order->getFulfillmentSummary();
        \Log::info('Fulfillment summary', $summary);
        return $summary;
    }

    /**
     * Validate that all SHIPPABLE items in the order are ready for shipping
     * Non-shippable items (awaiting_handover, handed_over) are excluded from validation
     * Required because we have a single tracking number per order
     */
    private function validateShippableItemsReady(array $selectedItemIds): array
    {
        $notReadyItems = [];
        
        foreach ($selectedItemIds as $selectedId) {
            list($type, $id) = explode('_', $selectedId);
            
            if ($type === 'item') {
                $item = $this->order->items->firstWhere('id', $id);
                if (!$item) continue;
                
                // Skip non-shippable items
                if (in_array($item->fulfillment_status, ['awaiting_handover', 'handed_over'])) {
                    continue;
                }
                
                // Only items with 'processing' status can be shipped
                if ($item->fulfillment_status !== 'processing') {
                    $notReadyItems[] = $item->product_name . ' (' . ucwords(str_replace('_', ' ', $item->fulfillment_status)) . ')';
                }
            } elseif ($type === 'addon') {
                // Find the addon in any order item
                $addon = null;
                foreach ($this->order->items as $item) {
                    if ($item->addons && is_iterable($item->addons)) {
                        $addon = collect($item->addons)->firstWhere('id', $id);
                        if ($addon) break;
                    }
                }
                
                if (!$addon) continue;
                
                // Skip non-shippable addons
                if (in_array($addon->fulfillment_status, ['awaiting_handover', 'handed_over'])) {
                    continue;
                }
                
                // Only addons with 'processing' status can be shipped
                if ($addon->fulfillment_status !== 'processing') {
                    $notReadyItems[] = $addon->addon_product_name . ' - Addon (' . ucwords(str_replace('_', ' ', $addon->fulfillment_status)) . ')';
                }
            }
        }
        
        if (!empty($notReadyItems)) {
            $itemsList = implode(', ', $notReadyItems);
            return [
                'ready' => false,
                'message' => "Selected items must be in 'Processing' status before shipping.\n\nItems not ready: " . $itemsList . "\n\nPlease mark items as 'Processing' first, then try again."
            ];
        }
        
        return ['ready' => true, 'message' => ''];
    }
    
    /**
     * Validate that all SHIPPABLE items in the order are in shipped status before marking as delivered
     * Non-shippable items (awaiting_handover, handed_over) are excluded from validation
     */
    private function validateItemsReadyForDelivered(array $selectedItemIds): array
    {
        $notShippedItems = [];
        
        foreach ($selectedItemIds as $selectedId) {
            list($type, $id) = explode('_', $selectedId);
            
            if ($type === 'item') {
                $item = $this->order->items->firstWhere('id', $id);
                if (!$item) continue;
                
                // Skip non-shippable items
                if (in_array($item->fulfillment_status, ['awaiting_handover', 'handed_over'])) {
                    continue;
                }
                
                if (!in_array($item->fulfillment_status, ['shipped', 'delivered'])) {
                    $notShippedItems[] = $item->product_name . ' (' . ucwords(str_replace('_', ' ', $item->fulfillment_status)) . ')';
                }
            } elseif ($type === 'addon') {
                // Find the addon in any order item
                $addon = null;
                foreach ($this->order->items as $item) {
                    if ($item->addons && is_iterable($item->addons)) {
                        $addon = collect($item->addons)->firstWhere('id', $id);
                        if ($addon) break;
                    }
                }
                
                if (!$addon) continue;
                
                // Skip non-shippable addons
                if (in_array($addon->fulfillment_status, ['awaiting_handover', 'handed_over'])) {
                    continue;
                }
                
                if (!in_array($addon->fulfillment_status, ['shipped', 'delivered'])) {
                    $notShippedItems[] = $addon->addon_product_name . ' - Addon (' . ucwords(str_replace('_', ' ', $addon->fulfillment_status)) . ')';
                }
            }
        }
        
        if (!empty($notShippedItems)) {
            $itemsList = implode(', ', $notShippedItems);
            return [
                'ready' => false,
                'message' => "Cannot mark selected items as delivered. Items must be shipped first.\n\nItems not shipped yet: " . $itemsList . "\n\nPlease use 'Mark Shipped' button first to ship these items."
            ];
        }
        
        return ['ready' => true, 'message' => ''];
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
                // Only processing items can be marked as shipped (already shipped/delivered items should be excluded)
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

    /**
     * Check if all shippable items in the order are already shipped or delivered
     * Used to determine if the "Mark Shipped" button should be disabled
     */
    public function areAllShippableItemsShipped(): bool
    {
        foreach ($this->order->items as $item) {
            // Skip non-shippable items
            if (in_array($item->fulfillment_status, ['awaiting_handover', 'handed_over'])) {
                continue;
            }
            
            // If item is shippable but not shipped/delivered, return false
            if (!in_array($item->fulfillment_status, ['shipped', 'delivered'])) {
                return false;
            }
        }
        
        // Check addons as well
        foreach ($this->order->items as $item) {
            foreach ($item->addons ?? [] as $addon) {
                // Skip non-shippable addons
                if (in_array($addon->fulfillment_status, ['awaiting_handover', 'handed_over'])) {
                    continue;
                }
                
                // If addon is shippable but not shipped/delivered, return false
                if (!in_array($addon->fulfillment_status, ['shipped', 'delivered'])) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Check if there are any items eligible for shipping (in processing status)
     * Used to determine if the "Mark Shipped" button should be disabled
     */
    public function hasItemsEligibleForShipping(): bool
    {
        foreach ($this->order->items as $item) {
            // Check if item is shippable and in processing status
            if (!in_array($item->fulfillment_status, ['awaiting_handover', 'handed_over']) 
                && $item->fulfillment_status === 'processing') {
                return true;
            }
        }
        
        // Check addons as well
        foreach ($this->order->items as $item) {
            foreach ($item->addons ?? [] as $addon) {
                // Check if addon is shippable and in processing status
                if (!in_array($addon->fulfillment_status, ['awaiting_handover', 'handed_over']) 
                    && $addon->fulfillment_status === 'processing') {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function render()
    {
        return view('livewire.backend.order-details')->layout('layouts.backend.index');
    }
}