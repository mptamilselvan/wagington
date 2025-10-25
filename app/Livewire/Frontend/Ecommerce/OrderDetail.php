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

    public function getProgressSteps($status)
    {
        // Special handling for backordered status - should not activate processing
        $isBackordered = in_array($status, ['backordered', 'partially_backordered']);
        
        $steps = [
            [
                'key' => 'placed',
                'label' => 'Order Placed',
                'icon' => 'clipboard-list',
                'active' => true
            ],
            [
                'key' => 'processing',
                'label' => 'Processing',
                'icon' => 'cog',
                'active' => !$isBackordered && in_array($status, ['processing', 'shipped', 'delivered'])
            ],
            [
                'key' => 'shipped',
                'label' => 'Shipped',
                'icon' => 'truck',
                'active' => !$isBackordered && in_array($status, ['shipped', 'delivered'])
            ],
            [
                'key' => 'delivered',
                'label' => 'Delivered',
                'icon' => 'check-circle',
                'active' => !$isBackordered && $status === 'delivered'
            ]
        ];

        return $steps;
    }
}