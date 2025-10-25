<?php

namespace App\Livewire\Frontend\Ecommerce;

use Livewire\Component;
use App\Services\ECommerceService;

class Landing extends Component
{
    public array $sections = [];
    public string $q = '';
    protected ECommerceService $svc;

    public function mount(ECommerceService $svc)
    {
        $this->svc      = $svc;
        $this->sections = $svc->getLandingSections();
    }

    public function updatedQ(): void
    {
        $this->sections = $this->svc->getLandingSections(q: $this->q);
    }

    public function render()
    {
        return view('livewire.frontend.ecommerce.landing');
    }
}