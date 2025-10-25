@props([
    'title' => 'Successfully updated!',
    'message' => '',
    'duration' => 2500, // milliseconds
])

<!-- Success Modal - auto closes after duration -->
<div
    x-data="{ show: true, closeAfter(ms){ setTimeout(() => this.show = false, ms); } }"
    x-init="closeAfter({{ (int) $duration }})"
    x-show="show"
    x-cloak
    aria-live="polite"
    class="fixed inset-0 z-50 flex items-center justify-center"
>
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/40" @click="show = false; $wire.call('clearSuccessMessage')"></div>

    <!-- Modal Card -->
    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative bg-white rounded-2xl shadow-xl px-6 py-7 w-[92%] max-w-md text-center"
        role="dialog" aria-modal="true"
    >
        <!-- Success Icon -->
        <div class="mx-auto -mt-12 mb-3 w-16 h-16 rounded-full bg-white shadow-md grid place-items-center">
            <svg class="w-9 h-9 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14" />
                <path d="M22 4L12 14.01l-3-3" />
            </svg>
        </div>

        <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
        @if($message !== '')
            <p class="mt-2 text-sm text-gray-600">{{ $message }}</p>
        @endif

        <!-- Hidden close button for accessibility; backdrop click also closes -->
        <button type="button" @click="show = false; $wire.call('clearSuccessMessage')" class="sr-only">Close</button>
    </div>
</div>