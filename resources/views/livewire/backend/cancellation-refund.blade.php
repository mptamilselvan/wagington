<main class="py-10 lg:pl-72 bg-gray-50">

    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="h-20 md:flex md:items-center md:justify-between">
            <span class="text-xl font-medium text-gray-900 sm:truncate sm:tracking-tight">
                {{ $title }}</span>
        </div>
    </div>

    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">
        <div class="w-1/5 p-4 bg-white">
            @component('components.subMenu', [
                'active' => 'cancellation-refund',
                'subMenuType' => 'serviceSettings',
            ])
            @endcomponent
        </div>
        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                @foreach($cr as $key => $data)
                
                    <div class="">
                        <label class="label">Cancellation before</label><br>
                        @component('components.button-component', [
                            'label' => $data->type,
                            'type' => 'graybuttonSmall',
                            'class' => 'h-[44px]'
                            ])
                        @endcomponent
                    </div>
                    <div class="col-span-4">
                        @component('components.textbox-component', [
                            'wireModel' => "value.$data->type",
                            'id' => "value_.$key",
                            'label' => 'Refund Percentage',
                            'placeholder' => "Enter number",
                            'error' => $errors->first("value.$data->type"),
                            ])
                        @endcomponent
                    </div>
                @endforeach

            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-2 mt-10">
                @component('components.button-component', [
                    'label' => 'Save',
                    'id' => 'save',
                    'type' => 'submitSmall',
                    'wireClickFn' => 'save',
                    ])
                @endcomponent
            </div>
        </div>
    </div>
</main>
