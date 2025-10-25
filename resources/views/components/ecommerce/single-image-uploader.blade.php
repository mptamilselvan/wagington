@php
    // Props:
    // $model: Livewire property (e.g., 'images' or 'optionImage')
    // $existing: string|null existing image URL
    // $label: optional label
    $model = $model ?? 'images';
    $existing = $existing ?? null;
    $label = $label ?? null;
@endphp

<div class="inline-block" x-data>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>
    @endif

    @php 
        // Determine presence of image: either existing URL or a new TemporaryUploadedFile at $$model[0]
        $newFile = null;
        if (isset($$model)) {
            if (is_array($$model) && count($$model) > 0) {
                $newFile = $$model[0];
            } elseif(is_object($$model)) {
                $newFile = $$model; // in case bound directly
            }
        }
        $hasImage = $existing || $newFile;
    @endphp

    @if(!$hasImage)
        <!-- Plus button uploader -->
        <div class="relative w-12 h-12">
            <input 
                type="file" 
                accept="image/*"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-50"
                x-on:change="$wire.upload('{{ $model }}', $event.target.files[0])"
            >
            <div class="absolute inset-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center hover:bg-blue-200 pointer-events-none" title="Add image">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
            </div>
        </div>
    @else
        <!-- Preview with remove X; call removeExistingImage(0) or clear method if provided -->
        <div class="relative w-12 h-12">
            @if($newFile)
                <img src="{{ $newFile->temporaryUrl() }}" alt="Image" class="w-12 h-12 object-cover rounded-lg border border-gray-200">
                <button type="button" wire:click="{{ $clearMethod ?? 'clearAddonImage' }}" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600" aria-label="Remove image">×</button>
            @elseif($existing)
                <img src="{{ $existing }}" alt="Image" class="w-12 h-12 object-cover rounded-lg border border-gray-200">
                <button type="button" wire:click="{{ $removeExistingMethod ?? 'removeExistingImage' }}(0)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600" aria-label="Remove image">×</button>
            @endif
        </div>
    @endif

    @error($model) <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
</div>