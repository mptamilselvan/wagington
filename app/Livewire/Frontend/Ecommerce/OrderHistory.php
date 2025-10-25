<?php

namespace App\Livewire\Frontend\Ecommerce;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Services\ECommerceService;

class OrderHistory extends Component
{
    use WithPagination;

    public $perPage = 10;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        // Ensure user is authenticated
        abort_unless(Auth::check() && Auth::user()->hasRole('customer'), 403, 'Access denied.');
    }

    public function render()
    {
        // Re-check authorization on each re-render (Livewire rehydration may skip mount())
        $user = Auth::user();
        abort_unless($user && $user->hasRole('customer'), 403, 'Access denied.');

        $ecommerceService = app(ECommerceService::class);
        $orders = $ecommerceService->listUserOrders($user->id, $this->perPage);
        
        return view('livewire.frontend.ecommerce.order-history', [
            'orders' => $orders,
        ])->layout('layouts.frontend.index');
    }

    public function goToOrderDetail($orderNumber)
    {
        return redirect()->route('customer.order-detail', ['orderNumber' => $orderNumber]);
    }
}