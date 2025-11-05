<?php

namespace App\Livewire\Frontend\Ecommerce;

use Livewire\Component;
use App\Services\ECommerceService;
use Illuminate\Support\Collection;

class ProductShow extends Component
{
    public string $slug;
    public array $product = [];
    public array $recommendations = [];

    // UI state
    public ?int $selectedVariantId = null;
    public array $activeGallery = [];
    public int $activeIndex = 0;
    public array $attributeOptions = [];
    public array $selectedAttributes = [];
    public array $availableValues = [];
    public array $attributeMeta = []; // e.g., ['Color' => ['Red' => '#ff0000']]
    public int $qty = 1;
    // Removed pincode UI/state
    public string $pincode = '';
    public ?array $pincodeResult = null;
    public string $activeTab = 'features';

    // Addons UI state
    public array $selectedAddons = []; // keyed by addon product id => ['product_id'=>..,'variant_id'=>..,'qty'=>1,'is_required'=>bool,'name'=>..,'variant_name'=>..,'sku'=>..,'unit_price'=>..,'subtotal'=>..]

    // Error state
    public ?string $errorMessage = null;

    public function mount(string $slug, ECommerceService $svc)
    {
        $this->slug = $slug;
        $model = $svc->getProductBySlug($slug);
        $this->product = $svc->presentDetail($model);
        $this->recommendations = $svc->getRecommendations($model);

        // Initialize addons selection: include required by default, optional preselected (can be deselected)
        foreach (($this->product['addons'] ?? []) as $ad) {
            $pid = $ad['id'];
            $variant = $ad['variant'] ?? null;
            $isRequired = (bool)($ad['is_required'] ?? false);
            $this->selectedAddons[$pid] = [
                'product_id' => $pid,
                'variant_id' => $variant['id'] ?? null,
                'qty' => 1,
                'is_required' => $isRequired,
                'name' => $ad['name'] ?? 'Addon',
                'variant_name' => $variant['name'] ?? null,
                'sku' => $variant['sku'] ?? null,
                'unit_price' => (float)($variant['price'] ?? 0),
                'subtotal' => (float)($variant['price'] ?? 0) * 1,
                'selected' => $isRequired, // required addons selected by default, optional addons not selected
            ];
        }

        // Derive attribute options from all variants
        $variants = collect($this->product['variants'] ?? []);
        $attrMap = [];
        foreach ($variants as $v) {
            // Variants come with 'variant_attributes' from presenter
            foreach (($v['variant_attributes'] ?? []) as $k => $val) {
                $attrMap[$k] = $attrMap[$k] ?? [];
                if (!in_array($val, $attrMap[$k], true)) { $attrMap[$k][] = $val; }
            }
        }
        $this->attributeOptions = array_map(fn($vals) => array_values($vals), $attrMap);

        // Build attribute meta (color_hex etc.) keyed by attribute type/name -> value -> meta
        $this->attributeMeta = app(\App\Services\ECommerceService::class)->getAttributeMeta($model);

        // Preselect: primary variant else first; mirror its attributes
        $primary = $variants->firstWhere('is_primary', true) ?? $variants->first();
        $this->selectedVariantId = $primary['id'] ?? null;
        $this->selectedAttributes = $primary['variant_attributes'] ?? [];

        // Active gallery for selected variant, else product gallery
        $this->activeGallery = ($primary['gallery'] ?? []) ?: ($this->product['gallery'] ?? []);
        $this->activeIndex = 0;

        // Initialize available values matrix
        $this->recalculateAvailableValues();
    }

    public function selectVariant($variantId)
    {
        $this->selectedVariantId = $variantId;
        $v = collect($this->product['variants'])->firstWhere('id', $this->selectedVariantId);
        if ($v) {
            $this->selectedAttributes = $v['variant_attributes'] ?? [];
            $this->activeGallery = ($v['gallery'] ?? []) ?: ($this->product['gallery'] ?? []);
            $this->activeIndex = 0;
        }
    }

    public function selectAttribute(string $name, string $value)
    {
        $this->selectedAttributes[$name] = $value;
        $this->recalculateAvailableValues();

        // Find best match variant using selected attributes
        $match = collect($this->product['variants'])->first(function($v){
            foreach (($this->selectedAttributes ?? []) as $k => $val) {
                if (($v['variant_attributes'][$k] ?? null) !== $val) return false;
            }
            return true;
        });
        if ($match) {
            $this->selectedVariantId = $match['id'];
            $this->activeGallery = ($match['gallery'] ?? []) ?: ($this->product['gallery'] ?? []);
            $this->activeIndex = 0;
        }
    }

