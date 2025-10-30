<div class="min-h-screen bg-gray-50 lg:ml-72" 
     x-data="{}"
     x-init="
        $wire.on('room-created', () => {
            setTimeout(() => {
                $wire.call('switchToListView');
            }, 2000);
        });
     ">
    @if ($showForm)
        <!-- Add/Edit Room Form - Full Page -->
        <div class="min-h-screen bg-gray-50">
            <!-- Header Container -->
            <div class="px-4 sm:px-6 py-2 mt-3">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-4 py-2 mt-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <button wire:click="closeForm" 
                                        class="mr-3 p-1 rounded-full hover:bg-gray-100 transition-colors duration-200"
                                        title="Back to room list">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <div>
                                    <h1 class="text-xl font-semibold text-gray-900">
                                        {{ $editingRoom ? 'Update Room' : 'Add Room' }}
                                    </h1>
                                    @if($editingRoom)
                                        <p class="text-sm text-gray-500 mt-1">Editing: {{ $editingRoom->name }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Form Content -->
            <div class="px-4 sm:px-6 pb-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-6">
                        {{-- Add/Edit Room Form --}}
                        @if($editingRoom)
                            @include('components.room.edit-room', ['rooms' => $rooms, 'roomTypes' => $roomTypes])
                        @else
                            @include('components.room.add-room', ['rooms' => $rooms, 'roomTypes' => $roomTypes])
                        @endif
                        
                        
                        <!-- Session Messages -->
                        @if (session()->has('message'))
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4 text-green-800">
                                {{ session('message') }}
                            </div>
                        @endif
                        @if (session()->has('warning'))
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4 text-yellow-800">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
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

                        <!-- Validation Errors Summary intentionally removed to show only inline field errors -->

                        <!-- Form Actions -->
                        <!--<div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <button 
                                wire:click="closeForm"
                                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors"
                            >
                                Cancel
                            </button>
                            <button 
                                wire:click="saveRoom"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                            >
                                <span wire:loading.remove wire:target="saveRoom">
                                    {{ $editingRoom ? 'Update' : 'Save' }}
                                </span>
                                <span wire:loading wire:target="saveRoom" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving Room...
                                </span>
                            </button>
                        </div>-->

                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Room List Page -->
        @include('components.room.room-list') 
    @endif

    
</div>