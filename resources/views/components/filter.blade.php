<div class="fixed inset-0 bg-black bg-opacity-40 z-20" @if(isset($wireClickCloseFn))wire:click="{{ $wireClickCloseFn }}" @endif></div>

<div class="absolute top-[162px] lg:top-[136px] right-[10px] sm:right-[27px] rounded-[16px] z-30  @if(isset($small)) max-w-[300px] sm:max-w-[371px]  @else max-w-[300px] sm:max-w-[371px] md:max-w-[451px] lg:max-w-[594px] @endif  w-full overflow-hidden  bg-white border  shadow 
    style=" width: max-content">

    {{-- section top --}}

    <div class=" @if(isset($small)) lg:py-[40px] md:py-[30px] py-[28px] px-[20px] md:px-[23px] lg:px-[33px]  @else lg:py-[40px] md:py-[30px] py-[28px]  @endif ">
        <header class="mb-4 z-60  @if(!isset($small)) px-[20px] md:px-[23px] lg:px-[33px]  @endif ">
            <b class="text-[#595959] text-[20px] lg:text-[24px] font-semibold">Filter</b>
            <div class="flex justify-end w-full -mt-8 cursor-pointer text-maid-green "
                @if(isset($wireClickCloseFn))wire:click="{{ $wireClickCloseFn }}" @endif>
                <x-icons.close class="w-[13px] h-[13px] text-[#343538]" />
            </div>
        </header>

        <div class="">
            {{$dataSlot}}
        </div>
    </div>

    {{--buttom section for buttons --}}
    <div class="flex p-[25px] h-[86px] space-x-[63px] justify-end bg-[#F9F9F9]">
        @if($resetFilter)
        <div class="">
            @component('components.button-component', [
            'label' => 'Clear',
            'id' => 'clear',
            'type' => 'TextButton',
            'wireClickFn' => 'resetFilter',
            ])
            @endcomponent
        </div>
        @endif

        @if ($applyFilter)
        <div class="">
            @component('components.button-component', [
            'label' => 'Apply',
            'id' => 'apply',
            'type' => 'submitSmall',
            'wireClickFn' => 'applyFilter',
            ])
            @endcomponent
        </div>
        @endif

    </div>

</div>