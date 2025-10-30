<main class="py-10 lg:pl-72 bg-gray-50">

    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="h-20 md:flex md:items-center md:justify-between">
            <span class="text-xl font-medium text-gray-900 sm:truncate sm:tracking-tight">
                {{ $title }}</span>
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
                'active' => 'room-cancel-setting',
                'subMenuType' => 'roomSettings',
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <div class="flex gap-1">
                <div class="w-[30%] text-sm text-left text-gray-700 p-2 mt-2">
                    <div>
                        Cancellation Before
                    </div>
                    <div class="mt-3 w-36 font-bold border border-gray-200 rounded-[16px] text-center p-8 bg-gray-100">
                        6 Hours
                    </div>
                </div>
                <div class="flex-1 p-2">
                    <div class="mt-3">
                        @component('components.textbox-component', [
                            'wireModel' => 'before_6_hour_percentage',
                            'id' => 'before_6_hour_percentage',
                            'label' => 'Refund Percentage',
                            'class' => 'w-full p-10 h-26 rounded-[16px]',
                            'star' => true,
                            'placeholder' => 'Enter before 6 hour percentage',
                            'error' => $errors->first('before_6_hour_percentage'),
                        ])
                        @endcomponent
                    </div>
                </div>
            </div>

            <div class="flex gap-1">
                <div class="w-[30%] text-sm text-left text-gray-700 p-2">
                    <div class="mt-2">
                        Cancellation Before
                    </div>
                    <div class="mt-3 w-36 font-bold border border-gray-200 rounded-[16px] text-center p-8 bg-gray-100">
                        24 Hours
                    </div>
                </div>
                <div class="flex-1 p-2">
                    <div class="mt-3">
                        @component('components.textbox-component', [
                            'wireModel' => 'before_24_hour_percentage',
                            'id' => 'before_24_hour_percentage',
                            'label' => 'Refund Percentage',
                            'class' => 'w-full p-10 h-26 rounded-[16px]',
                            'star' => true,
                            'placeholder' => 'Enter before 24 hour percentage',
                            'error' => $errors->first('before_24_hour_percentage'),
                        ])
                        @endcomponent
                    </div>
                </div>
            </div>

            <div class="flex gap-1">
                <div class="w-[30%] text-sm text-left text-gray-700 p-2">
                    <div class="mt-2">
                        Cancellation Before
                    </div>
                    <div class="mt-3 w-36 font-bold border border-gray-200 rounded-[16px] text-center p-8 bg-gray-100">
                        72 Hours
                    </div>
                </div>
                <div class="flex-1 p-2">
                    <div class="mt-3">
                        @component('components.textbox-component', [
                            'wireModel' => 'before_72_hour_percentage',
                            'id' => 'before_72_hour_percentage',
                            'label' => 'Refund Percentage',
                            'class' => 'w-full p-10 h-26 rounded-[16px]',
                            'star' => true,
                            'placeholder' => 'Enter before 72 hour percentage',
                            'error' => $errors->first('before_72_hour_percentage'),
                        ])
                        @endcomponent
                    </div>
                </div>
            </div>

            <div class="flex gap-1">
                <div class="w-[30%] text-sm text-left text-gray-700 p-2">
                    <div class="mt-2">
                        Cancellation By
                    </div>
                    <div class="mt-3 w-36 font-bold border border-gray-200 rounded-[16px] text-center p-8 bg-gray-100">
                        Admin
                    </div>
                </div>
                <div class="flex-1 p-2">
                    <div class="mt-3">
                        @component('components.textbox-component', [
                            'wireModel' => 'admin_cancel_percentage',
                            'id' => 'admin_cancel_percentage',
                            'label' => 'Refund Percentage',
                            'class' => 'w-full p-10 h-26 rounded-[16px]',
                            'star' => true,
                            'placeholder' => 'Enter admin cancel percentage',
                            'error' => $errors->first('admin_cancel_percentage'),
                        ])
                        @endcomponent
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
            <hr class="mt-10 border-gray-300"> 
        </div>
    </div>
</main>