    private function recalculateAvailableValues(): void
    {
        $variants = collect($this->product['variants'] ?? []);
        $avail = [];
        // For each attribute key, compute which values are still available given current selections of other keys
        foreach (array_keys($this->attributeOptions) as $attrKey) {
            $filtered = $variants->filter(function($v) use ($attrKey) {
                foreach ($this->selectedAttributes as $k => $val) {
                    if ($k === $attrKey) continue; // ignore current attribute to compute its possibilities
                    if (($v['variant_attributes'][$k] ?? null) !== $val) return false;
                }
                return true;
            });
            $avail[$attrKey] = $filtered->pluck('variant_attributes.' . $attrKey)->filter()->unique()->values()->all();
        }
        $this->availableValues = $avail;
    }

    public function setActiveIndex(int $index)
    {
        $this->activeIndex = max(0, $index);
    }

    public function checkPincode()
    {
        // Dummy ETA logic; integrate real API later
        $this->pincodeResult = null;
        $code = trim($this->pincode);
        if ($code === '') return;
        $eta = (int) substr(md5($code), 0, 1) + 2; // pseudo days 2-11
        $this->pincodeResult = [
            'pincode' => $code,
            'eta_days' => $eta,
            'message' => "Estimated delivery in {$eta} day(s)",
        ];
    }

    public function updateAddonSubtotal($addonId)
    {
        if (isset($this->selectedAddons[$addonId])) {
            $qty = max(1, (int)($this->selectedAddons[$addonId]['qty'] ?? 1));
            $unitPrice = (float)($this->selectedAddons[$addonId]['unit_price'] ?? 0);
            $this->selectedAddons[$addonId]['qty'] = $qty;
            $this->selectedAddons[$addonId]['subtotal'] = $unitPrice * $qty;
        }
    }

    public function updatedSelectedAddons($value, $key)
    {
        // Extract addon ID and field from the key (e.g., "123.qty" -> addon ID 123, field "qty")
        if (preg_match('/(\d+)\.qty$/', $key, $matches)) {
            $addonId = (int)$matches[1];
            $this->updateAddonSubtotal($addonId);
        }
    }

    public function addToCart(ECommerceService $svc)
    {
        if (!$this->selectedVariantId) return;
        // Prepare selected addons payload (include required always; include optional only if selected=true)
        $addons = [];
        foreach ($this->selectedAddons as $row) {
            if (!($row['is_required'] ?? false) && empty($row['selected'])) continue;
            $row['qty'] = max(1, (int)($row['qty'] ?? 1));
            $row['subtotal'] = (float)($row['unit_price'] ?? 0) * $row['qty'];
            $addons[] = [
                'product_id' => $row['product_id'] ?? null,
                'variant_id' => $row['variant_id'] ?? null,
                'name' => $row['name'] ?? 'Addon',
                'variant_name' => $row['variant_name'] ?? null,
                'sku' => $row['sku'] ?? null,
                'is_required' => (bool)($row['is_required'] ?? false),
                'qty' => $row['qty'],
                'unit_price' => (float)($row['unit_price'] ?? 0),
                'subtotal' => (float)($row['subtotal'] ?? 0),
            ];
        }
        
        try {
            $svc->addToCart($this->selectedVariantId, max(1, (int)$this->qty), $addons);
            // Clear any previous error
            $this->errorMessage = null;
            // Show mini cart for feedback; badge updates via session
            $this->dispatch('cart-updated');
        } catch (\App\Exceptions\CartException $e) {
            // Handle expected cart-related errors with user-friendly messages
            $this->errorMessage = $e->getMessage();
            $this->dispatch('cart-error', ['message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            // Log unexpected errors and show generic message
            \Illuminate\Support\Facades\Log::error('Failed to add item to cart', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'variant_id' => $this->selectedVariantId,
                'qty' => $this->qty,
                'addons' => $addons
            ]);
            $this->errorMessage = 'Unable to add item to cart. Please try again.';
            $this->dispatch('cart-error', ['message' => $this->errorMessage]);
        }
    }

    public function switchTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function updatedQty()
    {
        $this->errorMessage = null;
    }

    public function render()
    {
        return view('livewire.frontend.ecommerce.product-show');
    }
}