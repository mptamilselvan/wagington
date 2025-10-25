<!-- Product Deletion Modal -->
@if($showDeleteModal)
<div x-data="{ show: true }" 
     x-show="show" 
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
    
    <!-- Modal -->
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="relative w-full max-w-2xl transform rounded-lg bg-white shadow-xl transition-all">
            
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">
                       Delete Room Type
                    </h3>
                    <button wire:click="cancelDelete" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Content -->
            <div class="px-6 py-4">
                @if($roomTypeToDelete)
                    <!-- Product Info -->
                    <div class="mb-6 rounded-lg bg-gray-50 p-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                              
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900">{{ $roomTypeToDelete->name }}</h4>
                                <p class="text-sm text-gray-500">
                                    Room Type • {{ $roomTypeToDelete->species->name ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    @if($isLoadingDependencies)
                        <div class="flex items-center justify-center py-8">
                            <div class="flex items-center space-x-2">
                                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-sm text-gray-600">Checking dependencies...</span>
                            </div>
                        </div>
                    @else
                        <!-- Dependencies and Warnings -->
                        @if(!empty($deletionDependencies) || !empty($deleteWarnings))
                            <div class="mb-6 space-y-4">
                                
                                <!-- Warnings -->
                                @if(!empty($deleteWarnings))
                                    <div class="rounded-md bg-yellow-50 p-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-yellow-800">Warnings</h3>
                                                <div class="mt-2 text-sm text-yellow-700">
                                                    <ul class="list-disc pl-5 space-y-1">
                                                            <li>{{ $deleteWarnings }}</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Dependencies -->
                                @if(!empty($deletionDependencies))
                                    <div class="rounded-md bg-blue-50 p-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <h3 class="text-sm font-medium text-blue-800">Dependencies Found</h3>
                                                <div class="mt-2 text-sm text-blue-700 space-y-2">
                                                    
                                                    @if(isset($deletionDependencies['rooms']) && $deletionDependencies['rooms'] > 0)
                                                        <div class="flex items-center justify-between p-2 bg-red-50 rounded border border-red-200">
                                                            <span class="font-medium text-red-800">Rooms</span>
                                                            <span class="text-red-600">{{ count($deletionDependencies['rooms']) }}</span>
                                                        </div>
                                                        <div class="p-2 bg-gray-50 rounded border border-gray-200">
                                                            <div class="font-medium text-gray-800 mb-2">Rooms ({{ count($deletionDependencies['rooms']) }})</div>
                                                            <div class="space-y-1">
                                                                @foreach($deletionDependencies['rooms'] as $room)
                                                                    <div class="flex items-center justify-between text-xs">
                                                                        <span>{{ $room['name'] }}</span>
                                                                        <span>{{ $room['room_type'] }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                  
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Cannot Delete Message -->
                                @if(!$canDelete)
                                    <div class="rounded-md bg-red-50 p-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-red-800">Cannot Delete</h3>
                                                <p class="mt-1 text-sm text-red-700">
                                                    This room type cannot be deleted due to rooms or required dependencies. 
                                                    Please resolve these issues before attempting to delete.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                        
                        <!-- Safe to Delete Message -->
                        @if($canDelete && empty($deletionDependencies) && empty($deleteWarnings))
                            <div class="mb-6 rounded-md bg-green-50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-green-800">Safe to Delete</h3>
                                        <p class="mt-1 text-sm text-green-700">
                                            No dependencies found. This room type can be safely deleted.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                @endif
            </div>
            
            <!-- Footer -->
            <div class="border-t border-gray-200 px-6 py-4">
                <div class="flex justify-end space-x-3">
                    <button wire:click="cancelDelete" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    
                    @if(!$isLoadingDependencies)
                        <button wire:click="confirmDelete" 
                                @if(!$canDelete) disabled @endif
                                class="px-4 py-2 text-sm font-medium text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500
                                       @if($canDelete) bg-red-600 hover:bg-red-700 @else bg-gray-400 cursor-not-allowed @endif">
                                Delete Room Type
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif