<div class="min-h-screen bg-gray-50 lg:ml-72">
    @if ($showForm)
        <!-- Add/Edit Room Booking Form - Full Page -->
        <div class="min-h-screen bg-gray-50">
            <!-- Header Container -->
            <div class="px-4 sm:px-6 py-2 mt-3">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-4 py-2 mt-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <button wire:click="closeForm"
                                    class="mr-3 p-1 rounded-full hover:bg-gray-100 transition-colors duration-200"
                                    title="Back to room booking list">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <div>
                                    <h1 class="text-xl font-semibold text-gray-900">
                                        {{ $editingRoomBooking ? 'Update Room Booking' : 'Add Room Booking' }}
                                    </h1>
                                    @if ($editingRoomBooking)
                                        <p class="text-sm text-gray-500 mt-1">Editing: {{ $editingRoomBooking->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Booking Form Content -->
            <div class="px-4 sm:px-6 pb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-6">
                        {{-- Add/Edit Room Booking Form --}}
                        @if ($editingRoomBooking)
                            @include('components.room.edit-room-booking')
                        @else
                            @include('components.room.add-room-booking')
                        @endif


                        <!-- Session Messages -->
                        <!--@if (session()->has('message'))
<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4 text-green-800">
                                {{ session('message') }}
                            </div>
@endif-->
                        @if (session()->has('warning'))
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4 text-yellow-800">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    {{ session('warning') }}
                                </div>
                            </div>
                        @endif
                        @if (session()->has('error'))
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4 text-red-800">
                                {{ session('error') }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Room Booking List Page -->
        @include('components.room.room-booking-list')
    @endif
</div>
