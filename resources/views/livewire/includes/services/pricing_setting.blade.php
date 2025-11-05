<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <div class="px-6 py-4 border-b bg-gray-200 border-gray-200 flex justify-between items-center cursor-pointer" @click="open = !open">
        <h3 class="text-lg font-medium text-gray-900">Pricing setting</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
    
    <div x-show="open" class="px-6 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @component('components.dropdown-component', [
                'wireModel' => 'pricing_type',
                'id' => 'pricing_type',
                'label' => 'Pricing Type',
                'star' => true,
                'options' => [['value' => 'fixed','option' => 'Fixed'],['value' => 'advance','option' => 'Advance'],['value' => 'distance_based','option' => 'Distance based']],
                'error' => $errors->first('pricing_type'),
                'placeholder_text' => "Select Pricing type",
                'disabled' => in_array('pricing_type', $readOnlyFields),
                // 'disabled' => $service_addon ? true : false,
            ])
            @endcomponent

            @component('components.select2-dropdown', [
                'wireModel' => 'pricing_attributes',
                'id' => 'pricing_attributes',
                'label' => 'Pricing Attributes',
                'star' => true,
                'options' => $availableAttributes,
                'multiple' => true,
                'error' => $errors->first('pricing_attributes'),
                'placeholder_text' => "Select Pricing type",
                'disabled' => in_array('pricing_attributes', $readOnlyFields) ? true : false,
                ])
                @endcomponent

            {{-- @component('components.select2-dropdown', [
                'wireModel' => 'pricing_attributes',
                'id' => 'pricing_attributes',
                'label' => 'Pricing Attributes',
                'star' => true,
                'options' => $availableAttributes,
                'error' => $errors->first('pricing_attributes'),
                'placeholder_text' => "Select Pricing type",
                'multiple'=>true,
                'wireBlurfn' => 'updatedPricingAttributes()'
            ])
            @endcomponent --}}
        </div>

        <!-- Pricing Options -->
        <div>
            <div class="flex justify-between items-center mb-2">
                <h4 class="text-md font-semibold">Pricing Options</h4>
                @if($service_addon == false)
                    <button type="button" wire:click="addPricingOption" class="text-blue-600 text-sm font-medium">+ Add more</button>
                @endif
            </div>
            <div class="space-y-3">
                @foreach($pricingOptions as $index => $option)
                    <div class="flex flex-wrap items-end gap-4 border border-gray-200 rounded-lg p-4 relative">
                        @foreach($selectedAttributes as $key)
                            @php
                                $attr = collect($availableAttributes)->firstWhere('value', $key);
                            @endphp
                            <div class="flex-1 min-w-[150px]">
                                {{-- <label class="block text-sm font-medium text-gray-700">
                                    {{ $attr['option'] ?? ucfirst($key) }} {{ $attr['data_type'] }}
                                </label> --}}
                                @if($attr['data_type'] == 'text')
                                    @component('components.textbox-component', [
                                        'wireModel'   => 'pricingOptions.'.$index.'.'.$key,
                                        'id'          => 'pricingOptions'.$index.$key,
                                        'label'       => $attr['option'] ?? ucfirst($key),
                                        'star'        => true,
                                        'placeholder' => 'Enter '.strtolower($attr['option'] ?? $key),
                                        'error'       => $errors->first('pricingOptions.'.$index.'.'.$key),
                                    ])
                                    @endcomponent
                                @endif

                                @if($attr['data_type'] == 'Intger')
                                    @component('components.texbox-number-component', [
                                        'wireModel'   => 'pricingOptions.'.$index.'.'.$key,
                                        'id'          => 'pricingOptions'.$index.$key,
                                        'label'       => $attr['option'] ?? ucfirst($key),
                                        'star'        => true,
                                        'placeholder' => 'Enter '.strtolower($attr['option'] ?? $key),
                                        'error'       => $errors->first('pricingOptions.'.$index.'.'.$key),
                                        'min' => 0,
                                    ])
                                    @endcomponent
                                @endif

                                @if($attr['data_type'] == 'decimal')
                                    @component('components.texbox-number-component', [
                                        'wireModel'   => 'pricingOptions.'.$index.'.'.$key,
                                        'id'          => 'pricingOptions'.$index.$key,
                                        'label'       => $attr['option'] ?? ucfirst($key),
                                        'star'        => true,
                                        'placeholder' => 'Enter '.strtolower($attr['option'] ?? $key),
                                        'error'       => $errors->first('pricingOptions.'.$index.'.'.$key),
                                        'min' => 0,
                                    ])
                                    @endcomponent
                                @endif

                                @if($attr['data_type'] == 'time')
                                    @component('components.time-component', [
                                        'wireModel' => 'pricingOptions.'.$index.'.'.$key,
                                        'id' => 'pricingOptions.'.$index.'.'.$key,
                                        'label' => $attr['option'] ?? ucfirst($key),
                                        'star' => true,
                                        'error' => $errors->first('pricingOptions.'.$index.'.'.$key),
                                    ])
                                    @endcomponent
                                @endif
                                {{-- <input type="text"
                                       wire:model="pricingOptions.{{ $index }}.{{ $key }}"
                                       class="w-full border-gray-300 rounded-lg"
                                       placeholder="Enter {{ strtolower($attr['option'] ?? $key) }}"> --}}
                            </div>
                        @endforeach

                        @if($index > 0)
                            <button type="button" wire:click="removePricingOption({{ $index }})" class="text-red-600 hover:text-red-800 absolute top-2 right-2">
                                <i class="fa fa-trash-can"></i>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

 