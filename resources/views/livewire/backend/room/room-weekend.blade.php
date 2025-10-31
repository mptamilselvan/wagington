<main class="py-10 lg:pl-72 bg-gray-50">

    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="h-20 md:flex md:items-center md:justify-between">
            <span class="text-xl font-medium text-gray-900 sm:truncate sm:tracking-tight">
                {{ $heading }}</span>
        </div>
    </div>

    @if(session()->has('success'))
        <x-success-modal :title="'Successfully updated!'" :message="session('success')" :duration="5000" />
    @endif

    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">

        <div class="w-1/5 p-4 bg-white">
            @component('components.subMenu', [
                'active' => 'room-weekend',
                'subMenuType' => 'roomSettings',
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
           

            <div class="flex gap-1">
                
                <div class="flex-1 p-2">
                    <div class="mt-3">
                        <label for="weekend_price_variation" class="block text-sm font-medium text-gray-700 mb-1">Weekend Price Variation</label>
                        <input type="number" step="0.01" min="0" wire:model="weekend_price_variation" id="weekend_price_variation" placeholder="Enter price variation" class="w-full p-4 border border-gray-300 rounded-[12px] focus:ring-blue-500 focus:border-blue-500" />
                        @error('weekend_price_variation')
                            <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

           

          

            <div class="flex items-center justify-end gap-2 mt-10">
                @component('components.button-component', [
                    'label' => 'Update',
                    'id' => 'save',
                    'type' => 'buttonSmall',
                    'wireClickFn' => 'save',
                ])
                @endcomponent
            </div>
            
        </div>
    </div>
</main>
