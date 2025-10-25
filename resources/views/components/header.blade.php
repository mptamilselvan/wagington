@props(['title', 'count', 'height', 'link' => ''])

@if ($type == 'titleOnly')
    <div
        class="bg-gray-backdrop w-full x-lay sticky z-20 inset-0 h-[60px] lg:h-[72px] top-[60px] lg:top-[64px]  lg:py-4 ">
        <div class="flex flex-row justify-between w-full">
            <div>
                <h1
                    class="text-gray flex justify-center items-center gap-[16px] md:gap-[20px] lg:gap-[25px] mt-3 lg:mt-0 font-bold   text-[#060D01] text-2xl lg:text-[32px] ">
                    @if (strlen($link) > 1)
                        <a href="{{ $link }}"
                            class="font-medium text-[14px] text-primary-blue hover:text-blue-hover">
                            <x-icons.arrow.leftArrow class="w-[9px] h-[16px]" />
                        </a>
                    @endif
                    {{ $title }}
                </h1>
            </div>
        </div>
    </div>
@endif

@if ($type == 'titleWithSlot')
    <div class="bg-gray-backdrop x-lay sticky z-20 inset-0 h-[100px] lg:h-[72px] top-[60px] lg:top-[64px]  lg:py-4 ">

        <div class="flex flex-col justify-between w-full lg:flex-row">
            <div class="flex items-center lg:justify-center">
                <h1
                    class="text-gray flex  gap-[16px] md:gap-[20px] lg:gap-[25px] space justify-center items-center mt-3 lg:mt-0 font-bold   text-[#060D01] text-2xl lg:text-[32px]">
                    @if (strlen($link) > 1)
                        <a href="{{ $link }}"
                            class="font-medium text-[14px] text-primary-blue hover:text-blue-hover">
                            <x-icons.arrow.leftArrow class="w-[9px] h-[16px]" />
                        </a>
                    @endif
                    {{ $title }}
                </h1>
                @isset($count)
                    <span
                        class="inline-block mt-1 px-3 text-primary-blue font-semibold text-[16px] md:text-[18px] lg:text-[20px]">
                        {{ $count }}
                    </span>
                @endisset

            </div>

            <div class="">
                {{ $slot }}
            </div>
        </div>
    </div>

@endif

@if ($type == 'custom')
    <div
        class="bg-gray-backdrop  x-lay sticky z-20 inset-0 @if (isset($height)) {{ $height }} @else  h-[84px] lg:h-[72px] @endif  top-[60px] lg:top-[64px]  lg:py-4 ">
        <div class="">
            {{ $slot }}
        </div>
    </div>
@endif

@if ($type == 'campaignEdit')
    <div
        class="bg-gray-backdrop  x-lay sticky z-20 inset-0 @if (isset($height)) {{ $height }} @else  h-[92px] sm:h-[60px] md:h-[74px] lg:h-[90px] @endif  top-[60px] lg:top-[64px]  lg:py-4 ">
        <div class="flex flex-row justify-between gap-4 ">

            <div class="flex items-start justify-between">

                <div class="flex flex-col items-start lg:items-start md:justify-center">
                    <h1
                        class=" flex header-title gap-[10px]  md:gap-[20px] lg:gap-[25px] space justify-center items-center  lg:mt-0 ">

                        <a href="{{ $link }}"
                            class="font-medium  text-[14px] text-primary-blue hover:text-blue-hover">
                            <x-icons.arrow.leftArrow class="w-[8px] lg:w-[9px] h-[17px] lg:h-[16px]" />
                        </a>

                        <span class="inline-block w-fit">
                            {{ $title }}
                        </span>
                        <div class="">


                            @component('components.badge', ['type' => $status ? 'active' : 'inactive'])
                            @endcomponent


                        </div>


                    </h1>
                    {{-- to show  leads count in header --}}

                    @component('components.lead-count-component',[
                        'distributionType' => $distributionType
                    ])
                    @endcomponent


                </div>

            </div>

            {{-- edit --}}

            <div class="flex flex-col items-start justify-end md:justify-start md:flow-row ">
                <div class="flex flex-col items-start justify-center xl:items-center xl:flex-row ">
                    <div
                        class="inline-block px-2  font-medium  text-[8px] sm:text-[10px] md:text-[13px]  text-[#595959] uppercase">
                        REFERRAL LINK :</div>
                    <div class="flex flex-row space-x-4">
                        @component('components.forms.inputWithCopy', [
                            'id' => $id,
                            'onClickFn' => $copyString,
                            'value' => $value,
                            'classInput' => 'h-[32px] md:h-[38px] truncate pr-10 w-[255px] text-[#374151] form-input text-[12px]
                                                                                                                                                                                                                                                                                                                    md:text-[13px]',
                            'classSvg' => 'w-[16px] h-[16px] md:w-[24px] md:h-[24px]',
                        ])
                        @endcomponent
                        @if (isset($leadReqBtn) && $distributionType==0)
                            @component('components.button-component', [
                                'label' => 'Request Lead',
                                'id' => 'submit',
                                'type' => 'submitSmall',
                                'wireClickFn' => 'showLeadRequest',
                            ])
                            @endcomponent
                        @endif
                    </div>

                </div>

                {{-- <h2
                    class=" mt-1 w-full  justify-end text-right flex flex-col sm:hidden lg:space-x-[16px] xl:space-x-[25px] xl:px-10">
                    <div class="header-camp-sub">TOTAL LEADS <span class="text-[#00A5FF]"> : {{ $totalLeads }}</span>
                    </div>
                    <div class="header-camp-sub">ASSIGNED LEADS <span class="text-[#38C976]"> :
                            {{ $assignedLeads }}</span> </div>
                    <div class="header-camp-sub">UNASSIGNED LEADS <span class="text-[#FF8206]"> :
                            {{ $unassignedLeads }}</span> </div>

                </h2> --}}
            </div>


        </div>
    </div>
