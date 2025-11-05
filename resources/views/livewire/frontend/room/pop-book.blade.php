<div x-data="{ open: false }"
    x-on:open-booking.window="open=true; $wire.refreshBooking(); if($event.detail.roomId){ $wire.setRoom($event.detail.roomId); } if($event.detail.roomTypeId){ $wire.room_type_id = $event.detail.roomTypeId; }"
    x-init="(() => {
        // Check URL parameter for auto-open (from alternative room type switch)
        const urlParams = new URLSearchParams(window.location.search);
        const shouldOpen = urlParams.get('open_booking') === '1';
        // Also check if autoOpenModal flag is set from server
        const hasAutoOpenFlag = @js($autoOpenModal);
        if (shouldOpen || hasAutoOpenFlag) {
            $nextTick(() => {
                open = true;
                $wire.refreshBooking();
                // Auto-check availability after modal opens and data is restored
                // Use a longer delay to ensure all Livewire data is synced
                setTimeout(() => {
                    // Check if we have the required data before checking availability
                    if ($wire.start_date && $wire.end_date && $wire.selectedPets && $wire.selectedPets.length >
                        0 && $wire.room_type_id) {
                        $wire.checkAvailability();
                    }
                }, 1000);
                // Clean URL by removing the query parameter
                if (shouldOpen) {
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, newUrl);
                }
            });
        }
    })()" x-cloak>
    <!-- Backdrop -->
    <div x-show="open" class="fixed inset-0 z-[60] bg-black/40" @click="open=false"></div>

    <!-- Panel -->
    <div x-show="open"
        class="fixed right-0 top-0 bottom-0 z-[61] w-full max-w-xl bg-white shadow-xl flex flex-col p-[20px] overflow-hidden h-full">
        <div class="flex items-center justify-between p-4 bg-white">
            <h2 class="text-lg font-semibold">Booking</h2>
            <button @click="open=false" class="text-gray-500">✕</button>
        </div>

        <!-- removed redundant wrapper -->

        <!-- Scrollable content wrapper start -->
        <div class="flex-1 overflow-y-auto mb-4">

            <!-- Pet Selection Section -->
            <div class="px-4 pb-4 border-b border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Select pet profile.</h3>
                    <div class="flex items-center gap-3">
                        @if (count($selectedPets) > 0)
                            <div class="bg-yellow-100 text-orange-600 px-3 py-1 rounded-full text-sm font-medium">
                                {{ count($selectedPets) }} pet{{ count($selectedPets) > 1 ? 's' : '' }} selected
                            </div>
                        @endif
                        <div class="flex gap-1">
                            <button wire:click="previousPage" @disabled($currentPage <= 0)
                                class="w-8 h-8 rounded-lg border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                ‹
                            </button>
                            <button wire:click="nextPage" @disabled(!$hasMorePets)
                                class="w-8 h-8 rounded-lg border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                ›
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pet Cards -->
                <div class="flex gap-4 overflow-x-auto pb-2 justify-center">
                    @forelse($pets as $pet)
                        <div wire:click="togglePetSelection({{ $pet->id }})"
                            class="flex-shrink-0 w-32 cursor-pointer transition-all duration-200 hover:scale-105">
                            <div
                                class="bg-white rounded-xl border-2 {{ in_array($pet->id, $selectedPets) ? 'border-blue-500' : 'border-gray-200' }} p-3 shadow-sm hover:shadow-md">
                                <!-- Pet Image -->
                                <div
                                    class="w-20 h-20 mx-auto mb-3 rounded-full overflow-hidden border-2 border-gray-200">
                                    @if ($pet->profile_image)
                                        <img src="{{ getFullUrl($pet->profile_image) }}" alt="{{ $pet->name }}"
                                            class="w-full h-full object-cover" />
                                    @else
                                        <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <!-- Pet Name -->
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium {{ in_array($pet->id, $selectedPets) ? 'text-blue-600' : 'text-gray-900' }}">
                                        {{ $pet->name }}
                                    </span>
                                    <div class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-sm text-gray-500 w-full">
                            No pets found with sterilisation status.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Add-ons Section (moved here after Pet Selection) -->
            <div class="bg-white px-4 pt-4 pb-1 space-y-4 border-b border-gray-100">
                <!-- Add-ons selector -->
                <div>
                    <div class="text-xl font-semibold text-gray-900 mb-2">Choose add-ons</div>
                    <div class="relative">
                        <select
                            class="w-full appearance-none border border-gray-300 rounded-2xl px-5 py-4 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-12"
                            wire:change="selectAddon($event.target.value)">
                            <option value="">Choose add-ons</option>
                            @foreach ($addons as $ad)
                                <option value="{{ $ad['id'] }}">{{ $ad['name'] }} — S$
                                    {{ number_format($ad['total_price'] ?? 0, 2) }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">▾</div>
                    </div>
                </div>

                <!-- Selected add-ons list -->
                @if (count($selectedAddons) > 0)
                    <div class="space-y-3">
                        @foreach ($selectedAddons as $sel)
                            <div
                                class="flex items-center justify-between bg-blue-50 text-gray-800 rounded-2xl px-5 py-4">
                                <div class="text-lg">{{ $sel['name'] }}</div>
                                <div class="flex items-center gap-3">
                                    <button
                                        class="w-9 h-9 rounded-xl border border-gray-300 flex items-center justify-center text-gray-700 bg-white"
                                        wire:click="decrementAddon({{ $sel['id'] }})">−</button>
                                    <div class="w-8 text-center font-medium">{{ $sel['qty'] }}</div>
                                    <button
                                        class="w-9 h-9 rounded-xl border border-gray-300 flex items-center justify-center text-gray-700 bg-white"
                                        wire:click="incrementAddon({{ $sel['id'] }})">＋</button>
                                    <div class="w-24 text-right font-semibold">S$
                                        {{ number_format($sel['price'] ?? 0, 2) }}</div>
                                    <button class="ml-2 text-gray-400 hover:text-gray-600"
                                        wire:click="removeAddon({{ $sel['id'] }})" aria-label="Remove">✕</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Total amount summary -->
                <div class="bg-gray-50 border border-gray-200 rounded-2xl px-6 py-4 flex items-center justify-between">
                    <div class="text-lg font-semibold text-gray-800">Total Add Ons Amount : S$
                        {{ number_format($this->addonsTotal, 2) }}</div>
                    <div
                        class="w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600">
                        ▾</div>
                </div>
            </div>

            <!-- Date Selection Section -->
            <div class="px-4 pt-4 pb-4 border-b border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>

                        <div class="flex items-center justify-between">
                            @component('components.date-advanced-component', [
                                'wireModel' => 'start_date',
                                'id' => 'startDate',
                                'label' => 'Start Date',
                                'star' => true,
                                'class' => 'w-full text-xl text-gray-800',
                                'error' => $errors->first('start_date'),
                                'min' => date('Y-m-d'),
                                'live' => true,
                                'peakSeasonDates' => $peakSeasonDates ?? [],
                                'offDaysDates' => $offDaysDates ?? [],
                            ])
                            @endcomponent
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            @component('components.date-advanced-component', [
                                'wireModel' => 'end_date',
                                'id' => 'endDate',
                                'label' => 'End Date',
                                'star' => true,
                                'class' => 'w-full text-xl text-gray-800',
                                'error' => $errors->first('end_date'),
                                'min' => $this->endDateMin,
                                'live' => true,
                                'peakSeasonDates' => $peakSeasonDates ?? [],
                                'offDaysDates' => $offDaysDates ?? [],
                            ])
                            @endcomponent
                        </div>

                        <!-- For Testing purpose Test date -->
                        {{-- <div class="flex items-center justify-between">
                           @component('components.date-advanced-component', [
    'wireModel' => 'test_date',
    'id' => 'testDate',
    'label' => 'Test Date',
    'star' => true,
    'class' => 'text-base text-gray-800',
    'error' => $errors->first('test_date'),
    'min' => date('Y-m-d'),
    'live' => true,
    'peakSeasonDates' => $peakSeasonDates ?? [],
    'offDaysDates' => $offDaysDates ?? [],
])
                    @endcomponent
                        </div> --}}

                    </div>
                </div>
                <div class="mt-6">
                    <button wire:click="checkAvailability" wire:loading.attr="disabled" wire:target="checkAvailability"
                        @if (!$this->canCheckAvailability) disabled @endif
                        class="w-full md:w-auto inline-flex items-center gap-2 px-6 py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow-md disabled:opacity-70 disabled:cursor-not-allowed">
                        <svg wire:loading wire:target="checkAvailability" class="h-5 w-5 animate-spin text-white"
                            viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        <span wire:loading.remove wire:target="checkAvailability">Check Availability</span>
                        <span wire:loading wire:target="checkAvailability">Checking...</span>
                    </button>
                    <div wire:loading wire:target="checkAvailability"
                        class="mt-2 text-sm text-gray-600 inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin text-gray-600" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                    </div>
                </div>
                @if ($availabilityMessage)
                    <div
                        class="mt-3 text-sm rounded-lg border px-4 py-3 flex items-start gap-2 {{ $availabilityType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' }}">
                        @if ($availabilityType === 'success')
                            <svg class="w-5 h-5 mt-0.5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4A1 1 0 014.707 9.293L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 mt-0.5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10A8 8 0 11.001 10 8 8 0 0118 10zm-8-4a1 1 0 00-.894.553L8 7v4l1.106 2.447A1 1 0 0010 14h0a1 1 0 00.894-.553L12 11V7l-1.106-2.447A1 1 0 0010 6z"
                                    clip-rule="evenodd" />
                            </svg>
                        @endif
                        <div class="flex-1">
                            <div class="font-medium">{{ $availabilityMessage }}</div>

                            @if (!empty($availabilityDetails))
                                <div class="mt-2 text-gray-700">
                                    <!--<div class="text-xs uppercase tracking-wide {{ $availabilityType === 'success' ? 'text-green-700' : 'text-red-700' }}">Summary</div>-->
                                    <!--<div class="mt-1">Available rooms: <span class="font-semibold">{{ $availabilityDetails['available_rooms'] ?? 0 }}</span></div>-->

                                    @if (!empty($availabilityDetails['pet_size_availability']))
                                        <!--<div class="mt-2">Pet size capacities:</div>-->
                                        <!--<ul class="mt-1 list-disc list-inside space-y-0.5 text-gray-800">-->
                                        @foreach ($availabilityDetails['pet_size_availability'] as $ps)
                                            <!-- <li>
                                            <span class="font-semibold">{{ $ps['pet_size_name'] ?? 'Size ID ' . ($ps['pet_size_id'] ?? '?') }}</span> —  remaining {{ $ps['remaining'] ?? 0 }} (used {{ $ps['used'] ?? 0 }} of {{ $ps['limit'] ?? 0 }})
                                       </li> -->
                                        @endforeach
                                        <!--<ul>-->
                                    @endif


                                    @if (!empty($availabilityDetails['pet_size_availability_by_room']))
                                        <!--<div class="mt-3">Per-room availability:</div>-->
                                        <div class="mt-1 space-y-2">
                                            @foreach ($availabilityDetails['pet_size_availability_by_room'] as $room)
                                                <div class="border border-gray-200 rounded-lg p-3 bg-white">
                                                    <!--<div class="text-xs text-gray-600 mb-1">Room #{{ $room['room_id'] }}</div>-->
                                                    @if (!empty($room['sizes']))
                                                        <ul class="list-disc list-inside text-sm">
                                                            @foreach ($room['sizes'] as $sz)
                                                                <!-- <li>
                                                            <span class="font-semibold">{{ $sz['pet_size_name'] ?? 'Size' }}</span> — remaining {{ $sz['remaining'] ?? 0 }} (used {{ $sz['used'] ?? 0 }} of {{ $sz['limit'] ?? 0 }})
                                                        </li> -->
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Show alternative room types if requested room is not available --}}
                @if ($availabilityType === 'error' && !empty($availabilityDetails['alternative_room_types']))
                    <div class="mt-4 rounded-2xl border border-blue-200 bg-blue-50 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-blue-200">
                            <h3 class="text-xl font-semibold text-blue-900">Alternative Room Options Available</h3>
                            <p class="text-sm text-blue-700 mt-1">The following room types are available for your
                                selected dates and pets:</p>
                        </div>
                        <div class="p-6 space-y-4">
                            @foreach ($availabilityDetails['alternative_room_types'] as $alt)
                                <div
                                    class="bg-white rounded-xl border border-blue-200 p-5 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-900">
                                                {{ $alt['room_type_name'] }}</h4>
                                            <p class="text-sm text-gray-600 mt-1">
                                                {{ $alt['available_rooms'] }}
                                                room{{ $alt['available_rooms'] > 1 ? 's' : '' }} available
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-2xl font-bold text-blue-600">
                                                ${{ number_format($alt['pricing']['total'] ?? 0, 2) }}</div>
                                            <div class="text-xs text-gray-500">for {{ $alt['pricing']['days'] ?? 1 }}
                                                day{{ ($alt['pricing']['days'] ?? 1) > 1 ? 's' : '' }}</div>
                                        </div>
                                    </div>

                                    {{-- Available Rooms --}}
                                    @if (!empty($alt['rooms']))
                                        <div class="mt-3 mb-3">
                                            <div class="text-sm font-semibold text-gray-700 mb-2">Available Rooms:
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                @foreach ($alt['rooms'] as $room)
                                                    <div
                                                        class="text-xs bg-gray-50 rounded-lg px-2 py-1 border border-gray-200">
                                                        <span class="font-medium">{{ $room['room_name'] }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Pet Size Availability --}}
                                    @if (!empty($alt['pet_size_availability']))
                                        <div class="mt-3 mb-3">
                                            <!--<div class="text-sm font-semibold text-gray-700 mb-2">Pet Size Availability:</div>-->
                                            <div class="space-y-1">
                                                @foreach ($alt['pet_size_availability'] as $psa)
                                                    <!-- <div class="flex items-center justify-between text-xs">
                                                <span class="text-gray-700">{{ $psa['pet_size_name'] }}</span>
                                                    <span class="{{ ($psa['remaining'] ?? 0) >= ($psa['needed'] ?? 0) ? 'text-green-600' : 'text-red-600' }} font-medium">
                                                        {{ $psa['remaining'] ?? 0 }}/{{ $psa['needed'] ?? 0 }} available
                                                    </span>
                                                </div> -->
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Pricing Breakdown --}}
                                    @if (!empty($alt['pricing']['pet_lines']))
                                        <div class="mt-3 mb-3 pt-3 border-t border-gray-200">
                                            <div class="text-sm font-semibold text-gray-700 mb-2">Pricing Breakdown:
                                            </div>
                                            <div class="space-y-1">
                                                @foreach ($alt['pricing']['pet_lines'] as $pl)
                                                    <div class="flex items-center justify-between text-xs">
                                                        <span class="text-gray-700">{{ $pl['pet_name'] }}
                                                            ({{ $pl['pet_size_name'] }})
                                                        </span>
                                                        <span
                                                            class="font-medium text-gray-900">${{ number_format($pl['final_price'] ?? 0, 2) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @if (($alt['pricing']['variation_total'] ?? 0) > 0)
                                                <div class="mt-2 pt-2 border-t border-gray-100 text-xs">
                                                    <div class="flex justify-between text-gray-600">
                                                        <span>Base Total:</span>
                                                        <span>${{ number_format($alt['pricing']['base_total'] ?? 0, 2) }}</span>
                                                    </div>
                                                    <div class="flex justify-between text-gray-600">
                                                        <span>Variation:</span>
                                                        <span>+
                                                            ${{ number_format($alt['pricing']['variation_total'] ?? 0, 2) }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Action Button --}}
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <button type="button"
                                            wire:click="switchToAlternativeRoomType('{{ $alt['room_type_slug'] }}', {{ $alt['room_type_id'] }})"
                                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                            Book This Room Type
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($isReadyForCart && $availabilityType === 'success' && !empty($quote))
                    <div class="mt-4 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                        <!--<div class="px-6 py-4 text-2xl font-semibold text-gray-800">Room Price Summary</div>
                    <div class="border-t border-gray-200"></div>-->
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="text-xl font-semibold text-gray-900">{{ $quote['room_name'] ?? 'Room' }}
                                </div>
                                <div class="text-xl font-semibold text-gray-900">
                                    ${{ number_format($quote['final_subtotal'] ?? 0, 2) }}</div>
                            </div>
                            <div class="grid gap-2 text-sm text-gray-800">
                                <div>Pet Quantity: <span class="font-medium">{{ $quote['pet_quantity'] ?? 0 }}</span>
                                </div>
                                <div>No. of days: <span class="font-medium">{{ $quote['days'] ?? 1 }}</span></div>
                            </div>

                            @if (!empty($quote['pet_lines']))
                                <div class="mt-2">
                                    <div class="text-sm font-semibold text-gray-700 mb-2">Per-pet breakdown</div>
                                    <div class="space-y-2">
                                        @foreach ($quote['pet_lines'] as $pl)
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-medium">{{ $pl['pet_name'] ?? 'Pet' }}
                                                        ({{ $pl['pet_size_name'] ?? 'Size' }})
                                                    </div>
                                                    <div class="text-xs text-gray-600">Base:
                                                        ${{ number_format($pl['base_price'] ?? 0, 2) }} @if (($pl['variation_percent'] ?? 0) > 0)
                                                            • Variation
                                                            {{ number_format($pl['variation_percent'], 2) }}% =
                                                            ${{ number_format($pl['variation_amount'] ?? 0, 2) }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="font-semibold">
                                                    ${{ number_format($pl['final_price'] ?? 0, 2) }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="my-4 border-t border-gray-200"></div>
                            <div>
                                <div class="text-lg font-semibold">Add-ons</div>
                                @if (!empty($quote['addon_lines']))
                                    <div class="mt-2 space-y-1">
                                        @foreach ($quote['addon_lines'] as $ad)
                                            <div class="flex items-center justify-between text-sm">
                                                <div>{{ $ad['name'] }} × {{ $ad['qty'] }}</div>
                                                <div>${{ number_format($ad['total'], 2) }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-sm text-gray-500">No add-ons selected</div>
                                @endif
                            </div>

                            <div class="my-4 border-t border-gray-200"></div>
                            <div class="space-y-1 text-sm">
                                <div class="flex items-center justify-between"><span>Room
                                        subtotal</span><span>${{ number_format($quote['base_total'] ?? 0, 2) }}</span>
                                </div>
                                @if (($quote['variation_total'] ?? 0) > 0)
                                    <div class="flex items-center justify-between"><span>Variation</span><span>+
                                            ${{ number_format($quote['variation_total'], 2) }}</span></div>
                                @endif
                                <div class="flex items-center justify-between">
                                    <span>Add-ons</span><span>${{ number_format($quote['addons_total'] ?? 0, 2) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-lg font-semibold mt-2">
                                <div>Total</div>
                                <div>${{ number_format($quote['total'] ?? 0, 2) }}</div>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($errors->has('start_date') || $errors->has('end_date'))
                    <div class="text-red-500 text-sm mt-2">
                        {{ $errors->first('start_date') }}
                    </div>
                @endif
                @if ($errors->has('end_date'))
                    <div class="text-red-500 text-sm mt-2">
                        {{ $errors->first('end_date') }}
                    </div>
                @endif
            </div>

            <!-- Agreements -->
            <div class="bg-white px-4 pt-4 pb-1 space-y-4 border-b border-gray-100">
                <div class="space-y-4 pt-1">
                    @if (is_array($aggreed_terms) && count($aggreed_terms) > 0)
                        <div class="space-y-2">
                            @foreach ($aggreed_terms as $i => $term)
                                <label class="flex items-center gap-3 text-gray-700">
                                    <input type="checkbox"
                                        class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        wire:model.live="agreeDocs.{{ $i }}">
                                    <span>
                                        @if (!empty($term['content']))
                                            {{ $term['content'] }}
                                        @endif
                                        @if (!empty($term['document_url']))
                                            — <a href="{{ $term['document_url'] }}" target="_blank"
                                                class="text-blue-600 underline">View document</a>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <div class="p-4 border-t bg-white space-y-3 z-[62] shadow-[0_-4px_10px_rgba(0,0,0,0.05)] shrink-0">
            @if ($isReadyForCart && !empty($quote))
                <!-- Final Price Summary -->
                <div class="text-sm font-semibold text-gray-900 mb-2">Final Price Summary</div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-700">Room Total</span>
                        <span class="font-medium">${{ number_format($quote['final_subtotal'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-700">Add-ons Total</span>
                        <span class="font-medium">${{ number_format($quote['addons_total'] ?? 0, 2) }}</span>
                    </div>
                </div>
                <div class="flex justify-between text-base font-semibold pt-3 mt-3 border-t border-gray-300">
                    <div>Total</div>
                    <div>${{ number_format($quote['total'] ?? 0, 2) }}</div>
                </div>
            @endif
            <div class="flex gap-3" x-data="{ showAuthPrompt: false }" x-on:show-auth-prompt.window="showAuthPrompt = true">
                @auth
                    <!--<a href="" class="flex-1 px-4 py-2 border rounded text-center">View booking</a>-->
                    <button wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart"
                        @if (!$isReadyForCart || !$this->areAllAgreementsChecked) disabled @endif
                        class="flex-1 px-4 py-2 rounded bg-blue-600 text-white text-center disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-700 inline-flex items-center justify-center gap-2">
                        <svg wire:loading wire:target="addToCart" class="h-5 w-5 animate-spin text-white"
                            viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        <span wire:loading.remove wire:target="addToCart">Add to Cart</span>
                        <span wire:loading wire:target="addToCart">Adding...</span>
                    </button>
                @else
                    <button type="button" @click="showAuthPrompt = true"
                        class="flex-1 px-4 py-2 border rounded text-center">View booking</button>
                    <button type="button" wire:click="guestProceed" @if (!$isReadyForCart || !$this->areAllAgreementsChecked) disabled @endif
                        class="flex-1 px-4 py-2 rounded bg-blue-600 text-white text-center disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-700">
                        Add to Cart
                    </button>

                    <!-- Guest Auth Prompt -->
                    <div x-cloak x-show="showAuthPrompt" class="fixed inset-0 z-[70] flex items-center justify-center">
                        <div class="absolute inset-0 bg-black/50" @click="showAuthPrompt=false"></div>
                        <div class="relative z-[71] w-[92%] max-w-md bg-white rounded-xl shadow-xl p-6">
                            <h3 class="text-lg font-semibold mb-2">Create an account or login</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                You’re not registered or logged in. To continue to booking, please create an account or
                                login.
                                Your items will remain in your booking.
                            </p>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <a href="{{ route('customer.register.form') }}"
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">Create
                                    account</a>
                                <a href="{{ route('customer.login') }}"
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-gray-300 text-gray-800 font-medium hover:bg-gray-50">Login</a>
                            </div>
                            <div class="mt-3 text-xs text-gray-500">If you choose not to register or login now, you can
                                continue browsing and come back later.</div>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</div>
</div>
</div>
