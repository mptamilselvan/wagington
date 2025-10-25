<div class="">
    @component('components.header', [
        'title' => $title,
        'type' => 'custom',
        'height' =>
            $view || $editId != ''
                ? 'h-[92px] sm:h-[60px] md:h-[74px] lg:h-[90px]'
                : ($this->form == true
                    ? 'h-[60px] lg:h-[72px]'
                    : 'h-[90px] lg:h-[72px]'),
        // 'link'=>( ),
        // 'count'=>($list == true ? : '' )
        'distributionType' => $distributionType,
    ])
        @slot('slot')

            @if ($list == true || $this->form == true)
                <div class="flex {{ $view || $editId != '' ? ' flex-row' : ' flex-col lg:flex-row ' }}  justify-between gap-4 ">

                    <div class="flex items-start justify-between">
                            {{-- heading section - title --}}
                        <div class="flex flex-col items-start lg:items-start md:justify-center">
                            <h1 class=" flex header-title gap-[10px]  space justify-center items-center  lg:mt-0 ">
                                @if ($this->form == true)
                                    <a href="{{ $list == true ? '' : '/campaigns' }}"
                                        class="manrope-500  text-[14px] text-primary-blue hover:text-blue-hover">
                                        <x-icons.arrow.leftArrow class="w-[8px] lg:w-[9px] h-[17px] lg:h-[16px]" />
                                    </a>
                                @endif
                                <span class="inline-block w-fit">
                                    {{ $title }}
                                </span>

                                @if ($list == true)
                                    <span
                                        class="inline-block mt-1  text-primary-blue manrope-600 text-[16px] md:text-[18px] lg:text-[20px]">
                                        @role('Admin')
                                            @if ($search != '')
                                                {{ $campaignCount }}
                                            @elseif ($filter == true)
                                                {{ $campaignCount }}
                                            @else
                                                000
                                            @endif
                                        @endrole

                                        @if (Auth::user()->hasRole('Consultant'))
                                            @if ($search != '')
                                                {{ $campaignCount }}
                                            @elseif ($filter == true)
                                                {{ $campaignCount }}
                                            @else
                                                {{ $consultantcampCount }}
                                            @endif
                                        @endif

                                    </span>
                                @endif
                            </h1>                            
                        </div>

                        {{-- filter section --}}
                        @if ($list == true)
                            <div class="lg:hidden">
                                @component('components.forms.iconButtons', [
                                    'wireClickFn' => 'showFilter',
                                ])
                                @endcomponent
                            </div>
                        @endif

                    </div>

                    {{-- search and filter --}}
                    @if ($list == true)
                        <div class="flex items-center justify-between -mt-3 sm:gap-4 sm:justify-end">

                            @component('components.search', [
                                'placeholder' => 'search',
                                'wireModel' => 'search',
                                'id' => 'search',
                                'debounce' => true,
                            ])
                            @endcomponent

                            <div class="hidden lg:block">
                                @component('components.forms.iconButtons', [
                                    'wireClickFn' => 'showFilter',
                                ])
                                @endcomponent
                            </div>

                            @role('Admin')
                                @component('components.button-component', [
                                    'label' => 'Add Campaign',
                                    'id' => 'list',
                                    'type' => 'submitSmall',
                                    'wireClickFn' => 'showForm',
                                ])
                                @endcomponent
                            @endrole
                        </div>
                    @endif

                    {{-- edit --}}
                    @if ($view || $editId != '')
                        <div class="flex flex-col items-start justify-end md:justify-start md:flow-row ">
                            <div class="flex flex-col items-start justify-center xl:items-center xl:flex-row ">
                                <div
                                    class="inline-block px-2  manrope-500  text-[8px] sm:text-[10px] md:text-[13px]  text-[#595959] uppercase">
                                    REFERRAL LINK :</div>
                                <div>
                                    @component('components.forms.inputWithCopy', [
                                        'id' => 'referralLink',
                                        'onClickFn' => 'copyPassword()',
                                        'value' => Session::get('adminRefCode'),
                                        'classInput' => 'h-[32px] md:h-[38px] truncate pr-10 w-[255px] text-[#374151] form-input text-[12px]
                                                        md:text-[13px]',
                                        'classSvg' => 'w-[16px] h-[16px] md:w-[24px] md:h-[24px]',
                                    ])
                                    @endcomponent
                                </div>
                            </div>
                            @if ($view || $editId != '')
                                <h2
                                    class=" mt-1 w-full  justify-end text-right flex flex-col sm:hidden lg:space-x-[16px] xl:space-x-[25px] xl:px-10">
                                    <div class="header-camp-sub">TOTAL LEADS <span class="text-[#00A5FF]"> : 2000</span> </div>
                                    <div class="header-camp-sub">ASSIGNED LEADS <span class="text-[#38C976]"> : 1000</span>
                                    </div>
                                    <div class="header-camp-sub">UNASSIGNED LEADS <span class="text-[#FF8206]"> : 1000</span>
                                    </div>

                                </h2>
                            @endif
                        </div>
                    @endif


                </div>
            @endif

        @endslot
    @endcomponent

    {{-- header --}}
    {{-- @component('components.header', ['title' => $title, 'type' => 'titleWithSlot', 'link' => $list == true ? '' : '/campaigns', 'count' => $list == true ? 10 : ''])
    @slot('slot')
    @if ($list == true)
    <div class="flex space-x-4">

        @component('components.search', ['placeholder' => 'search', 'wireModel' => 'search', 'id' => 'search', 'debounce' => true])
        @endcomponent

        @component('components.forms.iconButtons', [
    'wireClickFn' => 'showFilter',
])
        @endcomponent


        @component('components.button-component', [
    'label' => 'Add Campaign',
    'id' => 'list',
    'type' => 'submitSmall',
    'wireClickFn' => 'showForm',
])
        @endcomponent
    </div>
    @endif
    @endslot
    @endcomponent


    {{-- alert --}}
    @component('components.alert-component')
    @endcomponent

    <div class="overflow-hidden x-lay w-min-screen">

        {{-- form --}}
        @if ($this->form == true)
            <div class="py-6 mt-[10px]">
                @if ($editId != '')
                    @component('components.subMenu', [
                        'active' => 'campaignDetail',
                        'classNames' => 'top-[160px] sm:top-[130px] md:top-[145px] lg:top-[162px]',
                        'distributionType' => $distributionType,
                        'subMenuType' => $subMenuType,
                        'subMenu' => [],
                    ])
                    @endcomponent
                @endif

                {{-- sm:ml-[230px] md:ml-[250px] --}}
                <div
                    class=" space-y-[30px] @if ($view || $editId != '') ml-[32%]  lg:ml-[293px] @endif lg:w-1/2 px-2">

                    @component('components.textbox-component', [
                        'wireModel' => 'campaignName',
                        'id' => 'campaignName',
                        'label' => 'Campaign Name',
                        'star' => $view ? false : true,
                        'readonly' => $view ? true : false,
                        'error' => $errors->first('campaignName'),
                    ])
                    @endcomponent

                    <div>
                        @if ($view)
                            @component('components.textbox-component', [
                                'wireModel' => 'campaignId',
                                'id' => 'campaignId',
                                'label' => 'Unique Identifer',
                                'readonly' => 'true',
                            ])
                            @endcomponent
                        @endif
                    </div>

                    <div>
                        @component('components.textarea-component', [
                            'wireModel' => 'description',
                            'id' => 'description',
                            'rows' => 5,
                            'label' => 'Description',
                            // 'readonly' => $view ? true : false,
                            'star' => $view ? false : true,
                            'error' => $errors->first('description'),
                        ])
                        @endcomponent
                    </div>

                    @component('components.textbox-prefixcomponent', [
                        'wireModel' => 'campaignDomain',
                        'id' => 'campDomain',
                        'label' => 'Campaign Domain',
                        'star' => $view ? false : true,
                        'readonly' => $view ? true : false,
                        'prefix' => 'https://HappiPacks.com/',
                        'prefixPadding' => '191',
                        'error' => $errors->first('campaignDomain'),
                    ])
                    @endcomponent

                    @if ($view)

                        @component('components.textbox-component', [
                            'wireModel' => 'unsubUrl',
                            'id' => 'unsubUrl',
                            'label' => 'Unsubscription Url',
                            'readonly' => 'true',
                        ])
                        @endcomponent

                    @endif

                    <div class="flex w-full flex-col sm:flex-row gap-2  md:gap-12  xl:gap-[72px]  ">
                        <div class="w-full">
                            @component('components.date-component', [
                                'wireModel' => 'startDate',
                                'id' => 'startDate',
                                'label' => 'Start Date',
                                'star' => $view ? false : true,
                                'readonly' => $view ? true : false,
                                'error' => $errors->first('startDate'),
                            ])
                            @endcomponent
                        </div>

                        <div class="w-full">
                            @component('components.date-component', [
                                'wireModel' => 'endDate',
                                'id' => 'endDate',
                                'label' => 'End Date',
                                'star' => $view ? false : true,
                                'readonly' => $view ? true : false,
                                'error' => $errors->first('endDate'),
                            ])
                            @endcomponent
                        </div>

                    </div>
                    <div class="flex w-full gap-2  md:gap-6  xl:gap-[42px]">
                        <div class="w-full">
                            @component('components.textbox-component', [
                                'wireModel' => 'referralNo',
                                'id' => 'referralNo',
                                'star' => 'true',
                                'label' => 'No: of Referrals',
                                'star' => $view ? false : true,
                                'readonly' => $view ? true : false,
                                'error' => $errors->first('referralNo'),
                            ])
                            @endcomponent

                        </div>
                        <div class="flex items-end text-[#171717] align-middle">
                            =
                        </div>
                        <div class="w-full">
                            @component('components.textbox-component', [
                                'wireModel' => 'referralRewards',
                                'id' => 'referralRewards',
                                'star' => 'true',
                                'label' => 'Referral Rewards',
                                'star' => $view ? false : true,
                                'readonly' => $view ? true : false,
                                'error' => $errors->first('referralRewards'),
                            ])
                            @endcomponent
                        </div>

                    </div>

                    @component('components.textbox-component', [
                        'wireModel' => 'priceLead',
                        'id' => 'priceLead',
                        'label' => 'Price per Lead',
                        'star' => $view ? false : true,
                        'readonly' => $view ? true : false,
                        'error' => $errors->first('priceLead'),
                    ])
                    @endcomponent

                    @component('components.textbox-component', [
                        'wireModel' => 'priceReplacement',
                        'id' => 'priceReplacement',
                        'label' => 'Price per Replacement',
                        'star' => $view ? false : true,
                        'readonly' => $view ? true : false,
                        'error' => $errors->first('priceReplacement'),
                    ])
                    @endcomponent

                    @component('components.dropdown-component', [
                        'label' => 'Lead Distribution',
                        'wireModel' => 'leadDistribution',
                        'id' => 'leadDistribution',
                        'options' => [['value' => '0', 'option' => 'Centralized'], ['value' => '1', 'option' => 'Distributed']],
                        'disabled' => $editId != '' ? true : false,
                        'star' => $view ? false : true,
                        'error' => $errors->first('leadDistribution'),
                    ])
                    @endcomponent

                    <br />

                    <label class="font-medium text-gray-500">Assign Consultants</label>
                    <div class="flex flex-row gap-4">


                        @component('components.dropdown-component', [
                            'optionValue' => 'Consultant',
                            'wireModel' => 'sortValue',
                            'id' => 'sortValue',
                            'disabled' => $view ? true : false,
                            'options' => [],
                            'wireChangeFn' => 'getPlaceHolderText',
                        ])
                        @endcomponent

                        @component('components.search', [
                            'placeholder' => $SearchplaceHoder,
                            'wireModel' => 'sortSearch',
                            'id' => 'sortSearch',
                            'readonly' => $view ? true : false,
                            'debounce' => true,
                        ])
                        @endcomponent

                    </div>
                    <br />

                    <br />
                    <div class="flex justify-start gap-6 pl-1 xl:gap-[72px] ">
                        <label class="label">
                            Published
                        </label>

                        <div>
                            @component('components.checkbox-toggle', [
                                'title' => 'Published',
                                'name' => '',
                                'wireModel' => 'published',
                                'id' => 'published',
                                'disable' => $view ? true : false,
                                'value' => '1',
                            ])
                            @endcomponent

                        </div>
                    </div>


                    <div class="flex justify-end gap-6 xl:gap-[72px] items-center ">
                        @if ($view)

                            @component('components.button-component', [
                                'label' => 'edit',
                                'id' => 'edit',
                                'type' => 'cancelSmall',
                                'wireClickFn' => 'editCampaignDetails',
                            ])
                            @endcomponent

                            {{-- @component('components.button-component', [
                                'label' => 'Delete',
                                'id' => 'delete',
                                'type' => 'cancelSmall',
                                'wireClickFn' => 'deletePopUp',
                            ])
                                                @endcomponent --}}
                        @else
                            @component('components.button-component', [
                                'label' => 'Cancel',
                                'id' => 'cancel',
                                'type' => 'cancelSmall',
                                'wireClickFn' => 'cancel',
                            ])
                            @endcomponent

                            @component('components.button-component', [
                                'label' => 'Save',
                                'id' => 'save',
                                'type' => 'submitSmall',
                                'wireClickFn' => 'submit',
                            ])
                            @endcomponent

                        @endif
                    </div>
                </div>
            </div>
        @endif


        <div>
            {{-- listing --}}
            @if ($this->list == true)

                {{-- filter --}}
                @if ($this->openFilter == true)
                    @component('components.filter', [
                        'wireClickCloseFn' => 'closeFilter',
                        'resetFilter' => 'resetFilter',
                        'applyFilter' => 'applyFilter',
                    ])
                        @slot('dataSlot')
                            <div href="helper-view" class="flex flex-col space-y-[30px] pt-5">

                                <div class="px-[20px] md:px-[23px] lg:px-[33px]">
                                    @component('components.checkbox-component', [
                                        'title' => 'status',
                                        'name' => 'filterStatus',
                                        'options' => [['id' => 0, 'name' => 'Inactive'], ['id' => 1, 'name' => 'Active']],
                                        'type' => 'multiple-checkbox',
                                        'class' => 'flex flex-row space-x-8',
                                        'defer' => true,
                                        'filter' => true,
                                        'wireClickFn' => 'setOnlyChecked',
                                    ])
                                    @endcomponent
                                </div>

                            </div>
                        @endslot
                    @endcomponent
                @endif


                <div class="mt-[28px] table-wrapper">
                    <table class="table">
                        <thead class="bg-[#F9FAFB]">
                            <th class="th">CAMPAIGN NAME</th>
                            <th class="th">START DATE</th>
                            <th class="th">END DATE</th>
                            <th class="th">STATUS</th>
                            <th class="th">TOTAL LEADS</th>
                            <th class="th">UNASSIGNED LEADS</th>
                            <th class="th">ASSIGNED LEADS</th>
                            <th class="th"></th>
                            <th class="th"></th>
                        </thead>
                        <tbody>
                            @foreach ($campaigns as $campaign)
                                @if (($campaign->show_inactive == 1 && Auth::user()->hasRole('Consultant')) || Auth::user()->hasRole('Admin'))
                                    <tr>
                                        <td class="td">
                                            {{ ucfirst($campaign->name) }}
                                        </td>
                                        <td class="td">
                                            {{ date('d/m/Y', strtotime($campaign->start_date)) }}
                                        </td>
                                        <td class="td">
                                            {{ date('d/m/Y', strtotime($campaign->end_date)) }}
                                        </td>
                                        <td class="td">
                                            @component('components.badge', [
                                                'type' => $campaign->is_published == 1 ? 'active' : 'inactive',
                                            ])
                                            @endcomponent
                                        </td>
                                        <td class="td">{{ $campaign->totalLeads }}</td>
                                        <td class="td">{{ $campaign->totalLeads - $campaign->assignedLeads }}</td>
                                        <td class="td">{{ $campaign->assignedLeads }}</td>
                                        <td class="td">
                                            @if ($campaign->referral_code == '')
                                                <a class="cursor-pointer text-primary-blue"
                                                    wire:click="enrollToCampaign({{ $campaign->id }})">Enroll</a>
                                            @else
                                                <span clas="text-primary-blue">{{ $campaign->referral_code }}</span>
                                            @endif
                                        </td>
                                        <td class="td hover:text-blue-hover">
                                            @role('Admin')
                                                <a href="#"><svg
                                                        wire:click='showCampaignDetails({{ $campaign->id }})'
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="w-6 h-6">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                    </svg>
                                                </a>
                                            @else
                                                <a href="{{ 'leads/user/' . $campaign->id }}"><svg
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="w-6 h-6">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                    </svg>
                                                </a>
                                            @endrole
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>

                </div>

                <div class="">
                    {{-- {{ $campaigns->onEachSide(1)->links() }} --}}
                </div>
            @endif
        </div>




        {{-- delete pop up --}}
        @if ($popUp == true)
            @component('components.popUpFolder.statusPopUp')
                @slot('content')
                    <div class="justify-center items-center flex-auto p-[20px] md:p-[60px] text-center">

                        <h2 class="manrope-600 text-[20px] md:text-[24px] text-[#232323] ">Are you sure you want to
                            delete the campaign</h2>
                    </div>
                @endslot
                @slot('footer')
                    <div
                        class=" h-[97px] flex gap-3 sm:gap-6 md:gap-10 justify-center items-center  mt-2 rounded-b-[20px]  bg-[#FAFAFA] ">
                        <button wire:click='cancelPopUp' class="text-button-green">
                            Cancel
                        </button>

                        <button wire:click='deleteCampaignDetails' class="button-primary-red-small">
                            Delete
                        </button>
                    </div>
                @endslot
            @endcomponent
        @endif
        {{-- delete pop up end --}}

    </div>

    <script>
        function copyPassword() {
            let copyPwd = document.getElementById("referralLink");
            copyPwd.select();
            copyPwd.setSelectionRange(0, 99999); // For mobile devices
            navigator.clipboard.writeText(copyPwd.value);
            swal({
                icon: "success",
                title: "Copied!",

            });
        }

        function upload() {
            let button = document.getElementById('hiddenImageUpload')
            button.click();
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"
        integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
    <script>
        $("#description").attr("readonly", true);
        window.addEventListener('desc-textarea-readonly', event => { //alert(event.detail.value);
            if (event.detail.value == false) {
                $("#description").attr("readonly", false);
            }
            if (event.detail.value == true) {
                $("#description").attr("readonly", true);
            }
        })
    </script>
</div>
