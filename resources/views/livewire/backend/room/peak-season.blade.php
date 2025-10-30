<main class="py-10 lg:pl-72 bg-gray-50">

    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="h-20 md:flex md:items-center md:justify-between">
            <span class="text-xl font-medium text-gray-900 sm:truncate sm:tracking-tight">
                {{ $page_title }}</span>
        </div>
    </div>

    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">
        <div class="w-1/5 p-4 bg-white">
            @component('components.subMenu', [
                'active' => 'room-peak-seasons',
                'subMenuType' => 'roomSettings',
            ])
            @endcomponent
        </div>
        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'title',
                            'id' => 'title',
                            'label' => 'Title',
                            'star' => true,
                            'placeholder' => 'Enter title',
                            'error' => $errors->first('title'),
                            ])
                        @endcomponent
                    </div>
                    <div>
                        @component('components.date-component', [
                            'wireModel' => 'start_date',
                            'id' => 'startDate',
                            'label' => 'Start Date',
                            'star' => true,
                            'error' => $errors->first('start_date'),
                            'min' => date('Y-m-d')
                            ])
                        @endcomponent
                    </div>
                    <div class="mt-3">
                        @component('components.date-component', [
                            'wireModel' => 'end_date',
                            'id' => 'endDate',
                            'label' => 'End Date',
                            'star' => true,
                            'error' => $errors->first('end_date'),
                            'min' => date('Y-m-d')
                            ])
                        @endcomponent
                    </div>
                    <div class="mt-2">
                        @component('components.textbox-component', [
                            'wireModel' => 'peak_price_variation',
                            'id' => 'peak_price_variation',
                            'label' => 'Peak Price % Increase',
                            'star' => true,
                            'placeholder' => 'Peak Price % Increase',
                            'error' => $errors->first('peak_price_variation'),
                            ])
                        @endcomponent
                    </div>
                    <div class="mt-2">
                        @component('components.textbox-component', [
                            'wireModel' => 'weekend_price_variation',
                            'id' => 'weekend_price_variation',
                            'label' => 'Weekend Price % Increase',
                            'star' => true,
                            'placeholder' => 'Weekend Price % Increase',
                            'error' => $errors->first('weekend_price_variation'),
                            ])
                        @endcomponent
                    </div>
                </div>
                <div>
                    @component('components.textarea-component', [
                    'wireModel' => 'description',
                    'id' => 'description',
                    'rows' => 10,
                    'label' => 'description',
                    'star' =>false,
                    'placeholder' => 'Type here...',
                    'error' => $errors->first('description'),
                    ])
                    @endcomponent
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-2 mt-10">
                @component('components.button-component', [
                    'label' => 'Clear',
                    'id' => 'clear',
                    'type' => 'cancelSmall',
                    'wireClickFn' => 'resetFields',
                    ])
                @endcomponent
                @component('components.button-component', [
                    'label' => 'Save',
                    'id' => 'save',
                    'type' => 'submitSmall',
                    'wireClickFn' => 'save',
                    ])
                @endcomponent
            </div>

            <hr class="mt-10 border-gray-300"> 
            <div class="mt-[28px] table-wrapper overflow-visible">
                <h4 class="mb-4 text-lg">Submissions</h4>
                    
                <table class="min-w-full bg-white table-auto">
                    <thead class="">
                        <tr>
                            <th class="th">Title</th>
                            <th class="th">Start Date</th>
                            <th class="th">End Date</th>
                            <th class="th">Peak Price % Increase</th>
                            <th class="th">Weekend Price % Increase</th>
                            <th class="th">Description</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody>
                    
                        @foreach ($data as $peak_season)
                            <tr class="border-t">
                                <td class="td">{{ $peak_season->title }}</td>
                                <td class="td">{{ $peak_season->start_date->format('M d, Y') }}</td>
                                <td class="td">{{ $peak_season->end_date->format('M d, Y') }}</td>
                                <td class="td">{{ $peak_season->peak_price_variation }}</td>
                                <td class="td">{{ $peak_season->weekend_price_variation }}</td>
                                <td class="td">{{ Str::limit($peak_season->description, 20) }}</td>
                                <td class="td">
                                    @component('components.three-dots-trigger', [
                                        'menuItems' => [
                                            ['label' => 'Edit', 'wireFn' => "edit($peak_season->id)"],                                               
                                            ['label' => 'Delete', 'wireFn' => "deletePopUp($peak_season->id)"],
                                        ],
                                    ])
                                    @endcomponent
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {{-- delete pop up --}}
    @if ($popUp == true)
        @component('components.popUpFolder.statusPopUp')
        @slot('content')
            Are you sure you want to delete the record?
        @endslot
        @slot('footer')
            <div class="flex items-center justify-end gap-2 mt-5">
                @component('components.button-component', [
                    'label' => 'Cancel',
                    'id' => 'cancel',
                    'type' => 'cancelSmall',
                    'wireClickFn' => '$set("popUp", false)',
                ])
                @endcomponent

                @component('components.button-component', [
                    'label' => 'Delete',
                    'id' => 'delete',
                    'type' => 'buttonSmall',
                    'wireClickFn' => 'delete',
                ])
                @endcomponent
            </div>
        @endslot
        @endcomponent
    @endif
    {{-- delete pop up end --}}
</main>
