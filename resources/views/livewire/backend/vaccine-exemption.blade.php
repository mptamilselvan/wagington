<main class="py-10 lg:pl-72 bg-gray-50">

    @push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" type="module"></script>
    <script type="module">
        document.addEventListener("livewire:init", () => {
            // Init select2
            $('#blood_test_id').select2({
                placeholder: "--Select--",
                allowClear: true,
                dropdownCssClass: "select2-dropdown-custom",  // custom dropdown style
                selectionCssClass: "select2-selection-custom"
            });

            // When manually changed by user, update Livewire property
            $('#blood_test_id').on('change', function () {
                let data = $(this).val();
                @this.set('blood_test_id', data);
            });

            Livewire.on("setup-blood-tests", (data) => {
                let bloodTests = data.blood_tests;

                let $select = $('#blood_test_id');
                $select.empty(); // clear old options

                // append new options
                bloodTests.forEach(test => {
                    let option = new Option(test.option, test.value, false, false);
                    $select.append(option);
                });

                $select.trigger('change'); // refresh Select2
            });

            // Listen for Livewire event from PHP
            Livewire.on('set-blood-tests', (data) => {
                // data.blood_test_id is array of IDs
                $('#blood_test_id').val(data.blood_test_id).trigger('change');
            });

            Livewire.on('reset-blood-tests', () => {
                $('#blood_test_id').val(null).trigger('change');
            });
        });
    </script>
    @endpush
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
            'active' => 'admin.vaccine-exemptions',
            'subMenuType' => 'petSettings',
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                @component('components.dropdown-component', [
                'wireModel' => 'species_id',
                'id' => 'species_id',
                'label' => 'Select Species which cannot be vaccinated',
                'star' => true,
                'options' => $species,
                'error' => $errors->first('species_id'),
                'wireChangeFn' => 'changeSpecies()'
                ])
                @endcomponent

                @component('components.select2-dropdown', [
                'wireModel' => 'blood_test_id',
                'id' => 'blood_test_id',
                'label' => 'Required Blood Test',
                'star' => true,
                'options' => $blood_tests,
                'multiple' => true,
                ])
                @endcomponent


            </div>
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
                'type' => 'buttonSmall',
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
                            <th class="text-sm font-normal text-left text-gray-500">Species</th>
                            <th class="text-sm font-normal text-left text-gray-500">Required
                                Blood Test</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $exemptions)
                        <tr class="border-t">
                            <td class="text-sm text-left text-gray-700">{{
                                $exemptions->species->name }}</td>
                            <td class="text-sm text-left text-gray-700">{{
                                $exemptions->blood_test_names_string
                                }}</td>
                            <td class="td">
                                @component('components.three-dots-trigger', [
                                'menuItems' => [
                                ['label' => 'Edit', 'wireFn' => "edit($exemptions->id)"],
                                ['label' => 'Delete', 'wireFn' => "deletePopUp($exemptions->id)"],
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