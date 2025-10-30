<?php

namespace App\Livewire\Frontend\Ecommerce;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\ECommerceService;

class Listing extends Component
{
    use WithPagination;

    public $q = '';
    public $category_id = null;
    public $price_min = '';
    public $price_max = '';
    public $attrs = [];
    public $sort = 'newest';
    public $product_types = ['regular', 'variant'];
    public $shippable = '';
    public $showFilterPanel = false;

    protected $queryString = [
        'q' => ['except' => ''],
        'category_id' => ['except' => null],
        'price_min' => ['except' => ''],
        'price_max' => ['except' => ''],
        'attrs' => ['except' => []],
        'sort' => ['except' => 'newest'],
        'product_types' => ['except' => ['regular','variant']],
        'shippable' => ['except' => ''],
    ];

    public function mount()
    {
        // category_id will be populated from query string automatically
    }



    public function clearFilters()
    {
        $this->reset(['price_min', 'price_max', 'attrs', 'shippable', 'q']);
        $this->sort = 'newest';
        $this->resetPage();
        $this->showFilterPanel = false;
    }



    public function applyFilters()
    {
        $this->resetPage();
        $this->showFilterPanel = false;
    }

    public function toggleFilterPanel()
    {
        $this->showFilterPanel = !$this->showFilterPanel;
        \Log::info('Toggle filter panel', ['showFilterPanel' => $this->showFilterPanel]);
    }

    public function render()
    {
        $params = [
            'q' => $this->q,
            'category_id' => $this->category_id ? (int) $this->category_id : null,
            'price_min' => $this->price_min && is_numeric($this->price_min) ? (float) $this->price_min : null,
            'price_max' => $this->price_max && is_numeric($this->price_max) ? (float) $this->price_max : null,
            'attrs' => $this->attrs,
            'sort' => $this->sort,
            'product_types' => $this->product_types,
            'shippable' => $this->shippable,
            'per_page' => 12,
            'page' => $this->getPage(),
        ];

        $svc = app(ECommerceService::class);
        $products = $svc->listProducts($params);
        $filters = $svc->buildFilters($params['category_id']);

        return view('livewire.frontend.ecommerce.listing', [
            'products' => $products,
            'filters' => $filters,
        ]);
    }
}