@endif


@if ($type == 'campaignEditAll')
    <div
        class="bg-gray-backdrop  x-lay sticky z-20 inset-0 @if (isset($height)) {{ $height }} @else  h-[92px] sm:h-[60px] md:h-[74px] lg:h-[90px] @endif  top-[60px] lg:top-[64px]  lg:py-4 ">
        <div class="flex flex-row justify-between gap-4 ">

            <div class="flex items-start justify-between">

                <div class="flex flex-col items-start lg:items-start md:justify-center">
                    <h1
                        class=" flex header-title gap-[10px]  md:gap-[20px] lg:gap-[25px] space justify-center items-center  lg:mt-0 ">

                        <a href="{{ $link }}"
                            class="font-medium  text-[14px] text-primary-blue hover:text-blue-hover">
                            <x-icons.arrow.leftArrow class="w-[8px] lg:w-[9px] h-[17px] lg:h-[16px]" />
                        </a>

                        <span class="inline-block w-fit">
                            {{ $title }}
                        </span>
                        <div class="">


                            @component('components.badge', ['type' => $status ? 'active' : 'inactive'])
                            @endcomponent


                        </div>


                    </h1>

                    <h2 class=" hidden sm:flex space-x-[10px] lg:space-x-[16px] xl:space-x-[25px] xl:px-10">
                        <div class="header-camp-sub">TOTAL LEADS <span class="text-[#00A5FF]"> :
                                {{ $totalLeads }}</span> </div>
                        <div class="header-camp-sub">ASSIGNED LEADS <span class="text-[#38C976]"> :
                                {{ $assignedLeads }}</span> </div>
                        <div class="header-camp-sub">UNASSIGNED LEADS <span class="text-[#FF8206]"> :
                                {{ $unassignedLeads }}</span> </div>
                    </h2>


                </div>

            </div>

            {{-- edit --}}

            <div class="flex flex-col items-start justify-end md:justify-start md:flow-row ">
                <div class="flex flex-col items-start justify-center xl:items-center xl:flex-row ">
                    <div
                        class="inline-block px-2  font-medium  text-[8px] sm:text-[10px] md:text-[13px]  text-[#595959] uppercase">
                        REFERRAL LINK :</div>
                    <div class="flex flex-row space-x-4">
                        @component('components.forms.inputWithCopy', [
                            'id' => $id,
                            'onClickFn' => $copyString,
                            'value' => $value,
                            'classInput' => 'h-[32px] md:h-[38px] truncate pr-10 w-[255px] text-[#374151] form-input text-[12px]
                                                                                                                                                                                                                                                                                                                    md:text-[13px]',
                            'classSvg' => 'w-[16px] h-[16px] md:w-[24px] md:h-[24px]',
                        ])
                        @endcomponent
                        @if (isset($leadReqBtn))
                            @component('components.button-component', [
                                'label' => 'Request Lead',
                                'id' => 'submit',
                                'type' => 'submitSmall',
                                'wireClickFn' => 'showLeadRequest',
                            ])
                            @endcomponent
                        @endif
                    </div>

                </div>

                <h2
                    class=" mt-1 w-full  justify-end text-right flex flex-col sm:hidden lg:space-x-[16px] xl:space-x-[25px] xl:px-10">
                    <div class="header-camp-sub">TOTAL LEADS <span class="text-[#00A5FF]"> : {{ $totalLeads }}</span>
                    </div>
                    <div class="header-camp-sub">ASSIGNED LEADS <span class="text-[#38C976]"> :
                            {{ $assignedLeads }}</span> </div>
                    <div class="header-camp-sub">UNASSIGNED LEADS <span class="text-[#FF8206]"> :
                            {{ $unassignedLeads }}</span> </div>

                </h2>
            </div>


        </div>
    </div>
@endif
