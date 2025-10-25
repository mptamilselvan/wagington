<?php

namespace App\Livewire\Frontend;

use App\Http\Controllers\Controller;

class CustomerPaymentMethodController extends Controller
{
    /**
     * Show payment methods page
     */
    public function showPaymentMethods()
    {
        return view('frontend.payment-methods');
    }
}