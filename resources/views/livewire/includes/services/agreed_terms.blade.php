<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <!-- Header -->
    <div class="px-6 py-4 border-b bg-gray-200 flex justify-between items-center cursor-pointer" @click="open = !open">
        <h3 class="text-lg font-medium text-gray-900">Agreed Terms</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" 
             :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>

    <!-- Add More Button -->
    <div class="px-6 py-3 border-b flex justify-end">
        @component('components.button-component', [
            'label' => '+ Add More',
            'id' => 'add_more_button',
            'type' => 'submitSmall',
            'wireClickFn' => 'addItem',
        ])
        @endcomponent
    </div>

    <!-- Body -->
    <div x-show="open" x-transition class="px-6 py-6 space-y-6">
        @foreach($items as $index => $item)
            <div class="grid grid-cols-1 md:grid-cols-11 gap-6 border rounded-lg p-4">

                <div class="md:col-span-5">
                <!-- Content -->
                @component('components.textbox-component', [
                    'wireModel' => "items.$index.content",
                    'id' => "items.$index.content",
                    'label' => 'Title',
                    'star' => false,
                    'placeholder' => 'Enter title',
                    'error' => $errors->first("items.$index.content"), 
                ])
                @endcomponent
                </div>


                <div class="md:col-span-5">
                <!-- File Upload -->
                @component('components.file-upload-component', [
                    'wireModel' => "items.$index.document",
                    // 'wireName' => "items[$index][document]",
                    'id' => "items.$index.document",
                    'label' => 'Document',
                    'star' => false,
                    'src' => isset($item['preview']) ? $item['preview'] : null,
                    'error' => $errors->first("items.$index.document"), 
                ])
                @endcomponent
                </div>

                <!-- Remove Button -->
                @if($index > 0)
                    <div class="flex items-center justify-center pt-6">
                        <a class="text-sm cursor-pointer" wire:click="removeItem({{$index}})">
                            <i class="fa fa-trash-can"></i>
                        </a>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
