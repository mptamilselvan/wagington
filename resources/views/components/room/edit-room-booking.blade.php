<div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm">
    <div class="px-6 py-6">
        <!-- Error Messages -->
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-red-800 mb-2">Please fix the following errors:</h3>
                        <ul class="list-disc list-inside text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @error('update')
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-red-800 font-medium">{{ $message }}</span>
                </div>
            </div>
        @enderror

        <!-- Customer Dropdown -->
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Customer*</label>
                <select wire:model.live="customer_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select Customer</option>
                    @foreach ($customers as $customer)
                        @if ($customer && isset($customer->name) && isset($customer->email))
                            <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                        @endif
                    @endforeach
                </select>
                @error('customer_id')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <!-- Species Dropdown -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Species*</label>
                <select wire:model.live="species_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select Species</option>
                    @foreach ($species as $specie)
                        @if ($specie && isset($specie->name))
                            <option value="{{ $specie->id }}">{{ $specie->name }}</option>
                        @endif
                    @endforeach
                </select>
                @error('species_id')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Pets and Service Addons Dropdowns (Multiple) - Same Row -->
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <!-- Pets Dropdown (Multiple) -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Pets*</label>
                @if ($customer_id)
                    <select wire:model="selectedPetId" wire:change="addPet($event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Pet to Add</option>
                        @foreach ($pets as $pet)
                            @if ($pet && isset($pet->id) && isset($pet->name) && !in_array($pet->id, $selectedPetIds))
                                <option value="{{ $pet->id }}">{{ $pet->name }}</option>
                            @endif
                        @endforeach
                    </select>
                @else
                    <p class="text-sm text-gray-500">Please select a customer first</p>
                @endif
                @error('pets_reserved')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror

                <!-- Selected Pets Display -->
                @if (!empty($selectedPetIds) && count($selectedPetIds) > 0 && !empty($pets))
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($selectedPetIds as $index => $petId)
                            @php
                                $pet = $pets ? $pets->firstWhere('id', $petId) : null;
                            @endphp
                            @if ($pet && isset($pet->name))
                                <div
                                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 text-blue-800 rounded-lg border border-blue-200">
                                    <span class="text-sm font-medium">{{ $pet->name }}</span>
                                    <button type="button" wire:click="removePet({{ $index }})"
                                        class="text-blue-600 hover:text-blue-800 focus:outline-none" title="Remove pet">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Service Addons Dropdown (Multiple) -->
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Service Addons</label>
                <select wire:model="selectedServiceAddonId" wire:change="addServiceAddon($event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select Service Addon to Add</option>
                    @foreach ($serviceAddons as $addon)
                        @if ($addon && isset($addon->id) && isset($addon->title) && !in_array($addon->id, $selectedServiceAddonIds))
                            <option value="{{ $addon->id }}">{{ $addon->title }} -
                                S${{ number_format($addon->price ?? 0, 2) }}</option>
                        @endif
                    @endforeach
                </select>
                @error('service_addons')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror

                <!-- Selected Service Addons Display -->
                @if (!empty($selectedServiceAddonIds) && count($selectedServiceAddonIds) > 0 && !empty($serviceAddons))
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($selectedServiceAddonIds as $index => $addonId)
                            @php
                                $addon = $serviceAddons ? $serviceAddons->firstWhere('id', $addonId) : null;
                            @endphp
                            @if ($addon && isset($addon->title))
                                <div
                                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-100 text-green-800 rounded-lg border border-green-200">
                                    <span class="text-sm font-medium">{{ $addon->title }} -
                                        S${{ number_format($addon->price ?? 0, 2) }}</span>
                                    <button type="button" wire:click="removeServiceAddon({{ $index }})"
                                        class="text-green-600 hover:text-green-800 focus:outline-none"
                                        title="Remove addon">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Start Date*</label>
                <input type="date" wire:model="check_in_date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('check_in_date')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">End Date*</label>
                <input type="date" wire:model="check_out_date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('check_out_date')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Payment Status and Booking Status -->
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Payment Status*</label>
                <select wire:model="payment_status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select Payment Status</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
                @error('payment_status')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Booking Status*</label>
                <select wire:model="booking_status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select Booking Status</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="completed">Completed</option>
                </select>
                @error('booking_status')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Room Type and Room Price Option -->
        <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Room Type*</label>
                <select wire:model.live="room_type_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Select Room Type</option>
                    @foreach ($roomTypes as $roomType)
                        @if ($roomType && isset($roomType->name))
                            <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
                        @endif
                    @endforeach
                </select>
                @error('room_type_id')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block mb-2 text-sm font-normal text-gray-700">Room Price Option (No. of Days: 1)*</label>
                @if ($room_type_id && count($roomPriceOptions) > 0)
                    <select wire:model.live="selectedRoomPriceOptionId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Price Option</option>
                        @foreach ($roomPriceOptions as $priceOption)
                            <option value="{{ $priceOption->id }}">{{ $priceOption->label }} -
                                S${{ number_format($priceOption->price, 2) }}</option>
                        @endforeach
                    </select>
                @elseif($room_type_id)
                    <p class="text-sm text-gray-500">No price options available for this room type (no_of_days = 1)</p>
                @else
                    <p class="text-sm text-gray-500">Please select a room type first</p>
                @endif
                @error('room_price')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
            <button type="button" wire:click="closeForm"
                class="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            <button type="button" wire:click="updateRoomBooking" wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <span wire:loading.remove wire:target="updateRoomBooking">
                    Update Room Booking
                </span>
                <span wire:loading wire:target="updateRoomBooking" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Updating...
                </span>
            </button>
        </div>
    </div>
</div>
