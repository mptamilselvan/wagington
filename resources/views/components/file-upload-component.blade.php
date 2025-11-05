<div
    x-data="{ isDragging: false }"
    x-on:drop.prevent="isDragging = false"
    x-on:dragover.prevent="isDragging = true"
    x-on:dragleave.prevent="isDragging = false"
    class="cursor-pointer"
    :class="{ 'border-blue-500 bg-blue-50': isDragging, 'border-gray-300': !isDragging }"
>
    <label for="{{ $wireModel }}"  class="block mb-2 text-sm font-normal text-gray-700">{{ $label ?? '' }}
        @if (isset($star) && $star == true)
            <span class="">*</span>
        @else
            <span class="">&nbsp;</span>
        @endif
    </label>
    <label for="{{ $wireModel }}" class="block text-center cursor-pointer">
        <div class="flex flex-col items-center">
            <div class="flex w-full p-3 border-2 border-dashed rounded-md">
                <span class="text-[#1B85F3] text-sm flex-1 text-left ">Add File</span>
                <span class="text-sm text-[#1B85F3] ml-2 flex-1 text-right">+</span>
            </div>
        </div>
        <input id="{{ $wireModel }}" type="file" wire:model="{{ $wireModel }}" class="hidden" @if (isset($disabled) && $disabled == true) disabled="" @endif class="rounded-xl" />
    </label>

    @if (isset($error))
        <div id="error_{{ $wireModel }}" class="mt-2 error-message">{{ $error }}</div>
    @endif

    @php
        $file = data_get($this, $wireModel);
    @endphp


    <!-- Show preview -->
    @if (!empty($file))
        @if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
            {{-- New uploaded file --}}
            @if (Str::startsWith($file->getMimeType(), 'image/'))
                <img src="{{ $file->temporaryUrl() }}" class="object-cover w-20 h-20 rounded">
            @else
                <div class="flex items-center justify-center w-20 h-20 bg-gray-200 rounded">
                    <i class="fa-solid fa-file"></i>
                </div>
            @endif
        @elseif (is_string($file))
            {{-- Existing file path (edit mode) --}}
            @if (Str::endsWith($file, ['.jpg', '.jpeg', '.png', '.gif']))
                <img src="{{ env('DO_SPACES_URL').'/'.$file }}" class="object-cover w-20 h-20 rounded">
            @elseif (Str::endsWith($file, '.pdf'))
                <a href="{{ env('DO_SPACES_URL').'/'.$file }}" target="_blank">
                    <div class="flex items-center justify-center w-20 h-20 bg-gray-200 rounded">
                        <i class="fa-solid fa-file"></i>
                    </div>
                </a>
            @endif
        @endif
        
        {{-- Other file types --}}
    @elseif ($src)
        {{-- Existing stored image --}}
        @if (is_string($src) && Str::endsWith($src, ['.jpg','.jpeg','.png','.gif']))
            {{-- Saved image in DO Spaces --}}
            <img src="{{ env('DO_SPACES_URL').'/'.$src }}" class="object-cover w-20 h-20">

        @elseif (is_string($src) && Str::endsWith($src, '.pdf'))
            {{-- Saved PDF in DO Spaces --}}
            <a href="{{ env('DO_SPACES_URL').'/'.$src }}" target="_blank"><div class="flex items-center justify-center w-20 h-20 bg-gray-200 rounded">
                <i class="fa-solid fa-file"></i>
            </div>
            </a>
        @endif
            {{-- <img src="{{ asset('images/pdf-icon.png') }}" alt="PDF" class="w-20 h-20"> --}}
        {{-- <img src="{{ env('DO_SPACES_URL').'/'.$src }}" class="w-40" alt="current"> --}}
    @endif
</div>


