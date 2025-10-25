@php
    // Props:
    // $model: Livewire array property (e.g., 'images')
    // $existing: array of existing URLs
    // $label: optional label
    // $limitText: optional helper text under label
    $model = $model ?? 'images';
    $existing = $existing ?? [];
    $label = $label ?? null;
    $limitText = $limitText ?? 'You can upload multiple images';
@endphp

<div class="md:col-span-2" x-data>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }} <span class="text-gray-500">({{ $limitText }})</span></label>
    @endif

    <!-- Drop area -->
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
        <input 
            type="file" 
            wire:model="{{ $model }}"
            multiple
            accept="image/*"
            class="hidden"
            id="multi-{{ $model }}-input"
        >
        <label for="multi-{{ $model }}-input" class="cursor-pointer flex flex-col items-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="mt-2">
                <span class="text-blue-600 font-medium">Add Images</span>
            </div>
        </label>
    </div>

    <!-- Grid previews -->
    @php
        $newList = data_get($this, $model, []);
    @endphp
    @if(!empty($existing) || (is_array($newList) && count($newList)))
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach($existing as $index => $item)
                @php $url = is_array($item) ? ($item['url'] ?? $item["file_url"] ?? $item) : $item; @endphp
                <div class="relative">
                    <img src="{{ $url }}" alt="Image" class="w-full h-24 object-cover rounded-lg border">
                    <button type="button" wire:click="removeExistingImage({{ $index }})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">×</button>
                </div>
            @endforeach

            @if(is_array($newList))
                @foreach($newList as $index => $image)
                    <div class="relative">
                        <img src="{{ $image->temporaryUrl() }}" alt="Image" class="w-full h-24 object-cover rounded-lg border">
                        <button type="button" wire:click="removeImage({{ $index }})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">×</button>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    @error($model) <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    @error($model.'*') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
</div>