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
                'active' => 'admin.advance-duration',
                'subMenuType' => 'serviceSettings',
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <label class="label">Minimum Advance Duration*</label>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @component('components.texbox-addon-component', [
                    'type' => 'number',
                    'wireModel' => 'advance_days',
                    'id' => 'advance_days',
                    'placeholder' => 'Enter number',              
                    'addon' => 'Days',              
                    'error' => $errors->first('advance_days'),
                ])
                @endcomponent

                @component('components.texbox-addon-component', [
                    'type' => 'number',
                    'wireModel' => 'advance_hours',
                    'id' => 'advance_hours',
                    'placeholder' => 'Enter number',              
                    'addon' => 'Hours',              
                    'error' => $errors->first('advance_hours'),
                ])
                @endcomponent
            </div>
            <div class="flex items-center justify-end gap-2 mt-10">
                {{-- @component('components.button-component', [
                    'label' => 'Clear',
                    'id' => 'clear',
                    'type' => 'cancelSmall',
                    'wireClickFn' => 'resetFields',
                ])
                @endcomponent --}}

                @component('components.button-component', [
                    'label' => 'Save',
                    'id' => 'save',
                    'type' => 'buttonSmall',
                    'wireClickFn' => 'save',
                ])
                @endcomponent
            </div>
        </div>
    </div>

</main>