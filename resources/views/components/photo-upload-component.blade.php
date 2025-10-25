<div class="flex flex-col items-center space-y-4">
    <div class="relative">
        <!-- Circular button -->
        <label class="flex items-center justify-center w-32 h-32 rounded-full bg-gray-300 hover:opacity-90 border-4 border-white shadow-lg">

            
            
            {{-- If new file is chosen --}}
            @if ($this->{$wireModel})
                <img src="{{ $this->{$wireModel}->temporaryUrl() }}" class="w-32 h-32 rounded-full object-cover">
            @elseif ($src)
                {{-- Existing stored image --}}
                <img src="{{ env('DO_SPACES_URL').'/'.$src }}" class="w-32 h-32 rounded-full object-cover">
            @else
                <div class="text-center">
                    <svg class="w-6 h-6 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-sm text-gray-500">Add picture</span>
                </div>
            @endif
        </label>

        <!-- Small image icon overlay -->
        <label for="photo" class="absolute bottom-2 right-2 bg-white rounded-lg shadow p-1 cursor-pointer">
           <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </label>
        <input id="photo" type="file" wire:model="{{ $wireModel }}" name="{{ $wireModel }}" class="hidden" accept="image/*">
    </div>

    <!-- Hidden file input -->

    @if (isset($error))
        <div id="error_{{ $wireModel }}" class="mt-2 error-message">{{ $error }}</div>
    @endif

</div>