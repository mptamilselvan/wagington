{{-- Progress Steps - Show item's actual fulfillment status with proper shippable/non-shippable handling --}}
<div class="mb-4">
    <div class="flex flex-col">
        {{-- Product Type Indicator --}}
        <div class="mb-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item['shippable'] ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                {{ $item['shippable'] ? 'Shippable Product' : 'Nonshippable Product' }}
            </span>
        </div>

        {{-- Progress Steps --}}
        @php $steps = $this->getItemProgressSteps($item); @endphp
        <div class="flex items-center justify-between">
            @foreach($steps as $index => $step)
                <div class="flex flex-col items-center flex-1">
                    {{-- Step Icon --}}
                    <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $step['active'] ? ($item['shippable'] ? 'bg-blue-600' : 'bg-purple-600') : 'bg-gray-200' }} {{ $step['active'] ? 'text-white' : 'text-gray-400' }}">
                        @switch($step['icon'])
                            @case('clock')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                @break
                            @case('clipboard-list')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                @break
                            @case('cog')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                @break
                            @case('truck')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                @break
                            @case('check-circle')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                @break
                        @endswitch
                    </div>
                    
                    {{-- Step Label --}}
                    <p class="text-xs text-center mt-2 {{ $step['active'] ? ($item['shippable'] ? 'text-blue-600' : 'text-purple-600') : 'text-gray-500' }} font-medium">
                        {{ $step['label'] }}
                    </p>
                </div>
                
                {{-- Progress Line --}}
                @if($index < count($steps) - 1)
                    <div class="flex-1 h-0.5 mx-4 {{ $steps[$index + 1]['active'] ? ($item['shippable'] ? 'bg-blue-600' : 'bg-purple-600') : 'bg-gray-200' }}"></div>
                @endif
            @endforeach
        </div>
    </div>
</div>