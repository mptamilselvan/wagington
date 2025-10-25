<div x-data="{ 
    showSuccessMessage: {{ session()->has('message') ? 'true' : 'false' }},
    showErrorMessage: {{ session()->has('error') ? 'true' : 'false' }},
    successProgress: 100,
    errorProgress: 100,
    init() {
        // Auto-hide success message after 4 seconds with progress bar
        if (this.showSuccessMessage) {
            let startTime = Date.now();
            let duration = 4000;
            
            const updateProgress = () => {
                let elapsed = Date.now() - startTime;
                let remaining = Math.max(0, duration - elapsed);
                this.successProgress = (remaining / duration) * 100;
                
                if (remaining > 0) {
                    requestAnimationFrame(updateProgress);
                } else {
                    this.showSuccessMessage = false;
                }
            };
            requestAnimationFrame(updateProgress);
        }
        
        // Auto-hide error message after 6 seconds with progress bar
        if (this.showErrorMessage) {
            let startTime = Date.now();
            let duration = 6000;
            
            const updateProgress = () => {
                let elapsed = Date.now() - startTime;
                let remaining = Math.max(0, duration - elapsed);
                this.errorProgress = (remaining / duration) * 100;
                
                if (remaining > 0) {
                    requestAnimationFrame(updateProgress);
                } else {
                    this.showErrorMessage = false;
                }
            };
            requestAnimationFrame(updateProgress);
        }
    }
}">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div x-show="showSuccessMessage" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform translate-y-2"
             class="relative p-4 mb-6 overflow-hidden border border-green-200 bg-green-50 rounded-xl">
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-green-800" style="font-family: 'Rubik', sans-serif;">{{ session('message') }}</p>
                </div>
                <button @click="showSuccessMessage = false" class="ml-4 text-green-500 hover:text-green-700">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
            <!-- Progress bar -->
            <div class="absolute bottom-0 left-0 h-1 transition-all duration-100 ease-linear bg-green-400" 
                 :style="`width: ${successProgress}%`"></div>
        </div>
    @endif

    @if (session()->has('error'))
        <div x-show="showErrorMessage"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform translate-y-2"
             class="relative p-4 mb-6 overflow-hidden border border-red-200 bg-red-50 rounded-xl">
            <div class="relative z-10 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-red-800" style="font-family: 'Rubik', sans-serif;">{{ session('error') }}</p>
                </div>
                <button @click="showErrorMessage = false" class="ml-4 text-red-500 hover:text-red-700">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
            <!-- Progress bar -->
            <div class="absolute bottom-0 left-0 h-1 transition-all duration-100 ease-linear bg-red-400" 
                 :style="`width: ${errorProgress}%`"></div>
        </div>
    @endif

    {{-- Payment Methods List or Empty State --}}
    @if(count($paymentMethods) > 0)
        <div class="grid grid-cols-1 gap-6 mb-8 lg:grid-cols-2">
            @foreach($paymentMethods as $method)
                <div class="p-6 transition-colors bg-white border border-gray-200 rounded-xl hover:border-gray-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            {{-- Mastercard Icon --}}
                            {{-- Inline SVG by card brand --}}
                            <div class="flex items-center justify-center w-10 h-6">
    @switch(strtolower($method['brand'] ?? 'default'))
        @case('visa')
            {{-- VISA --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 32" class="w-auto h-6">
                <rect width="48" height="32" rx="4" fill="#1434CB"/>
                <text x="24" y="21" text-anchor="middle"
                    font-size="14" font-family="Arial, sans-serif"
                    font-weight="bold" fill="#ffffff">VISA</text>
            </svg>
            @break

        @case('mastercard')
            {{-- Mastercard --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 32" class="w-auto h-6">
                <rect width="48" height="32" rx="4" fill="#000000"/>
                <circle cx="18" cy="16" r="8" fill="#EB001B"/>
                <circle cx="30" cy="16" r="8" fill="#F79E1B"/>
                <path d="M24 9.6c-1.8 1.4-3 3.6-3 6.4s1.2 5 3 6.4c1.8-1.4 3-3.6 3-6.4s-1.2-5-3-6.4z" fill="#FF5F00"/>
            </svg>
            @break

        @case('amex')
            {{-- AMEX --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 32" class="w-auto h-6">
                <rect width="48" height="32" rx="4" fill="#006FCF"/>
                <text x="24" y="20" text-anchor="middle"
                    font-size="9" font-family="Arial, sans-serif"
                    font-weight="bold" fill="#ffffff" letter-spacing="0.5">AMEX</text>
            </svg>
            @break

        @case('discover')
            {{-- Discover --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 32" class="w-auto h-6">
                <rect width="48" height="32" rx="4" fill="#FF6000"/>
                <circle cx="40" cy="16" r="8" fill="#FFB200"/>
                <text x="14" y="20" text-anchor="middle"
                    font-size="8" font-family="Arial, sans-serif"
                    font-weight="bold" fill="#000000">DISCOVER</text>
            </svg>
            @break

        @default
            {{-- Default (unknown card) --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 32" class="w-auto h-6">
                <rect width="48" height="32" rx="4" fill="#E5E7EB" stroke="#D1D5DB" stroke-width="1"/>
                <rect x="6" y="10" width="36" height="3" rx="1" fill="#9CA3AF"/>
                <rect x="6" y="18" width="18" height="2" rx="1" fill="#6B7280"/>
                <rect x="26" y="18" width="16" height="2" rx="1" fill="#6B7280"/>
                <rect x="6" y="23" width="12" height="2" rx="1" fill="#6B7280"/>
            </svg>
    @endswitch
</div>

                            
                            {{-- Card Details --}}
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium text-gray-900" style="font-family: 'Rubik', sans-serif;">{{ $method['brand'] }} {{ $method['last4'] }}</span>
                                    @if($method['is_default'] ?? false)
                                        <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full" style="font-family: 'Rubik', sans-serif;">Primary</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Delete Button (hide for primary and when only one card exists) --}}
                        @if(!(($method['is_default'] ?? false)) && count($paymentMethods) > 1)
                        <button 
                            wire:click="openDeleteModal('{{ $method['id'] }}')"
                            class="text-gray-400 transition-colors hover:text-red-500"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                        @endif
                    </div>

                    {{-- Set as Primary Toggle --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700" style="font-family: 'Rubik', sans-serif;">Set as primary card</span>
                        <button 
                            wire:click="openSetDefaultModal('{{ $method['id'] }}')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none 0 focus:ring-offset-2 {{ ($method['is_default'] ?? false) ? 'bg-[#1B85F3]' : 'bg-gray-200' }}"
                        >
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ ($method['is_default'] ?? false) ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="py-20 text-center">
            <div class="flex items-center justify-center w-40 h-40 mx-auto mb-6">
                <img src="{{ asset('images/card.png') }}" alt="No cards" class="object-contain w-full h-full">
            </div>
            <p class="mb-8 text-lg text-gray-500" style="font-family: 'Rubik', sans-serif;">No cards added yet, add your card details now.</p>
        </div>
    @endif

    
        {{-- Bottom Section --}}
     <div class="flex items-center justify-between pt-4 pb-4 pl-8 pr-8 bg-white border-t border-gray-100 rounded-2xl">
        @if(count($paymentMethods) < 1) <p class="text-gray-500" style="font-family: 'Rubik', sans-serif;">Go ahead and add
            your card details.</p>
            @else
            <p class="text-gray-500" style="font-family: 'Rubik', sans-serif;">You can go ahead and add your card details.
            </p>
            @endif
            <a href="{{ route('customer.payment-methods.add') }}">
                <button type="button"
                    class="button-primary bg-[#1B85F3] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] ">
                    Add now
                </button>
            </a>
    
    </div>
    

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

                <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white shadow-xl rounded-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="px-6 pt-6 pb-4 bg-white">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-medium text-gray-900" style="font-family: 'Rubik', sans-serif;">Delete Card</h3>
                            <button wire:click="closeDeleteModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mb-6">
                            <p class="mt-2 text-sm text-gray-600" style="font-family: 'Rubik', sans-serif;">Are you sure you want to delete this card?</p>
                        </div>

                        <div class="flex justify-end pt-5 space-x-3">
                            <button 
                                wire:click="closeDeleteModal"
                                wire:loading.attr="disabled"
                                wire:target="deletePaymentMethod"
                                class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px] "
                                style="font-family: 'Rubik', sans-serif;"
                            >
                               <span>  Cancel </span>
                            </button>
                            <button 
                                wire:click="deletePaymentMethod"
                                wire:loading.attr="disabled"
                                wire:target="deletePaymentMethod"
                                class="button-primary bg-[#1B85F3] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]"
                                style="font-family: 'Rubik', sans-serif;"
                            >
                                <span wire:loading.remove wire:target="deletePaymentMethod">Delete</span>
                                <span wire:loading wire:target="deletePaymentMethod" class="inline-flex items-center justify-center gap-2">
                                    <svg class="flex-shrink-0 w-4 h-4 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="flex-shrink-0">Deleting...</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Set Default Confirmation Modal --}}
    @if($showSetDefaultModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

                <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white shadow-xl rounded-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="px-6 pt-6 pb-4 bg-white">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-medium text-gray-900" style="font-family: 'Rubik', sans-serif;">Set as primary card</h3>
                            <button wire:click="closeSetDefaultModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mb-6">
                            <p class="mt-2 text-sm text-gray-600" style="font-family: 'Rubik', sans-serif;">Are you sure you want to set this card as your primary payment method?</p>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button 
                                wire:click="closeSetDefaultModal"
                                wire:loading.attr="disabled"
                                wire:target="confirmSetDefault"
                               class="bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] text-gray-700 hover:bg-gray-200"
                                style="font-family: 'Rubik', sans-serif;"
                            >
                                Cancel
                            </button>
                            <button 
                                wire:click="confirmSetDefault"
                                wire:loading.attr="disabled"
                                wire:target="confirmSetDefault"
                                class="bg-[#1B85F3] text-white rounded-[8px] h-[36px] w-[123px] text-[13px] hover:bg-blue-600"
                                style="font-family: 'Rubik', sans-serif;"
                            >
                                <span wire:loading.remove wire:target="confirmSetDefault">Confirm</span>
                                <span wire:loading wire:target="confirmSetDefault" class="flex items-center">
                                    {{-- <svg class="w-4 h-4 mr-2 -ml-1 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg> --}}
                                    Updating...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>