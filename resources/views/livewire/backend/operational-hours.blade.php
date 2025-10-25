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
                'active' => 'operational-hours',
                'subMenuType' => 'generalSetting',
            ])
            @endcomponent
        </div>
        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 md:grid-cols-[1fr,2fr,2fr] gap-6">
                @foreach($operational_hours as $hour)
                
                    <div class="mt-6">
                        @component('components.button-component', [
                            'label' => $hour->day,
                            'type' => 'graybuttonSmall',
                            'class' => 'h-[44px]'
                            ])
                        @endcomponent
                    </div>
                    @component('components.time-component', [
                        'wireModel' => "start_time.$hour->day",
                        'id' => 'startTime_'.$hour->day,
                        'label' => 'Start Time',
                        'star' => true,
                        'error' => $errors->first("start_time.$hour->day"),
                        ])
                    @endcomponent
                    @component('components.time-component', [
                        'wireModel' => "end_time.$hour->day",
                        'id' => 'endTime_'.$hour->day,
                        'label' => 'End Time',
                        'star' => true,
                        'error' => $errors->first("end_time.$hour->day"),
                        ])
                    @endcomponent
                @endforeach
            </div>

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