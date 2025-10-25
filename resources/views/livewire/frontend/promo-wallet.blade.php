<div class="relative min-h-screen bg-white">
    <div class="w-full px-4 py-8 sm:px-6 lg:px-8 ">
        {{-- Header --}}
        <div class="p-6 border border-gray-200 rounded-lg shadow-sm bg-gray-50 sm:p-8">
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-start mb-3">
                    <a href="{{ route('home') }}" class="flex items-center mt-1 mr-3 text-gray-600 transition-colors hover:text-gray-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 sm:text-xl">Promotions</h1>
                        <p class="mt-2 text-gray-500">Enjoy exciting discounts on your bookings.</p>
                        {{-- <p class="mt-2 text-base text-gray-600">Add or edit your profile information</p> --}}
                    </div>
                </div>

                <!-- Right Section -->
                <div>
                    <button wire:click="openModal"
                            class="px-5 py-2 font-medium text-white transition rounded-lg shadow-md bg-primary-blue hover:bg-blue-600">
                        + Add Promotion
                    </button>
                </div>
            </div>

            @component('components.alert-component')@endcomponent

            

                <!-- Tabs -->
                <div class="flex mt-4 mb-4 space-x-10 border-b">
                    <button wire:click="setTab('active')" 
                            class="pb-2 border-b-2 {{ $tab === 'active' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500' }}">
                        Active
                    </button>
                    <button wire:click="setTab('past')" 
                            class="pb-2 border-b-2 {{ $tab === 'past' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500' }}">
                        Past
                    </button>
                    
                </div>

                <!-- Add Promotion Button -->
                

                <!-- Promotion Cards -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @forelse($userPromotions as $promo)
                        <div class="relative p-6 overflow-hidden bg-white border rounded-lg shadow" wire:click='openModel({{ $promo->id }})'>

                            <!-- Left colored border -->
                            <div class="absolute top-0 left-0 h-full w-2 @if($promo->voucher_type == "marketing_campaign") bg-primary-ora @else bg-[#1B85F3] @endif rounded-l-lg"></div>

                            <div class="flex items-center justify-between gap-1">
                                <div class="flex items-center">
                                    <!-- Icon -->
                                    <div class="flex-shrink-0 @if($promo->voucher_type == "marketing_campaign") bg-secondry-ora text-orange-500 @else bg-[#1B85F31F] text-orange-500 @endif rounded-full p-3 mr-4">
                                        @if($promo->voucher_type == "marketing_campaign")
                                            <x-icons.promotion class="w-6 h-6" />
                                        @else
                                            <x-icons.gift />
                                        @endif
                                    </div>
                                    <h3 class="text-base font-semibold text-gray-700">{{ ($promo->voucher_type != "marketing_campaign")?$promo->promotion->name :$promo->voucher_code }}</h3>
                                </div>
                                @if($promo->valid_till < today())
                                    <!-- Expired Badge -->
                                    @component('components.badge', ['type' => 'expired'])@endcomponent
                                @endif
                            
                            </div>
                            
                            <br>
                            <!-- Promo Details -->
                            {{-- <div class="flex items-center"> --}}
                                {{-- <div class="flex items-center justify-between">
                                    
                                </div> --}}
                                <p class="mt-1 ml-2 text-sm text-gray-600 truncate">
                                    {{ $promo->promotion->description }}
                                </p>

                                <div class="flex items-center justify-between mt-3 ml-2 text-sm">
                                    <span class="text-gray-700">
                                        Valid until 
                                        <b>{{ \Carbon\Carbon::parse($promo->valid_till)->format('d F Y') }}</b>
                                    </span>
                                    <span class="font-medium text-gray-800">
                                        Used : {{ $promo->usage_count ?? 0 }}/{{ $promo->max_usage ?? 5 }}
                                    </span>
                                </div>
                            {{-- </div> --}}
                        </div>
                        
                    @empty
                        <p class="col-span-3 text-gray-500">No promotions available.</p>
                    @endforelse
                </div>

                <!-- Add Promo Modal -->
                @if($showModal)
                    <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
                        <div class="p-6 bg-white rounded-lg shadow-lg w-96">
                            <h2 class="mb-4 text-lg font-semibold">Add Promotion</h2>

                            <input type="text" wire:model="promoCode" placeholder="Enter Promo Code"
                                class="w-full px-3 py-2 mb-4 border rounded">

                            @error('promoCode') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

                            <div class="flex justify-end mt-5 space-x-2">
                                @component('components.button-component', [
                                    'label' => 'Clear',
                                    'id' => 'clear',
                                    'type' => 'cancelSmall',
                                    'wireClickFn' => '$set("showModal", false)',
                                ])
                                @endcomponent

                                @component('components.button-component', [
                                'label' => 'Save',
                                'id' => 'save',
                                'type' => 'submitSmall',
                                'wireClickFn' => 'applyPromo',
                                ])
                                @endcomponent
                            </div>
                        </div>
                    </div>
                @endif

                @if($isOpen == true)
                    <div class="fixed inset-0 z-40 transition-opacity duration-300 ease-in-out bg-black bg-opacity-50"
                    onclick="Livewire.emit('closeModal')"></div>

                    <div class="fixed right-0 top-0 h-full w-full sm:w-[480px] md:w-[480px] lg:w-[480px] bg-neutral-100 shadow-xl z-50 flex flex-col rounded-l-3xl transform transition-transform duration-300 ease-in-out" style="transform: translateX(0);" onclick="event.stopPropagation()">
                        <!-- Header Image / Banner -->
                        <div class="relative flex-shrink-0 h-16 overflow-hidden rounded-tl-3xl">
                            <button wire:click="closeModal" class="absolute z-50 p-2 text-gray-700 transition-all bg-white rounded-full cursor-pointer top-4 right-4 hover:text-gray-900 bg-opacity-80 hover:bg-opacity-100" type="button" style="pointer-events: auto !important;" onclick="event.stopPropagation();">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                            {{-- <div class="flex items-center justify-center w-full h-full overflow-hidden bg-gradient-to-b from-blue-50 to-white">
                                <img src="http://127.0.0.1:8000/images/promo-banner.png" alt="Promo Banner" class="object-cover w-full h-full">
                            </div> --}}
                        </div>

                        <!-- Body -->
                        <div class="flex-1 px-4 pb-6 overflow-y-auto sm:px-10">
                            <h2 class="mt-6 mb-12 text-lg font-medium text-gray-700">Promo Details</h2>
                            {{-- <p class="mb-6 text-sm text-gray-600">Get exciting discounts by using this promotion code before it expires.</p> --}}

                            <!-- Promo Title -->
                            <h3 class="text-base font-medium text-gray-700">{{ $voucherDetail->promotion->name }}</h3>
                            <p class="mt-2 mb-6 text-sm leading-relaxed text-gray-600">
                                {{ $voucherDetail->promotion->description }}
                            </p>
                            @if($voucherDetail->promotion->promotion == 'referralpromotion')
                                <p class="mt-2 text-sm leading-relaxed text-gray-600">
                                    <b>Referrer Reward : </b>{{ $voucherDetail->promotion->referralPromotion->referrer_reward. ($voucherDetail->promotion->referralPromotion->discount_type == 'percentage'?'%':'$') }}<br>
                                    <b>Referee Reward : </b>{{ $voucherDetail->promotion->referralPromotion->referee_reward. ($voucherDetail->promotion->referralPromotion->discount_type == 'percentage'?'%':'$') }}
                                </p>
                            @elseif($voucherDetail->promotion->promotion == 'marketingcampaign')
                                <p class="mt-2 text-sm leading-relaxed text-gray-600">
                                    <b>Discount Value : </b>{{ $voucherDetail->promotion->marketingCampaign->discount_value. ($voucherDetail->promotion->marketingCampaign->discount_type == 'percentage'?'%':'$') }}
                                </p>
                            @endif

                            <!-- Validity -->
                            <p class="mt-4 mb-4 text-sm font-normal text-gray-700">
                                Valid until <span class="font-semibold">{{ $voucherDetail->valid_till->format('d F Y') }}</span>
                            </p>

                            <!-- Voucher Code -->
                            @component('components.forms.inputWithCopy', [
                                'id' => 'voucherCodeLink',
                                'onClickFn' => 'copyVoucherCode()',
                                'value' => $voucherDetail->voucher_code,
                                'classInput' => 'h-[32px] md:h-[38px] truncate pr-10 w-[255px] text-[#374151] form-input text-[12px]
                                                md:text-[13px] w-full',
                                'classSvg' => 'w-[16px] h-[16px] md:w-[24px] md:h-[24px]',
                                'readonly' => true
                            ])
                            @endcomponent
                            <p id="copyMessage" class="hidden mt-1 text-sm text-green-600">Copied!</p>
                            {{-- <div class="flex items-center justify-between px-4 py-3 mt-4 border rounded-lg bg-gray-50">
                            
                                <span class="font-semibold text-gray-800">{{ $voucherDetail->voucher_code }}</span>
                                <button class="text-blue-500 hover:text-blue-600" onClick="copyVoucherCode()">
                                    <svg xmlns="http://www.w3.org/2000/svg" 
                                        class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            d="M8 16h8M8 12h8m-6-8h6a2 2 0 012 2v12a2 2 0 01-2 2H10a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                                    </svg>
                                </button>
                            </div> --}}

                            <!-- Terms & Conditions -->
                            <div class="mt-6">
                                <h4 class="mb-2 text-base font-medium text-gray-700">Terms and Conditions</h4>
                                <ul class="pl-5 space-y-2 text-sm text-gray-600 list-disc">
                                    <li>{{ $voucherDetail->promotion->terms_and_conditions }}</li>
                                </ul>
                            </div>

                            <!-- CTA Button -->
                            <div class="mt-8">
                                @component('components.button-component', [
                                    'label' => 'Use Promo',
                                    'id' => 'use_promo',
                                    'type' => 'button',
                                ])
                                @endcomponent
                                {{-- <button 
                                    class="w-full bg-blue-500 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-600 transition"
                                    wire:click="applyPromo"
                                >
                                    Use Promo
                                </button> --}}
                            </div>
                        </div>
                    </div>
                @endif



        </div>
    </div>
    <script>
        function copyVoucherCode() {
            let copyText = document.getElementById("voucherCodeLink");
            // Select the text
            copyText.select();
            copyText.setSelectionRange(0, 99999); // for mobile

            // Copy to clipboard
            navigator.clipboard.writeText(copyText.value).then(() => {
                // Show confirmation
                let msg = document.getElementById("copyMessage");
                msg.classList.remove("hidden");
                setTimeout(() => msg.classList.add("hidden"), 2000);
            }).catch(err => {
                alert("Failed to copy: " + err);
            });
        }
    </script>
</div>