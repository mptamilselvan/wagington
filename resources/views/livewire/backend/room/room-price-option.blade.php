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
                'active' => 'room-price-options',
                'subMenuType' => 'roomSettings',
            ])
            @endcomponent
        </div>
        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <div class="mb-2">
                        @component('components.dropdown-component', [
                            'wireModel' => 'room_type_id',
                            'id' => 'roomTypeId',
                            'label' => 'Room Type',
                            'star' => true,
                            'error' => $errors->first('room_type_id'),
                            'options' => $roomTypes,
                            'optionValue' => 'Select Room Type',
                            'isDefer' => true,
                            'wireChangeFn' => 'changedRoomType',
                        ])
                        @endcomponent
                    </div>
                    <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'label',
                            'id' => 'title',
                            'label' => 'Label',
                            'star' => true,
                            'placeholder' => 'Enter label',
                            'error' => $errors->first('title'),
                            'disabled' => true,
                        ])
                        @endcomponent
                    </div>
                    <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'no_of_days',
                            'id' => 'noOfDays',
                            'label' => 'No. of Days',
                            'star' => true,
                            'error' => $errors->first('no_of_days'),
                        ])
                        @endcomponent
                    </div>
                    <div class="mt-3">
                        @component('components.textbox-component', [
                            'wireModel' => 'price',
                            'id' => 'price',
                            'label' => 'Price',
                            'star' => true,
                            'error' => $errors->first('price'),
                        ])
                        @endcomponent
                    </div>

                    <div class="mt-3">
                        @component('components.dropdown-component', [
                            'wireModel' => 'pet_size_id',
                            'id' => 'petSizeId',
                            'label' => 'Pet Size',
                            'star' => true,
                            'error' => $errors->first('pet_size_id'),
                            'options' => $petSizes,
                            'optionValue' => 'All',
                        ])
                        @endcomponent
                    </div>


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
                @php
                    $selectedRoomTypeName = null;
                    if (!empty($room_type_id) && isset($roomTypes) && is_array($roomTypes)) {
                        $match = collect($roomTypes)->firstWhere('value', (int) $room_type_id);
                        $selectedRoomTypeName = $match['option'] ?? null;
                    }
                @endphp
                <h4 class="mb-4 text-lg">Submissions @if ($selectedRoomTypeName)
                        <span class="text-gray-500">— {{ $selectedRoomTypeName }}</span>
                    @endif
                </h4>

                <table class="min-w-full bg-white table-auto">
                    <thead class="">
                        <tr>
                            <th class="th">Label</th>
                            <th class="th">No. of Days</th>
                            <th class="th">Price</th>
                            <th class="th">Pet Size</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($data as $roomPriceOption)
                            <tr class="border-t">
                                <td class="td">{{ $roomPriceOption->label }}</td>
                                <td class="td">{{ $roomPriceOption->no_of_days }}</td>
                                <td class="td">{{ $roomPriceOption->price }}</td>
                                <td class="td">{{ $roomPriceOption->petSize->name ?? 'N/A' }}</td>
                                <td class="td">
                                    @component('components.three-dots-trigger', [
                                        'menuItems' => [
                                            ['label' => 'Edit', 'wireFn' => "edit($roomPriceOption->id)"],
                                            ['label' => 'Delete', 'wireFn' => "deletePopUp($roomPriceOption->id)"],
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
