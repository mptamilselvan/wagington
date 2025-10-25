 <div>
  <!-- Success/Error Messages -->
    
   @component('components.alert-component')
   @endcomponent
  @if (session()->has('message123'))
      <div class="px-4 sm:px-6 py-2 mt-3">
          <div class="rounded-lg p-4 {{ session('message_type') === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
              <div class="flex items-center">
                  @if(session('message_type') === 'success')
                      <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                  @else
                      <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                  @endif
                  <span class="{{ session('message_type') === 'success' ? 'text-green-800' : 'text-red-800' }} font-medium">
                      {{ session('message') }}
                  </span>
                  <button 
                      wire:click="$set('clearMessage', true)"
                      class="ml-auto text-gray-400 hover:text-gray-600"
                      onclick="this.parentElement.parentElement.parentElement.remove()"
                  >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                      </svg>
                  </button>
              </div>
          </div>
      </div>
  @endif

  <div class="px-4 sm:px-6 py-2 mt-3">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-4 py-2">
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-semibold text-gray-900">Rooms</h1>
                        <div class="flex items-center space-x-3">
                            <!-- Search Input -->
                            <div class="relative">
                                <input type="text" wire:model.live="search" 
                                    class="w-80 px-4 py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                    placeholder="Room name, room type name">
                                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            
                            <!-- Filter Dropdown -->
                            <div class="relative" x-data="{ open: false }" x-on:click.away="open = false">
                                <button 
                                    wire:click="toggleFilterDropdown"
                                    class="w-80 px-4 py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white hover:bg-gray-50 flex items-center justify-between"
                                >
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                        <span class="text-gray-500">Filter by room type</span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <!-- Filter Dropdown Content -->
                                @if($showFilterDropdown)
                                    <div class="absolute top-full left-0 mt-1 w-80 bg-white border border-gray-300 rounded-lg shadow-lg z-50">
                                        <div class="p-4">
                                            <div class="space-y-4">
                                                <!-- Species Filter -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Species</label>
                                                    <select wire:model="filterSpecies" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                        <option value="">All Room Types</option>
                                                        @if(isset($roomTypes) && count($roomTypes) > 0)
                                                            @foreach($roomTypes as $roomType)
                                                                <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                                <!-- Filter Actions -->
                                                <div class="flex justify-between pt-4 border-t border-gray-200">
                                                    <button 
                                                        wire:click="clearFilters"
                                                        class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                                    >
                                                        Clear
                                                    </button>
                                                    <button 
                                                        wire:click="applyFilters"
                                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                                    >
                                                        Apply
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                          
                            
                            <!-- Add Button -->
                            <button 
                                wire:click="createRoom"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors text-sm"
                            >
                                Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room List -->
        <div class="px-4 sm:px-6 pb-20">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-visible">
                <!-- Table -->
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Room Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Room Type
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 overflow-visible">
                        @forelse($rooms as $room)
                            <tr class="hover:bg-gray-50 overflow-visible">
                                <!-- Inline Delete Confirmation -->
                                @if($roomToDelete === $room->id)
                                <td colspan="6" class="px-6 py-4">
                                    <div class="flex justify-end">
                                        <div class="flex items-center justify-between bg-gradient-to-r from-red-50 to-orange-50 border-2 border-red-300 rounded-lg p-3 shadow-lg max-w-2xl">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-shrink-0">
                                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                                                        <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h4 class="text-sm font-semibold text-gray-900">Delete "{{ $room->name }}"?</h4>
                                                    <p class="text-xs text-gray-600 mt-0.5">This action cannot be undone.</p>
                                                </div>
                                            </div>
                                            <div class="flex gap-2 ml-4">
                                                <button 
                                                    wire:click="cancelDelete"
                                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                                >
                                                    Cancel
                                                </button>
                                                <button 
                                                    wire:click="confirmDelete"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-gradient-to-r from-red-500 to-red-600 rounded-lg hover:from-red-600 hover:to-red-700 transition-colors shadow-md hover:shadow-lg"
                                                >
                                                    <span class="flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                        Delete
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                @else
                                <!-- Product Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $room->name }}</div>
                                </td>

                                <!-- Category path -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $roomType = $room->roomType ? $room->roomType->name : 'N/A';
                                    @endphp
                                    <div class="text-sm text-gray-700">
                                        {{ $roomType }}
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($room->status === 'available')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Available
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ ucfirst($room->status) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open" class="p-2 text-gray-400 hover:text-gray-600">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                            </svg>
                                        </button>
                                        
                                        <div x-show="open" @click.away="open = false" x-cloak
                                             class="absolute right-0 top-full mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50 origin-top">
                                            <div class="py-1">
                                                <!--<button 
                                                    wire:click="viewRoom({{ $room->id }})"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                            View
                                                    </div>
                                                </button>-->
                                                <button 
                                                    wire:click="editRoom({{ $room->id }})"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                >
                                                    <div class="flex items-center">
                                                        <!--<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>-->
                                                        Update
                                                    </div>
                                                </button>
                                                
                                                
                                                <button 
                                                    wire:click="initiateDelete({{ $room->id }})"
                                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                                                >
                                                    <div class="flex items-center">
                                                        <!--<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>-->
                                                            Delete
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"></path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No rooms</h3>
                                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new room.</p>
                                    <div class="mt-6">
                                        <button 
                                            wire:click="createRoom"
                                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                        >
                                            Add Room
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($rooms->hasPages())
            <div class="fixed bottom-0 bg-white border-t border-gray-200 shadow-lg z-50" style="left: 288px; right: 0;">
                <div class="px-4 sm:px-6 py-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            
                            
                            <!-- Pagination Controls -->
                            <div class="flex items-center space-x-2">
                            {{-- Previous Page Link --}}
                            @if ($rooms->onFirstPage())
                                <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded-md cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </span>
                            @else
                                <button wire:click="previousPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                            @endif

                            {{-- Pagination Elements --}}
                            @foreach ($rooms->getUrlRange(1, $rooms->lastPage()) as $page => $url)
                                @if ($page == $rooms->currentPage())
                                    <span class="px-3 py-2 text-sm text-white bg-blue-600 rounded-md">{{ $page }}</span>
                                @else
                                    <button wire:click="gotoPage({{ $page }})" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">{{ $page }}</button>
                                @endif
                            @endforeach

                            {{-- Next Page Link --}}
                            @if ($rooms->hasMorePages())
                                <button wire:click="nextPage" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            @else
                                <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded-md cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </span>
                            @endif
                            </div>
                            <!-- Per Page Dropdown -->
                            <div class="flex items-center space-x-2">
                                
                                <select 
                                    wire:model.live="perPage" 
                                    id="perPage"
                                    class="text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-14"
                                >
                                    <option value="10">10</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="500">500</option>
                                </select>
                                <label for="perPage" class="text-sm text-gray-700"> /Page</label>
                            </div>

                        </div>
                        
                        <div></div>
                    </div>
                </div>
            </div>
        @endif
</div>