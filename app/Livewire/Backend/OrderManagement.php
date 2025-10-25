<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;

class OrderManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }

    public function render()
    {
        $query = Order::query()
            ->with([
                'user:id,first_name,last_name,email',
                'payments' => function ($q) { $q->latest()->limit(1); },
                'items.addons',
            ])
            ->withCount('items')
            ->orderByDesc('created_at');

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->search !== '') {
            $s = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', $s)
                  ->orWhereHas('user', function ($uq) use ($s) {
                      $uq->where(function ($userQuery) use ($s) {
                          $userQuery->where('first_name', 'like', $s)
                                    ->orWhere('last_name', 'like', $s)
                                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$s]);
                      })->orWhere('email', 'like', $s);
                  })
                  ->orWhereHas('items', function ($iq) use ($s) {
                      $iq->where('product_name', 'like', $s);
                  });
            });
        }

        return view('livewire.backend.order-management', [
            'orders' => $query->paginate($this->perPage),
        ])->layout('layouts.backend.index');
    }
}