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
                'active' => 'tax-settings',
                'subMenuType' => 'generalSetting',
            ])
            @endcomponent
        </div>
        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'tax_type',
                            'id' => 'tax_type',
                            'label' => 'Tax Type',
                            'placeholder' => "Enter Tax Type",
                            'readonly' => true,
                            ])
                        @endcomponent
                    </div>
                </div>
                <div>
                   <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'rate',
                            'id' => 'rate',
                            'label' => 'Tax Rate(%)',
                            'star' => true,
                            'placeholder' => "Enter Tax Rate",
                            'read_only' => true,
                            'error' => $errors->first('rate'),
                            ])
                        @endcomponent
                    </div>
                </div>
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
