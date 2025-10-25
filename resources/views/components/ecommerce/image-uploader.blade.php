@php
    // Configurable props for reuse:
    // $model: Livewire property name (e.g., 'images')
    // $existing: array of existing image URLs (optional)
    // $label: label text (optional)
    // $multiple: allow multiple uploads (bool, default true)
    // $limit: max files info text only (optional)
    $model = $model ?? 'images';
    $inputModel = $model . '_buffer'; // use buffer so new selections append instead of replace
    $existing = $existing ?? [];
    $label = $label ?? 'Images';
    $multiple = $multiple ?? true;
    $limitText = $limit ?? 'up to 5 images';
    $helper = $helper ?? null;
    $inputId = 'image-upload-'.uniqid();
    $existingCount = is_array($existing) ? count($existing) : 0;
    // When Livewire re-renders, $$model may not be an array yet
    $newList = isset($$model) && is_array($$model) ? $$model : [];
    $currentTotal = $existingCount + count($newList);
    $maxCount = 5;
@endphp

<div class="md:col-span-2">
    <div class="flex items-center justify-between mb-1">
        <label class="block mb-2 text-sm font-normal text-gray-700">{{ $label }} @if($multiple)<span class="text-gray-500">({{ $limitText }})</span>@endif</label>
        <span class="text-xs text-gray-500">{{ min($currentTotal, $maxCount) }}/{{ $maxCount }}</span>
    </div>
    @if(!empty($helper))
        <p class="mb-2 text-xs text-gray-500">{{ $helper }}</p>
    @endif

    <!-- File Upload Area -->
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors {{ $currentTotal >= $maxCount ? 'opacity-50 pointer-events-none' : '' }}">
        <input 
            type="file" 
            wire:model="{{ $inputModel }}"
            @if($multiple) multiple @endif
            accept="image/*"
            class="hidden"
            id="{{ $inputId }}"
            @if($currentTotal >= $maxCount) disabled @endif
>
        <label for="{{ $inputId }}" class="cursor-pointer">
            <svg class="w-12 h-12 mx-auto text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="mt-4">
                <span class="font-medium text-blue-600">Add File</span>
            </div>
        </label>
    </div>

    <!-- Image Preview: show in a horizontal, responsive grid -->
    @if(!empty($newList) || !empty($existing))
        <div class="grid grid-cols-2 gap-4 mt-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            <!-- Existing Images -->
            @foreach($existing as $index => $image)
                @php $url = is_array($image) ? ($image['url'] ?? $image['file_url'] ?? $image) : $image; @endphp
                <div class="relative w-20">
                    <img src="{{ $url }}" alt="Image" class="object-cover w-20 h-20 border border-gray-200 rounded-lg">
                    <button 
                        type="button"
                        wire:click="removeExistingImage({{ $index }})"
                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] hover:bg-red-600"
                    >
                        ×
                    </button>
                </div>
            @endforeach

            <!-- New Images -->
            @if(is_array($newList))
                @foreach($newList as $index => $image)
                    <div class="relative w-20">
                        <img src="{{ $image->temporaryUrl() }}" alt="Image" class="object-cover w-20 h-20 border border-gray-200 rounded-lg">
                        <button 
                            type="button"
                            wire:click="removeImage({{ $index }})"
                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] hover:bg-red-600"
                        >
                            ×
                        </button>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    @error($model) <span class="text-sm text-red-500">{{ $message }}</span> @enderror
    @error($model.'.*') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
    @error($inputModel) <span class="text-sm text-red-500">{{ $message }}</span> @enderror
    @error($inputModel.'.*') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
</div>