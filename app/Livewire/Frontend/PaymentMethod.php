<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;

class PaymentMethod extends Component
{
    public $paymentMethods = [];
    public $showDeleteModal = false;
    public $selectedPaymentMethodId = null;
    public $isDeleting = false;

    // Set default confirmation state
    public $showSetDefaultModal = false;
    public $selectedDefaultPaymentMethodId = null;
    public $isSettingDefault = false;

    protected $paymentService;

    public function boot(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function mount()
    {
        // Security check: Only customers can access payment methods
        $user = Auth::user();
        if (!$user || !$user->hasRole('customer')) {
            abort(403, 'Access denied. Customer access required.');
        }
        
        try {
            $this->loadPaymentMethods();
            
            // Check for success parameter from URL
            if (request()->get('success') == '1') {
                session()->flash('message', 'Payment method added successfully');
            }
        } catch (\Exception $e) {
            \Log::error('Error in PaymentMethod mount', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Unable to load payment methods. Please refresh the page.');
            $this->paymentMethods = [];
        }
    }

    public function loadPaymentMethods()
    {
        try {
            $user = Auth::user();
            $result = $this->paymentService->getPaymentMethods($user);
            
            if ($result['status'] === 'success') {
                $this->paymentMethods = $result['payment_methods'] ?? [];
                
                // Get default payment method
                $defaultResult = $this->paymentService->getDefaultPaymentMethod($user);
                $defaultPaymentMethodId = $defaultResult['status'] === 'success' && $defaultResult['default_payment_method'] 
                    ? $defaultResult['default_payment_method']['id'] 
                    : null;

                // Mark default payment method
                if ($defaultPaymentMethodId && !empty($this->paymentMethods)) {
                    foreach ($this->paymentMethods as &$method) {
                        $method['is_default'] = $method['id'] === $defaultPaymentMethodId;
                    }
                }
            } else {
                $this->paymentMethods = [];
                \Log::warning('Failed to load payment methods', [
                    'user_id' => $user->id,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            $this->paymentMethods = [];
            \Log::error('Exception in loadPaymentMethods', [
                'user_id' => Auth::user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }



    public function openDeleteModal($paymentMethodId)
    {
        $this->selectedPaymentMethodId = $paymentMethodId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedPaymentMethodId = null;
        $this->isDeleting = false;
    }

    public function deletePaymentMethod()
    {
        try {
            if ($this->isDeleting) {
                return; // Prevent multiple clicks
            }

            if (!$this->selectedPaymentMethodId) {
                session()->flash('error', 'No payment method selected for deletion');
                $this->closeDeleteModal();
                return;
            }

            $this->isDeleting = true;
            $user = Auth::user();
            
            // Add logging to track the deletion attempt
            \Log::info('Attempting to delete payment method', [
                'user_id' => $user->id,
                'payment_method_id' => $this->selectedPaymentMethodId
            ]);
            
            $result = $this->paymentService->deletePaymentMethod($user, $this->selectedPaymentMethodId);
            
            \Log::info('Delete payment method result', [
                'user_id' => $user->id,
                'payment_method_id' => $this->selectedPaymentMethodId,
                'result' => $result
            ]);
            
            if ($result['status'] === 'success') {
                session()->flash('message', $result['message']);
                $this->loadPaymentMethods();
            } else {
                session()->flash('error', $result['message']);
            }

            $this->closeDeleteModal();
            
        } catch (\Exception $e) {
            \Log::error('Exception in deletePaymentMethod', [
                'user_id' => Auth::user()->id ?? null,
                'payment_method_id' => $this->selectedPaymentMethodId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'An error occurred while deleting the payment method. Please try again.');
            $this->closeDeleteModal();
        } finally {
            $this->isDeleting = false;
        }
    }

    public function openSetDefaultModal($paymentMethodId)
    {
        $this->selectedDefaultPaymentMethodId = $paymentMethodId;
        $this->showSetDefaultModal = true;
    }

    public function closeSetDefaultModal()
    {
        $this->showSetDefaultModal = false;
        $this->selectedDefaultPaymentMethodId = null;
        $this->isSettingDefault = false;
    }

    public function confirmSetDefault()
    {
        try {
            if ($this->isSettingDefault) {
                return; // prevent duplicate clicks
            }

            if (!$this->selectedDefaultPaymentMethodId) {
                session()->flash('error', 'No payment method selected to set as default');
                $this->closeSetDefaultModal();
                return;
            }

            $this->isSettingDefault = true;
            $user = Auth::user();
            $result = $this->paymentService->setDefaultPaymentMethod($user, $this->selectedDefaultPaymentMethodId);
            
            if ($result['status'] === 'success') {
                session()->flash('message', $result['message'] ?? 'Default payment method updated successfully');
                $this->loadPaymentMethods();
            } else {
                session()->flash('error', $result['message'] ?? 'Failed to update default payment method');
            }

            $this->closeSetDefaultModal();
        } catch (\Exception $e) {
            \Log::error('Exception in confirmSetDefault', [
                'user_id' => Auth::user()->id ?? null,
                'payment_method_id' => $this->selectedDefaultPaymentMethodId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'An error occurred while updating the default payment method. Please try again.');
            $this->closeSetDefaultModal();
        } finally {
            $this->isSettingDefault = false;
        }
    }

    // Kept for possible direct calls, but unused by UI now
    public function setDefaultPaymentMethod($paymentMethodId)
    {
        $user = Auth::user();
        $result = $this->paymentService->setDefaultPaymentMethod($user, $paymentMethodId);
        
        if ($result['status'] === 'success') {
            session()->flash('message', $result['message']);
            $this->loadPaymentMethods();
        } else {
            session()->flash('error', $result['message']);
        }
    }





    public function exception($e, $stopPropagation)
    {
        \Log::error('Livewire component exception', [
            'component' => static::class,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        session()->flash('error', 'An unexpected error occurred. Please try again.');
        $stopPropagation();
    }



    public function render()
    {
        return view('livewire.frontend.payment-method');
    }
}
