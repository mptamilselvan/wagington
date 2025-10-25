<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\ServicePricingAttributes;

class ServicePricingAttribute extends Component
{

    public $services_attributes, $title='Services Settings';

    public function mount()
    {
        $this->services_attributes = ServicePricingAttributes::get();

        \session(['submenu' => 'service-pricing-attributes']);
    }

    public function render()
    {
        return view('livewire.backend.service-pricing-attribute');
    }

    public function index()
    {
        return view('backend.service-pricing-attribute');
    }
}
