<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <div class="px-6 py-4 border-b bg-gray-200 border-gray-200 flex justify-between items-center cursor-pointer" @click="open = !open">
        <h3 class="text-lg font-medium text-gray-900">Address setting</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
    
    <div x-show="open" class="px-6 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Meta Title -->
            @component('components.checkbox-toggle', [
                'id' => 'is_shippable',
                'value' => $is_shippable,
                'wireModel' => 'is_shippable',
                'checked' => $is_shippable,
                'label' => 'Shippable',
                'disable' => in_array('is_shippable', $disabledFields),
                ])
            @endcomponent

            @if(in_array('limo_type', $readOnlyFields))
                @component('components.checkbox-toggle', [
                    'id' => 'limo_pickup_dropup_address',
                    'value' => $limo_pickup_dropup_address,
                    'wireModel' => 'limo_pickup_dropup_address',
                    'checked' => $limo_pickup_dropup_address,
                    'label' => 'Required Pickup/ Drop off address',
                    ])
                @endcomponent
            @endif
        </div>
    </div>
</div>