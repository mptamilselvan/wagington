@props(['order'])

@php
    $statuses = [
        'Order Placed' => ['placed', 'confirmed'],
        'Processing' => ['processing'],
        'Shipped' => ['shipped'],
        'Delivered' => ['delivered']
    ];
    
    $currentStatus = $order->status ?? 'processing';
    $currentStep = 0;
    
    // Special handling for backordered - should stay at Order Placed
    if (in_array($currentStatus, ['backordered', 'partially_backordered'])) {
        $currentStep = 0; // Stay at Order Placed
    } else {
        // Determine current step for normal statuses
        foreach ($statuses as $step => $statusList) {
            if (in_array($currentStatus, $statusList)) {
                break;
            }
            $currentStep++;
        }
    }
@endphp

<div class="w-full">
    <div class="flex items-center justify-between">
        @foreach($statuses as $stepName => $statusList)
            @php
                $stepIndex = array_search($stepName, array_keys($statuses));
                $isCompleted = $stepIndex < $currentStep;
                $isCurrent = $stepIndex === $currentStep;
                $isActive = $isCompleted || $isCurrent;
            @endphp
            
            <div class="flex flex-col items-center {{ $loop->first ? '' : 'flex-1' }}">
                @if(!$loop->first)
                    <!-- Progress Line -->
                    <div class="w-full h-0.5 {{ $isCompleted ? 'bg-blue-500' : 'bg-gray-200' }} mb-4"></div>
                @endif
                
                <!-- Status Icon -->
                <div class="flex items-center justify-center w-10 h-10 rounded-full mb-2 {{ $isActive ? ($isCompleted ? 'bg-blue-500' : 'bg-blue-100 border-2 border-blue-500') : 'bg-gray-200' }}">
                    @if($isCompleted)
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @elseif($isCurrent)
                        <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
                    @else
                        <div class="w-4 h-4 bg-gray-400 rounded-full"></div>
                    @endif
                </div>
                
                <!-- Status Label -->
                <span class="text-xs font-medium {{ $isActive ? 'text-gray-900' : 'text-gray-400' }} text-center">
                    {{ $stepName }}
                </span>
            </div>
            
            @if(!$loop->last)
                <!-- Connection Line -->
                <div class="flex-1 h-0.5 {{ $stepIndex < $currentStep ? 'bg-blue-500' : 'bg-gray-200' }} mx-2 mt-[-2rem]"></div>
            @endif
        @endforeach
    </div>
</div>