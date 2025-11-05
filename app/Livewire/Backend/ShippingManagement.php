<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ShippingRate;
use Illuminate\Validation\Rule;

class ShippingManagement extends Component
{
    use WithPagination;

    // UI State
    public $showForm = false;
    public $showList = true;
    public $editingShippingRate = null;
    public $search = '';
    public $showDeleteModal = false;
    public $shippingRateToDelete = null;
    public $showSuccessModal = false;
    public $successMessage = '';

    // Pagination
    public $perPage = 10;

    // Form Properties
    public $region = '';
    public $weight_min = '';
    public $weight_max = '';
    public $volume_min = '';
    public $volume_max = '';
    public $cost = '';

    protected function rules()
    {
        return [
            'region' => [
                'required',
                'string',
                'max:50',
                Rule::unique('shipping_rates', 'region')->ignore($this->editingShippingRate)
            ],
            'weight_min' => 'nullable|numeric|min:0',
            'weight_max' => 'nullable|numeric|min:0|gte:weight_min',
            'volume_min' => 'nullable|numeric|min:0',
            'volume_max' => 'nullable|numeric|min:0|gte:volume_min',
            'cost' => 'required|numeric|min:0',
        ];
    }

    protected $messages = [
        'weight_max.gte' => 'Weight max must be greater than or equal to weight min.',
        'volume_max.gte' => 'Volume max must be greater than or equal to volume min.',
        'region.unique' => 'A shipping rate for this region already exists.',
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function render()
    {
        $shippingRates = ShippingRate::query()
            ->when($this->search, function ($query) {
                $query->where('region', 'like', '%' . $this->search . '%');
            })
            ->orderBy('region')
            ->paginate($this->perPage);

        return view('livewire.backend.shipping-management', [
            'shippingRates' => $shippingRates
        ])->layout('layouts.backend.index');
    }

    public function showAddForm()
    {
        $this->resetForm();
        $this->showForm = true;
        $this->showList = false;
        $this->editingShippingRate = null;
    }

    public function showEditForm($id)
    {
        $shippingRate = ShippingRate::findOrFail($id);
        $this->editingShippingRate = $id;
        $this->region = $shippingRate->region;
        $this->weight_min = $shippingRate->weight_min;
        $this->weight_max = $shippingRate->weight_max;
        $this->volume_min = $shippingRate->volume_min;
        $this->volume_max = $shippingRate->volume_max;
        $this->cost = $shippingRate->cost;
        
        $this->showForm = true;
        $this->showList = false;
    }
    public function save()
    {
        $this->validate();

        if ($this->editingShippingRate) {
            // Update existing
            $shippingRate = ShippingRate::findOrFail($this->editingShippingRate);
            $shippingRate->update([
                'region' => $this->region,
                'weight_min' => $this->weight_min ?: null,
                'weight_max' => $this->weight_max ?: null,
                'volume_min' => $this->volume_min ?: null,
                'volume_max' => $this->volume_max ?: null,
                'cost' => $this->cost,
            ]);
            $this->successMessage = 'Shipping rate updated successfully!';
        } else {
            // Create new
            ShippingRate::create([
                'region' => $this->region,
                'weight_min' => $this->weight_min ?: null,
                'weight_max' => $this->weight_max ?: null,
                'volume_min' => $this->volume_min ?: null,
                'volume_max' => $this->volume_max ?: null,
                'cost' => $this->cost,
            ]);
            $this->successMessage = 'Shipping rate created successfully!';
        }

        $this->resetForm();
        $this->showList();
        $this->showSuccessModal = true;
    }

    public function confirmDelete($id)
    {
        $this->shippingRateToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if ($this->shippingRateToDelete) {
            ShippingRate::findOrFail($this->shippingRateToDelete)->delete();
            $this->successMessage = 'Shipping rate deleted successfully!';
            $this->showSuccessModal = true;
        }
        
        $this->shippingRateToDelete = null;
        $this->showDeleteModal = false;
        $this->resetPage();
    }

    public function cancelDelete()
    {
        $this->shippingRateToDelete = null;
        $this->showDeleteModal = false;
    }

    public function showList()
    {
        $this->showForm = false;
        $this->showList = true;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingShippingRate = null;
        $this->region = '';
        $this->weight_min = '';
        $this->weight_max = '';
        $this->volume_min = '';
        $this->volume_max = '';
        $this->cost = '';
        $this->resetErrorBag();
    }

    public function closeModal()
    {
        $this->showSuccessModal = false;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}