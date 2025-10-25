@props([
    // mode: 'main' (no checkbox) or 'addon' (checkbox + qty)
    'mode' => 'addon',
    'id' => null, // addon id for bindings (ignored for main)
    'image' => null,
    'name' => '',
    'price' => 0,
    'required' => false,
    'row' => null, // selectedAddons[$id]
])

<div class="min-w-[160px] max-w-[180px] w-[160px] border rounded-lg bg-white overflow-hidden" @if($id) wire:key="addon-inline-{{ $id }}" @endif>
    <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">
        @if($image)
            <img src="{{ $image }}" alt="{{ $name }}" class="object-contain w-full h-full" />
        @else
            <span class="text-sm text-gray-400">No image</span>
        @endif
    </div>
    <div class="p-3 space-y-2">
        <div class="text-sm font-medium line-clamp-2">{{ $name }}</div>
        <div class="flex items-center justify-between">
            <div class="text-sm font-semibold">S${{ number_format($price,2) }}</div>
            @if($mode === 'addon')
                @if($required)
                    <span class="text-[11px] text-red-600 bg-red-50 border border-red-100 px-2 py-0.5 rounded">Required</span>
                @else
                    <label class="text-xs inline-flex items-center gap-1">
                        <input type="checkbox" class="rounded border-gray-300" wire:model="selectedAddons.{{ $id }}.selected">
                        <span>Include</span>
                    </label>
                @endif
            @endif
        </div>

        @if($mode === 'addon')
            <div class="flex items-center justify-between">
                <div class="text-xs text-gray-500 truncate">{{ $row['variant_name'] ?? '' }}</div>
                <div class="inline-flex items-center border rounded overflow-hidden">
                    <button type="button" class="px-2 py-1 text-gray-700 hover:bg-gray-50"
                        <button
                            type="button"
                            class="px-2 py-1 text-gray-700 hover:bg-gray-50"
                            @click.prevent="
                                const current = Number($wire.get('selectedAddons.{{ $id }}.qty')) || 1;
                                $wire.set('selectedAddons.{{ $id }}.qty', Math.max(1, current - 1));
                            "
                        >–</button>

                        <input
                            type="number"
                            min="1"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            wire:model="selectedAddons.{{ $id }}.qty"
                            value="{{ $row['qty'] ?? 1 }}"
                            class="w-12 text-center border-0 focus:ring-0 py-1"
                        />

                        <button
                            type="button"
                            class="px-2 py-1 text-gray-700 hover:bg-gray-50"
                            @click.prevent="
                                const current = Number($wire.get('selectedAddons.{{ $id }}.qty')) || 1;
                                $wire.set('selectedAddons.{{ $id }}.qty', current + 1);
                            "
                        >+</button>
                </div>
            </div>
        @endif
    </div>
</div>