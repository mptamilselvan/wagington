<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <div class="px-6 py-4 border-b bg-gray-200 border-gray-200 flex justify-between items-center cursor-pointer">
        <h3 class="text-lg font-medium text-gray-900">Service Booking Slots</h3>
        @if($booking_slot_flag == true)
            <div class="bg-[#D6FFCC] manrope-600 w-[57px] h-[20px] text-[12px] text-[#3CB504] flex items-center justify-center rounded-full p-3">
                Edited
            </div>
        @endif
    </div>
    
    <div class="px-6 py-6">
        <div class="grid grid-cols-1 gap-6 divide-y divide-gray-200">
            @foreach($days as $day)
                <div class="py-4 first:pt-0 last:pb-0">
                    
                    {{-- Input fields for adding a NEW slot --}}
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-3 mt-4 items-center">
                        <div class="mt-6">
                            @component('components.button-component', [
                                'label' => $day,
                                'type' => 'graybuttonSmall',
                                'class' => 'h-[44px]'
                                ])
                            @endcomponent
                        </div>
                        <div class="col-span-2">
                        @component('components.time-component', [
                            'wireModel' => "slots.{$day}.new.start",
                            'id' => "slots.{$day}.new.start",
                            'label' => 'Start Time',
                            'star' => true,
                            'error' => $errors->first("slots.{$day}.new.start"),
                        ])
                        @endcomponent
                        </div>
                        <div class="col-span-2">
                        @component('components.time-component', [
                            'wireModel' => "slots.{$day}.new.end",
                            'id' => "slots.{$day}.new.end",
                            'label' => 'End Time',
                            'star' => true,
                            'error' => $errors->first("slots.{$day}.new.end"),
                        ])
                        @endcomponent
                        </div>
                        <div class="mt-auto float-left">
                            <button type="button" wire:click="addSlot('{{ $day }}')"
                                class="text-blue-500 hover:text-blue-700 font-medium whitespace-nowrap">+ Add</button>
                        </div>
                    </div>

                    {{-- Existing slots displayed as tags --}}
                    {{-- Existing slots displayed as tags/chips below --}}
                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        @foreach ($slots[$day] as $index => $slot)
                            {{-- Only display a tag if the slot has values --}}
                            @if(!empty($slot['start']) && !empty($slot['end']))
                                <div class="inline-flex items-center px-3 py-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-md">
                                    <span>{{ date('h:i A', strtotime($slot['start'])) }} - {{ date('h:i A', strtotime($slot['end'])) }}</span>
                                    <button type="button" 
                                        wire:click="removeSlot('{{ $day }}', {{ $index }})" 
                                        class="flex-shrink-0 ml-2 -mr-1.5 p-0.5 text-blue-400 rounded hover:bg-blue-200 hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <span class="sr-only">Remove slot</span>
                                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        
        {{-- Save button --}}
        <div class="flex items-center justify-end gap-2 mt-10">
            @component('components.button-component', [
                'label' => 'Save',
                'id' => 'save',
                'type' => 'submitSmall',
                'wireClickFn' => 'saveBookingSlots',
            ])
            @endcomponent
        </div>
    </div>
</div>
