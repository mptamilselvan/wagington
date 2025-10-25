<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;

class AddPaymentMethod extends Component
{
    public $clientSecret = null;
    public $publishableKey = null;
    public $isLoading = false;

    protected $paymentService;

    public function boot(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function mount()
    {
        // Security check: Only customers can add payment methods
        $user = Auth::user();
        if (!$user || !$user->hasRole('customer')) {
            abort(403, 'Access denied. Customer access required.');
        }
        
        $this->publishableKey = config('services.stripe.key');
        $this->createSetupIntent();
    }

    public function createSetupIntent()
    {
        $this->isLoading = true;
        $user = Auth::user();
        $result = $this->paymentService->createSetupIntent($user);
        
        if ($result['status'] === 'success') {
            $this->clientSecret = $result['client_secret'];
        } else {
            session()->flash('error', $result['message']);
        }
        $this->isLoading = false;
    }



    public function render()
    {
        // Use Livewire layout only once; the view itself no longer extends a layout
        return view('livewire.frontend.add-payment-method')->layout('layouts.frontend.index');
    }
}