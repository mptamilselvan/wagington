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
                'active' => 'room-off-days',
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
                </div>
                <div>
                 @component('components.textarea-component', [
                    'wireModel' => 'reason',
                    'id' => 'reason',
                    'rows' => 7,
                    'label' => 'Reason',
                    'star' =>false,
                    'placeholder' => 'Type here...',
                    'error' => $errors->first('reason'),
                    ])
                    @endcomponent
                </div>
                <div class="mt-2">
                        @component('components.textbox-component', [
                            'wireModel' => 'off_day_price_variation',
                            'id' => 'off_day_price_variation',
                            'label' => 'Off Day Price % Increase',
                            'star' => true,
                            'placeholder' => 'Off Day Price % Increase',
                            'error' => $errors->first('off_day_price_variation'),
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
                            <th class="th">Off Day Price % Increase</th>
                            <th class="th">Description</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody>
                    
                        @foreach ($data as $day)
                            <tr class="border-t">
                                <td class="p-2">{{ $day->title }}</td>
                                <td class="p-2">{{ $day->start_date->format('M d, Y') }}</td>
                                <td class="p-2">{{ $day->end_date->format('M d, Y') }}</td>
                                <td class="p-2">{{ $day->off_day_price_variation }}</td>
                                <td class="p-2">{{ Str::limit($day->reason, 20) }}</td>
                                <td class="flex gap-2 p-2">
                                    @component('components.three-dots-trigger', [
                                        'menuItems' => [
                                            ['label' => 'Edit', 'wireFn' => "edit($day->id)"],                                               
                                            ['label' => 'Delete', 'wireFn' => "deletePopUp($day->id)"],
                                        ],
                                    ])
                                    @endcomponent
                                    {{-- <button wire:click="edit({{ $day->id }})" class="text-blue-600 hover:underline">Edit</button>
                                    <button wire:click="deletePopUp({{ $day->id }})" class="text-red-600 hover:underline">Delete</button> --}}
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
