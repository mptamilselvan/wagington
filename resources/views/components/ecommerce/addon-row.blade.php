<div class="flex items-center gap-4 border rounded-lg p-3 bg-white">
    {{-- Image thumbnail (falls back to initial of name) --}}
    <div class="w-12 h-12 rounded overflow-hidden bg-gray-100 flex items-center justify-center">
        @php $thumb = $ad['image'] ?? null; @endphp
        @if($thumb)
            <img src="{{ $thumb }}" alt="{{ $ad['name'] }}" class="w-full h-full object-cover" />
        @else
            <span class="text-xs text-gray-500">{{ strtoupper(substr($ad['name'] ?? 'A',0,1)) }}</span>
        @endif
    </div>

    {{-- Name + variant info --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
            <div class="font-medium truncate">{{ $ad['name'] }}</div>
            @if(!empty($ad['is_required']))
                <span class="text-[11px] text-red-600 bg-red-50 border border-red-100 px-2 py-0.5 rounded">Required</span>
            @endif
        </div>
        @if(!empty($row['variant_name']))
            <div class="text-xs text-gray-500 truncate">{{ $row['variant_name'] }}</div>
        @endif
    </div>

    {{-- Optional include toggle (hide for required) --}}
    @if(empty($ad['is_required']))
        <label class="text-sm inline-flex items-center gap-2 shrink-0">
            <input type="checkbox" wire:model="selectedAddons.{{ $ad['id'] }}.selected" class="rounded border-gray-300">
            <span>Include</span>
        </label>
    @else
        <span class="text-xs text-gray-500 shrink-0">Included</span>
    @endif

    {{-- Price + qty in one compact control --}}
    <div class="flex items-center gap-3 shrink-0">
        <span class="text-sm text-gray-700 whitespace-nowrap">S${{ number_format($row['unit_price'] ?? 0, 2) }}</span>
        <div class="inline-flex items-center border rounded overflow-hidden">
            <button type="button" class="px-2 py-1 text-gray-700 hover:bg-gray-50" @click.prevent="$wire.set('selectedAddons.{{ $ad['id'] }}.qty', Math.max(1, (parseInt($wire.get('selectedAddons.{{ $ad['id'] }}.qty') || 1) - 1)))">–</button>
            <input type="number" min="1" inputmode="numeric" pattern="[0-9]*" wire:model="selectedAddons.{{ $ad['id'] }}.qty" class="w-12 text-center border-0 focus:ring-0 py-1" />
            <button type="button" class="px-2 py-1 text-gray-700 hover:bg-gray-50" @click.prevent="$wire.set('selectedAddons.{{ $ad['id'] }}.qty', (parseInt($wire.get('selectedAddons.{{ $ad['id'] }}.qty') || 1) + 1))">+</button>
        </div>
    </div>
</div>