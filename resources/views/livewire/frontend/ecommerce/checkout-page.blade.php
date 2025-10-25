@section('meta_tags')
    @php
        $metaTitle = 'Checkout - ' . config('app.name');
        $metaDescription = 'Complete your purchase securely. Review your order details, shipping information, and payment method before finalizing your order.';
        $canonicalUrl = route('shop.checkout');
    @endphp
    
    <x-seo.meta-tags 
        :title="$metaTitle"
        :description="$metaDescription"
        :canonicalUrl="$canonicalUrl"
        :type="'website'"
    />
@endsection

<div class="min-h-screen bg-gray-50">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Checkout</h1>
            <p class="text-gray-600">Complete your order in just a few steps</p>
        </div>

        @if(!$placed)
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                {{-- Main Checkout Form --}}
                <div class="lg:col-span-7 space-y-6">
                    {{-- Address Section --}}
                    <x-ecommerce.checkout-unified-address-section 
                        :addresses="$addresses"
                        :selectedShippingAddressId="$selectedShippingAddressId"
                        :selectedBillingAddressId="$selectedBillingAddressId"
                        :billingAddressSameAsShipping="$billingAddressSameAsShipping"
                        :sectionOpen="$addressSectionOpen"
                        :requiresShipping="$requiresShipping"
                    />

                    {{-- Payment Section --}}
                    <x-ecommerce.checkout-payment-section 
                        :paymentMethods="$paymentMethods"
                        :selectedPaymentMethodId="$selectedPaymentMethodId"
                        :sectionOpen="$paymentSectionOpen"
                        :disabled="!$selectedBillingAddressId || ($requiresShipping && !$selectedShippingAddressId)"
                    />

                    {{-- Error Messages --}}
                    @if($errors->any())
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-red-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-red-800 mb-1">Please correct the following errors:</h4>
                                    <ul class="text-sm text-red-700 space-y-1">
                                        @foreach($errors->all() as $error)
                                            <li>
                                                • {{ $error }}
                                                @if($error === 'The name field is required.')
                                                    <a href="{{ route('customer.profile.step', 1) }}" 
                                                       class="ml-2 text-blue-600 hover:text-blue-800 underline font-medium"
                                                       target="_blank">
                                                        Complete your profile here
                                                    </a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Order Summary Sidebar --}}
                <div class="lg:col-span-5">
                    @php
                        $totalSaved = 0;
                        foreach($cart['items'] ?? [] as $item) {
                            if (!empty($item['saved_amount']) && $item['saved_amount'] > 0) {
                                $totalSaved += (float) $item['saved_amount'];
                            }
                            if (!empty($item['addons']) && is_array($item['addons'])) {
                                foreach ($item['addons'] as $ad) {
                                    if (!empty($ad['saved_amount']) && $ad['saved_amount'] > 0) {
                                        $totalSaved += (float) $ad['saved_amount'];
                                    }
                                }
                            }
                        }

                        $couponSavings = max(0.0, (float) ($totalCouponDiscount ?? 0));
                        $totalSaved += $couponSavings;
                    @endphp

                    <x-ecommerce.checkout-order-summary 
                        :cart="$cart"
                        :checkoutSummary="$checkoutSummary"
                        :shippingAmount="$checkoutSummary['shipping_amount'] ?? 0"
                        :taxAmount="$checkoutSummary['tax']['amount'] ?? 0"
                        :taxRate="$checkoutSummary['tax']['rate'] ?? 0"
                        :appliedCoupons="$appliedCoupons"
                        :totalCouponDiscount="$totalCouponDiscount"
                        :currentCouponInput="$currentCouponInput"
                        :couponMessage="$couponMessage"
                        :maxCoupons="$maxCoupons"
                        :selectedShippingAddressId="$selectedShippingAddressId"
                        :selectedBillingAddressId="$selectedBillingAddressId"
                        :billingAddressSameAsShipping="$billingAddressSameAsShipping"
                        :selectedPaymentMethodId="$selectedPaymentMethodId"
                        :totalSaved="$totalSaved"
                        :loading="$loading"
                        :requiresShipping="$requiresShipping"
                    />
                </div>
            </div>
        @else
            {{-- Success Modal Overlay --}}
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-xl max-w-md w-full p-6 text-center">
                    {{-- Success Icon --}}
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    
                    {{-- Success Message --}}
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Order Confirmed!</h2>
                    <p class="text-gray-600 mb-4">Thank you for your purchase. Your order has been successfully placed.</p>
                    
                    {{-- Order Details --}}
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <div class="text-sm text-gray-600 mb-1">Order Number</div>
                        <div class="font-semibold text-gray-900">{{ $orderNumber }}</div>
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="space-y-3">
                        <a href="{{ route('shop.thank-you', ['orderNumber' => $orderNumber]) }}" 
                           class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                            View Order Details
                        </a>
                        <a href="{{ route('shop.home') }}" 
                           class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors duration-200">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>