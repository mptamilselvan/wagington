<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <div class="px-6 py-4 border-b bg-gray-200 border-gray-200 flex justify-between items-center cursor-pointer" @click="open = !open">
        <h3 class="text-lg font-medium text-gray-900">Rule Engine</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
    
    <div x-show="open" class="px-6 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Meta Title -->
            @component('components.checkbox-toggle', [
                'id' => 'pet_selection_required',
                'value' => $pet_selection_required,
                'wireModel' => 'pet_selection_required',
                'checked' => $pet_selection_required,
                'label' => 'Consider Pet selection as quantity'
                ])
            @endcomponent
            {{-- {{  $disabledFields['0'] }} --}}
            @component('components.checkbox-toggle', [
                'id' => 'evaluvation_required',
                'value' => $evaluvation_required,
                'wireModel' => 'evaluvation_required',
                'checked' => $evaluvation_required,
                'label' => 'Requires Temperament Evaluation of the pet',
                'disable' => in_array('evaluvation_required', $disabledFields),
                ])
            @endcomponent
        </div>
    </div>
</div>