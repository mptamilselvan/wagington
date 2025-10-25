<?php

namespace App\Livewire\Frontend\Ecommerce;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\ECommerceService;

class Listing extends Component
{
    use WithPagination;

    public string $q = '';
    public ?int $category_id = null;
    public ?float $price_min = null;
    public ?float $price_max = null;
    public array $attrs = [];
    public string $sort = 'newest';
    public array $product_types = ['regular', 'variant']; // default: show both; excludes addon by service
    public string $shippable = ''; // '' = all, 'true' = shippable only, 'false' = non-shippable only

    protected $queryString = [
        'q' => ['except' => ''],
        'category_id' => ['except' => null],
        'price_min' => ['except' => null],
        'price_max' => ['except' => null],
        'attrs' => ['except' => []],
        'sort' => ['except' => 'newest'],
        'product_types' => ['except' => ['regular','variant']],
        'shippable' => ['except' => ''],
        // Note: pagination is handled by WithPagination in Livewire v3 via $paginators[]
        // so we should NOT add 'page' to the query string manually.
    ];

    public function mount(?int $category = null)
    {
        // Prefer route param if present
        $this->category_id = $category;
        // Backward-compat: also accept query ?category=... (old param) or ?category_id=...
        if ($this->category_id === null) {
            $qCat = request()->query('category_id') ?? request()->query('category');
            if ($qCat !== null && $qCat !== '') {
                $this->category_id = (int) $qCat;
            }
        }

        // Filters will be set in render
    }

    public function updating($field, $value)
    {
        // Prevent empty product_types (would yield no results)
        if ($field === 'product_types' && empty($value ?? [])) {
            // Reset pagination and return a sanitized default so Livewire assigns it
            $this->resetPage();
            return ['regular','variant'];
        }
        if (str_starts_with($field, 'attrs.')) {
            // Guarantee group key holds an array even after first toggle
            $parts = explode('.', $field, 2);
            $groupKey = $parts[1] ?? null;
            if ($groupKey && !is_array($this->attrs[$groupKey] ?? null)) {
                $this->attrs[$groupKey] = [];
            }
        }
        if (in_array($field, ['q','category_id','price_min','price_max','attrs','sort','product_types','shippable']) || str_starts_with($field, 'attrs.')) {
            $this->resetPage();
        }
    }

    // Coerce price inputs to floats and keep consistent bounds
    public function updatedPriceMin($value)
    {
        // Coerce but do not auto-swap while typing
        $this->price_min = is_numeric($value) ? (float) $value : null;
        $this->resetPage();
    }

    public function updatedPriceMax($value)
    {
        $this->price_max = is_numeric($value) ? (float) $value : null;
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->price_min = null;
        $this->price_max = null;
        $this->attrs = [];
        $this->sort = 'newest';
        $this->product_types = ['regular','variant'];
        $this->category_id = null;
        $this->shippable = '';
        $this->resetPage();
    }

    // Normalize category_id from radio ("" => null, numbers => int)
    public function updatedCategoryId($value)
    {
        $this->category_id = ($value === '' || $value === null) ? null : (int) $value;
        $this->resetPage();
    }

    public function updatedSort($value)
    {
        $this->sort = $value;
        $this->resetPage();
    }

    public function updatedShippable($value)
    {
        $this->shippable = $value;
        $this->resetPage();
    }

    public function updatedQ($value)
    {
        $this->q = $value;
        $this->resetPage();
    }

    public function updated($field, $value)
    {
        // Handle attribute updates
        if (str_starts_with($field, 'attrs.')) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $params = [
            'q' => $this->q,
            // Coerce category_id from radio (could be empty string)
            'category_id' => ($this->category_id === '' || $this->category_id === null) ? null : (int) $this->category_id,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'attrs' => $this->attrs,
            'sort' => $this->sort,
            'product_types' => $this->product_types,
            'shippable' => $this->shippable,
            'per_page' => 12,
            // Use Livewire v3 pagination accessor
            'page' => $this->getPage(),
        ];


        $svc = app(ECommerceService::class);
        $products = $svc->listProducts($params);
        $filters = $svc->buildFilters($params['category_id']);

        // Normalize attrs to arrays for each group to prevent boolean coercion
        foreach (($filters['attributes'] ?? []) as $group) {
            $k = $group['name'];
            if (!isset($this->attrs[$k]) || !is_array($this->attrs[$k])) {
                $this->attrs[$k] = [];
            }
        }

        return view('livewire.frontend.ecommerce.listing', [
            'products' => $products,
            'filters' => $filters,
        ]);
    }
}