<main class="py-10 lg:pl-72 bg-gray-50">

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" type="module"></script>
    <script type="module">
        document.addEventListener("livewire:init", () => {
            // Listen for Livewire event from PHP
            Livewire.on('set-customer', (data) => {
                setTimeout(() => {
                    $('#selected_customer_ids').select2({
                        placeholder: "Select Customer",
                        allowClear: true,
                        dropdownCssClass: "select2-dropdown-custom",
                        selectionCssClass: "select2-selection-custom"
                    });

                    $('#selected_customer_ids').on('change', function () {
                        let data = $(this).val();
                        @this.set('selected_customer_ids', data);
                    });

                    if(data){
                        $('#selected_customer_ids').val(data.selected_customer_ids).trigger('change');
                    }
                }, 50);
            });

            Livewire.on('reset-blood-tests', () => {
                $('#selected_customer_ids').val(null).trigger('change');
            });
        });
    </script>
@endpush

    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="h-20 md:flex md:items-center md:justify-between">
            @if ($this->form == true)
                <div class="flex items-center gap-2">
                    <!-- Back link -->
                    <a href="{{ $list == true ? '' : route('admin.marketingcampaign') }}"
                    class="inline-flex items-center justify-center rounded-full p-1.5 text-primary-blue hover:bg-gray-100 hover:text-blue-hover transition">
                        <x-icons.arrow.leftArrow class="w-4 h-4" />
                        <span class="sr-only">Back</span>
                    </a>

                    <!-- Title -->
                    <h1 class="px-2 text-xl font-semibold text-gray-900 dark:text-white sm:tracking-tight">
                        {{ $title }}
                    </h1>
                </div>
            @endif
            @if ($list == true)
                <span class="text-xl font-medium text-gray-900 sm:truncate sm:tracking-tight">{{ $title }}</span>
                <div class="flex items-center justify-between -mt-3 sm:gap-4 sm:justify-end">
                    {{-- @component('components.search', [
                        'placeholder' => 'Search name',
                        'wireModel' => 'searchcampaing',
                        'id' => 'search',
                        'debounce' => true,
                    ])
                    @endcomponent --}}

                    {{-- @role('Admin') --}}
                    @component('components.button-component', [
                    'label' => 'Add',
                    'id' => 'list',
                    'type' => 'buttonSmall',
                    'wireClickFn' => 'showForm',
                    ])
                    @endcomponent
                    {{-- @endrole --}}
                </div>
            @endif
        </div>
    </div>


    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form --}}
    <div class="flex gap-3 mx-3 my-3">
        <div class="w-full p-4 bg-white">
            @if($form == true)
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Name --}}
                    @component('components.textbox-component', [
                        'wireModel' => 'name',
                        'id' => 'name',
                        'label' => 'Name',
                        'star' => true,
                        'error' => $errors->first('name'),
                    ]) @endcomponent

                    {{-- Promo Code --}}
                    @component('components.textbox-component', [
                        'wireModel' => 'promo_code',
                        'id' => 'promo_code',
                        'label' => 'Promo Code',
                        'star' => false,
                        'error' => $errors->first('promo_code'),
                    ]) @endcomponent

                    {{-- Description --}}
                    @component('components.textarea-component', [
                        'wireModel' => 'description',
                        'id' => 'description',
                        'rows' => 9,
                        'label' => 'Description',
                        'star' => false,
                        'error' => $errors->first('description'),
                    ]) @endcomponent

                    {{-- Terms and Conditions --}}
                    <div wire:ignore  x-data="{ content: @entangle('terms_and_conditions') }"
                        x-init="
                            ClassicEditor
                                .create($refs.editor)
                                .then(editor => {
                                    editor.model.document.on('change:data', () => {
                                        content = editor.getData();
                                    });
                                })
                                .catch(error => {
                                    console.error(error);
                                });
                        "
                    ><label for="terms_and_conditions" class="block mb-2 text-gray-700">Terms and Conditions</label>
                        <div x-ref="editor">{!! $terms_and_conditions !!}</div>
                    </div>
                    {{-- @component('components.textarea-component', [
                        'wireModel' => 'terms_and_conditions',
                        'id' => 'terms_and_conditions',
                        'rows' => 3,
                        'label' => 'Terms and Conditions',
                        'star' => false,
                        'error' => $errors->first('terms_and_conditions'),
                    ]) @endcomponent --}}

                    {{-- Valid From Date --}}
                    @component('components.date-component', [
                        'wireModel' => 'valid_from_date',
                        'id' => 'valid_from_date',
                        'label' => 'Valid from date',
                        'type' => 'date',
                        'star' => true,
                        'error' => $errors->first('valid_from_date'),
                        'min' => date('Y-m-d'),
                        'wireChangeFn' => 'changeValidFromDate()'
                    ]) @endcomponent

                    {{-- Valid From Time --}}
                    @component('components.time-component', [
                        'wireModel' => 'valid_from_time',
                        'id' => 'valid_from_time',
                        'label' => 'Valid from time',
                        'type' => 'time',
                        'star' => true,
                        'error' => $errors->first('valid_from_time'),
                    ]) @endcomponent

                    {{-- Valid Till Date --}}
                    @component('components.date-component', [
                        'wireModel' => 'valid_till_date',
                        'id' => 'valid_till_date',
                        'label' => 'Valid till date',
                        'type' => 'date',
                        'star' => true,
                        'error' => $errors->first('valid_till_date'),
                        'min' => $valid_from_date
                    ]) @endcomponent

                    {{-- Valid Till Time --}}
                    @component('components.time-component', [
                        'wireModel' => 'valid_till_time',
                        'id' => 'valid_till_time',
                        'label' => 'Valid till time',
                        'type' => 'time',
                        'star' => true,
                        'error' => $errors->first('valid_till_time'),
                    ]) @endcomponent

                    {{-- Discount Type --}}
                    @component('components.dropdown-component', [
                        'wireModel' => 'discount_type',
                        'id' => 'discount_type',
                        'label' => 'Discount Type',
                        'star' => true,
                        'options' => [
                            ['value' => 'percentage', 'option' => 'Percentage'],
                            ['value' => 'amount', 'option' => 'Amount'],
                        ],
                        'error' => $errors->first('discount_type'),
                    ]) @endcomponent

                    {{-- Discount Value --}}
                    @component('components.texbox-number-component', [
                        'wireModel' => 'discount_value',
                        'id' => 'discount_value',
                        'label' => 'Discount Value',
                        'type' => 'number',
                        'star' => true,
                        'min' => 0,
                        'error' => $errors->first('discount_value'),
                    ]) @endcomponent
                    

                    {{-- Usage Type --}}
                    @component('components.dropdown-component', [
                        'wireModel' => 'usage_type',
                        'id' => 'usage_type',
                        'label' => 'Usage Type',
                        'star' => true,
                        'options' => [
                            ['value' => 'single_use', 'option' => 'Single Use'],
                            ['value' => 'multiple_use', 'option' => 'Multiple Use'],
                            ['value' => 'unlimited', 'option' => 'Unlimited'],
                        ],
                        'wireChangeFn' => 'changeUsageType()',
                        'error' => $errors->first('usage_type'),
                    ]) @endcomponent
                    
                    @if($usage_type == 'multiple_use')
                        {{-- Customer Usage Limit --}}
                        @component('components.texbox-number-component', [
                            'wireModel' => 'customer_usage_limit',
                            'id' => 'customer_usage_limit',
                            'label' => 'Customer Usage Limit',
                            'type' => 'number',
                            'star' => true,
                            'min' => 1,
                            'error' => $errors->first('customer_usage_limit'),
                        ]) @endcomponent
                    @endif

                    

                    {{-- Coupon Validity --}}
                    @component('components.textbox-component', [
                        'wireModel' => 'coupon_validity',
                        'id' => 'coupon_validity',
                        'label' => 'Coupon Validity (days)',
                        'type' => 'number',
                        'star' => true,
                        'error' => $errors->first('coupon_validity'),
                    ]) @endcomponent

                    {{-- Stackable Option --}}
                    @component('components.dropdown-component', [
                        'wireModel' => 'stackable',
                        'id' => 'stackable',
                        'label' => 'Stackable Option',
                        'star' => true,
                        'options' => [
                            ['value' => 'yes', 'option' => 'Yes'],
                            ['value' => 'no', 'option' => 'No'],
                        ],
                        'error' => $errors->first('stackable'),
                    ]) @endcomponent

                    {{-- Customers --}}
                    @component('components.dropdown-component', [
                        'wireModel' => 'customer_type',
                        'id' => 'customer_type',
                        'label' => 'customer type',
                        'star' => true,
                        'wireChangeFn' => 'changeCustomerType()',
                        'options' => [
                            ['value' => 'all', 'option' => 'All Customers'],
                            ['value' => 'new', 'option' => 'Newly Onboarded Customers'],
                            ['value' => 'selected', 'option' => 'Selected Customers'],
                        ],
                        'error' => $errors->first('customer_type'),
                    ]) 
                    @endcomponent

                    @if($customer_type == "selected")
                        @component('components.select2-dropdown', [
                            'wireModel' => 'selected_customer_ids',
                            'id' => 'selected_customer_ids',
                            'label' => 'select Customers',
                            'star' => true,
                            'options' => $customers,
                            'multiple' => true,
                        ])
                        @endcomponent
                    @endif

                    @if($customer_type == "new")
                        @component('components.dropdown-component', [
                        'wireModel' => 'new_customer_days',
                        'id' => 'new_customer_days',
                        'label' => 'Newly Onboarded Customers Days',
                        'star' => true,
                        'options' => [
                            ['value' => '7', 'option' => 'Last 7 Days'],
                            ['value' => '15', 'option' => 'Last 15 Days'],
                            ['value' => '30', 'option' => 'Last 30 Days'],
                            ['value' => '60', 'option' => 'Last 60 Days'],
                        ],
                        'error' => $errors->first('new_customer_days'),
                    ]) @endcomponent
                    @endif
                </div>

                {{-- Publish toggle --}}
                <div class="mt-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="published" class="rounded">
                        <span class="text-sm text-gray-700">Publish promotion (Only published promotions can be used by users)</span>
                    </label>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center justify-end gap-2 mt-10">
                    @component('components.button-component', [
                        'label' => 'Clear',
                        'id' => 'clear',
                        'type' => 'cancelSmall',
                        'wireClickFn' => 'resetFields',
                    ]) @endcomponent

                    @component('components.button-component', [
                        'label' => 'Save',
                        'id' => 'save',
                        'type' => 'buttonSmall',
                        'wireClickFn' => 'save',
                    ]) @endcomponent
                </div>
            @endif

            @if ($this->list == true)
                <div class="mt-[28px] table-wrapper overflow-visible">
                    <table class="min-w-full bg-white table-auto">
                        <thead class="bg-[#F9FAFB]">
                            <th class="th">Name</th>
                            <th class="th">Promo code</th>
                            <th class="th">Valid from</th>
                            <th class="th">Vaild till</th>
                            <th class="th">Discount</th>
                            <th class="th">Published</th>
                            <th class="th"></th>
                        </thead>
                        <tbody>
                            @foreach ($data as $campaing)
                            <tr>
                                <td class="td">
                                    {{ ucfirst( $campaing->name ) }}
                                </td>
                                <td class="td">
                                    {{ $campaing->promo_code }}
                                </td>
                                <td class="td">
                                    {{ $campaing->valid_from->format('M d, Y H:i A') }}
                                </td>
                                <td class="td">
                                    {{ $campaing->valid_till->format('M d, Y H:i A') }}
                                </td>
                                <td class="td">
                                @if($campaing->marketingCampaign)
                                    {{ $campaing->marketingCampaign->discount_value }} {{ $campaing->marketingCampaign->discount_type == 'percentage'?'%':'SGD' }}
                                @endif
                                </td>
                                <td class="td">
                                    @if($campaing->published == 1)
                                        <i class="w-5 h-5 p-1 text-white bg-green-500 rounded-full fa-solid fa-check"></i>
                                    @else
                                        <i class="w-5 h-5 p-1 text-white bg-red-500 rounded-full fa-solid fa-close"></i>
                                    @endif
                                </td>
                                <td class="td">
                                    @component('components.three-dots-trigger', [
                                        'menuItems' => [
                                            ['label' => 'Edit', 'wireFn' => "edit($campaing->id)"],                                               
                                            ['label' => 'Delete', 'wireFn' => "deletePopUp($campaing->id)"],
                                        ],
                                    ])
                                    @endcomponent
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- {{ $data->links() }} --}}
                </div>
            @endif
           
        
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
    @push('css')
<style>
    /* Less specific selector (may be overridden) */
.ck-editor {
    border-radius: 8px;
}

/* More specific selector (more likely to be applied) */
    .ck-editor__editable_inline {
        min-height: 190px; /* Set editor height */
        
    }
    .ck-content ol {
        list-style-type: decimal;
        padding-left: 20px;
    }

    .ck-content ul {
        list-style-type: disc;
        padding-left: 20px;
    }
</style>
@endpush
@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js" type="module"></script>
    
@endpush

</main>
