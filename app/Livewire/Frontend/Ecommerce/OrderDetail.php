<?php

namespace App\Livewire\Frontend\Ecommerce;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\ECommerceService;

class OrderDetail extends Component
{
    public $orderNumber;
    public $orderDetail;

    public function mount($orderNumber)
    {
        // Ensure user is authenticated
        abort_unless(Auth::check() && Auth::user()->hasRole('customer'), 403, 'Access denied.');
        
        $this->orderNumber = $orderNumber;
        $this->loadOrderDetail();
    }

    public function loadOrderDetail()
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('customer'), 403, 'Access denied.');

        $ecommerceService = app(ECommerceService::class);
        try {
            $this->orderDetail = $ecommerceService->getUserOrderDetail($user->id, $this->orderNumber);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Order not found');
        }
    }

    public function render()
    {
        return view('livewire.frontend.ecommerce.order-detail', [
            'orderDetail' => $this->orderDetail
        ])->layout('layouts.frontend.index');
    }

    public function goBack()
    {
        return redirect()->route('customer.order-history');
    }

    public function getStatusDisplayName($status)
    {
        return match($status) {
            'pending' => 'Order Placed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => ucfirst($status)
        };
    }

    public function getItemProgressSteps($fulfillmentStatus)
    {
        // Map fulfillment status to progress steps for individual items
        $status = $fulfillmentStatus ?? 'pending';
        
        // Check if this is a non-shippable (digital) product
        $isNonShippable = in_array($status, ['awaiting_handover', 'handed_over']);
        
        if ($isNonShippable) {
            // Non-shippable products: awaiting_handover -> handed_over
            $steps = [
                [
                    'key' => 'awaiting_handover',
                    'label' => 'Awaiting Handover',
                    'icon' => 'clipboard-list',
                    'active' => in_array($status, ['awaiting_handover', 'handed_over'])
                ],
                [
                    'key' => 'handed_over',
                    'label' => 'Handed Over',
                    'icon' => 'check-circle',
                    'active' => $status === 'handed_over'
                ]
            ];
        } else {
            // Shippable products
            $steps = [];
            
            // Only show "Awaiting Stock" if item is actually awaiting stock
            if ($status === 'awaiting_stock') {
                $steps[] = [
                    'key' => 'awaiting_stock',
                    'label' => 'Awaiting Stock',
                    'icon' => 'clock',
                    'active' => true
                ];
            }
            
            // Always show these steps for shippable products
            $steps[] = [
                'key' => 'processing',
                'label' => 'Processing',
                'icon' => 'cog',
                'active' => in_array($status, ['processing', 'shipped', 'delivered'])
            ];
            
            $steps[] = [
                'key' => 'shipped',
                'label' => 'Shipped',
                'icon' => 'truck',
                'active' => in_array($status, ['shipped', 'delivered'])
            ];
            
            $steps[] = [
                'key' => 'delivered',
                'label' => 'Delivered',
                'icon' => 'check-circle',
                'active' => $status === 'delivered'
            ];
        }

        return $steps;
    }
    
    public function getStatusLabel($fulfillmentStatus)
    {
        return match($fulfillmentStatus) {
            'pending' => 'Pending',
            'awaiting_stock' => 'Awaiting Stock',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'awaiting_handover' => 'Awaiting Handover',
            'handed_over' => 'Handed Over',
            default => ucfirst(str_replace('_', ' ', $fulfillmentStatus))
        };
    }

    public function getInvoiceDataProperty()
    {
        if (empty($this->orderDetail['payment']['history']) ||
            empty($this->orderDetail['payment']['history'][0])) {
            return null;
        }

        $payment = $this->orderDetail['payment']['history'][0];
        
        // Only return data if we have at least one invoice-related field
        if (empty($payment['invoice_url']) && 
            empty($payment['invoice_pdf_url']) && 
            empty($payment['invoice_number'])) {
            return null;
        }

        return [
            'payment' => $payment,
            'order_number' => $this->orderDetail['order']['order_number'] ?? null
        ];
    }
}