@props([
    'name' => 'image',
    'currentImage' => null,
    'error' => ''
])

<div class="mb-2 flex justify-center">
    <div class="relative">
        <!-- Profile Picture Display -->
        <div class="w-32 h-32 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden border-4 border-white shadow-lg">
            @if($currentImage)
                @php
                    $isFullUrl = is_string($currentImage) && str_starts_with($currentImage, 'http');
                    // If the path comes from DO Spaces full URL, use as-is; if from local public storage key, wrap with asset('storage/')
                    $src = $isFullUrl ? $currentImage : asset('storage/' . ltrim($currentImage, '/'));
                @endphp
                <img src="{{ $src }}" alt="Profile Picture" class="w-full h-full object-cover">
            @else
                <div class="text-center">
                    <svg class="w-6 h-6 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-sm text-gray-500">Add picture</span>
                </div>
            @endif
        </div>
        
        <!-- Upload Button -->
        <label for="{{ $name }}" class="absolute bottom-0 right-0 bg-white rounded-full p-2 shadow-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </label>
        
        <!-- Hidden File Input -->
        <input 
            type="file" 
            id="{{ $name }}" 
            name="{{ $name }}" 
            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
            class="hidden"
            onchange="previewImage(this)"
        >
    </div>
    
    @if($error)
        <p class="mt-2 text-sm text-red-600 text-center">{{ $error }}</p>
    @endif
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const container = input.parentElement.querySelector('div');
            container.innerHTML = `<img src="${e.target.result}" alt="Profile Picture" class="w-full h-full object-cover">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>