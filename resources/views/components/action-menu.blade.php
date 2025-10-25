@props(['align' => 'right', 'width' => 'w-48'])

<div class="relative inline-block text-left" x-data="{
        open: false,
        dropUp: false,
        checkPlacement() {
            const btn = this.$refs.trigger;
            const menu = this.$refs.menu;
            if (!btn || !menu) return;

            // Measure actual menu height for accurate placement decisions
            const rect = btn.getBoundingClientRect();
            const menuHeight = menu.offsetHeight || menu.scrollHeight || 0;
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;

            const buffer = 12; // Small spacing between trigger and menu

            // Show dropdown upward if there's insufficient space below but enough space above
            this.dropUp = spaceBelow < (menuHeight + buffer) && spaceAbove > (menuHeight + buffer);
        }
    }"
    @keydown.escape.window="open = false"
>
    <!-- Three-dot trigger button -->
    <button x-ref="trigger"
            @click="open = !open; if(open) checkPlacement()"
            class="inline-flex items-center p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-md transition-colors duration-200"
            {{ $attributes->except(['align', 'width']) }}>
        <x-icons.threeDot class="w-5 h-5" />
    </button>
    
    <!-- Dropdown Menu -->
    <div x-ref="menu"
         x-show="open" 
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute z-50 {{ $width }} bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none {{ $align === 'left' ? 'left-0' : 'right-0' }}"
         x-effect="if (open) { requestAnimationFrame(() => checkPlacement()); }"
         :class="dropUp 
                    ? '{{ $align === 'left' ? 'bottom-full mb-2 origin-bottom-left' : 'bottom-full mb-2 origin-bottom-right' }}'
                    : '{{ $align === 'left' ? 'top-full mt-2 origin-top-left' : 'top-full mt-2 origin-top-right' }}'">
        <div class="py-1">
            {{ $slot }}
        </div>
    </div>
</div>