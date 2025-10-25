<main class="{{ $firstSegment === 'admin' ? 'py-10 lg:pl-72 bg-gray-50' : 'min-h-screen bg-white relative' }}">
    <div class="w-full {{ $firstSegment === 'customer' ? 'px-4 sm:px-6 lg:px-8 py-8':'' }}">
        <div class="{{ $firstSegment === 'customer' ? 'bg-gray-50 border border-gray-200 rounded-lg shadow-sm p-6 sm:p-8' : '' }}">
    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="items-center h-20 md:flex">
            <!-- Back link -->
            <a href="{{route($firstSegment.'.pets') }}"
            class="inline-flex items-center justify-center rounded-full p-1.5 text-primary-blue hover:bg-gray-100 hover:text-blue-hover transition">
                <x-icons.arrow.leftArrow class="w-4 h-4" />
                {{-- <span class="pl-4 text-xl font-semibold text-black"> Medical History </span> --}}
                <span class="sr-only">Back</span>
            </a>

            <!-- Title -->
            <h1 class="px-2 text-xl font-semibold text-gray-900 dark:text-white sm:tracking-tight">
                {{ $title }}
            </h1>
        </div>
         {{-- <span class="pl-10 text-base font-normal text-gray-500">Add your pet's medical history. </span> --}}
    </div>

    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">
        <div class="w-1/5 p-4 bg-white">
            @component('components.subMenu', [
                'active' => 'medical-history-records',
                'subMenuType' => 'petAdmin',
                'firstSegment' => $firstSegment,
                'pet_id' => $pet_id,
                'customer_id' => $customer_id,
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <div>
                        @component('components.textbox-component', [
                            'wireModel' => 'name',
                            'placeholder' => 'Enter name',
                            'id' => 'name',
                            'label' => 'Name',
                            'star' => true,
                            'error' => $errors->first('name'),
                        ])
                        @endcomponent
                    </div>
                    <div class="mt-6">
                        @component('components.file-upload-component', [
                            'wireModel' => 'document',
                            'id' => 'document',
                            'label' => 'File',
                            'star' => true,
                            'src' => $src,
                            'error' => $errors->first('document'),
                            ])
                        @endcomponent
                    </div>
                </div>
                <div>
                    @component('components.textarea-component', [
                        'wireModel' => 'notes',
                        'id' => 'notes',
                        'rows' => 5,
                        'label' => 'Add Note',
                        'placeholder' => 'Type here...',
                        'star' =>false,
                        'error' => $errors->first('notes'),
                        ])
                    @endcomponent
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-10">
                @component('components.button-component', [
                    'label' => 'Cancel',
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
            <div class="mt-[28px] table-wrapper hr">
                <h4 class="mb-4 text-lg">Submissions</h4>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($data as $medical_history)
                    <div class="flex flex-col gap-4 p-5 bg-white shadow rounded-xl">
                        <!-- Header -->
                        <div class="flex items-center gap-3">
                            {!! documentPreview($medical_history->document)!!}
                            {{-- <img src="{{ documentPreview($medical_history->document) }}" alt="Document Preview" class="object-cover w-12 h-12 rounded"> --}}
                            <h2 class="text-lg font-semibold text-gray-800">{{ $medical_history->name }}</h2>

                            <div class="flex items-center gap-3 ml-auto text-gray-500">
                                <!-- Download -->
                                <button class="hover:text-gray-500" wire:click='download("{{ $medical_history->id}}")'>
                                    <i class="fa-solid fa-download"></i>
                                </button>
                                <!-- Edit -->
                                <button class="hover:text-gray-500" wire:click="edit({{ $medical_history->id }})">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <!-- Delete -->
                                <button class="hover:text-gray-500" wire:click="deletePopUp({{ $medical_history->id }})">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="text-sm leading-relaxed text-gray-500">
                            {{ $medical_history->notes }}
                        </p>

                        <!-- Submission Date -->
                        <p class="text-xs text-gray-400">
                            Submission date : {{ $medical_history->created_at }}
                        </p>
                    </div>
                    @endforeach
                </div>
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
        </div>
    </div>
</main>