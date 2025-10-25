@props(['title','count','link','svg','svgColor'])

<div class="overflow-hidden bg-white rounded-[8px] shadow w-full max-w-[358px] min-w-[100]">
    <div class="px-[22px] lg:px-[24px] py-[26px] h-[90px] md:h-[100px]">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center rounded-[6px] w-[38px] h-[38px] lg:w-[48px] lg:h-[48px]  @if (isset($svgColor)) {{ $svgColor }} @else bg-secondry-yellow @endif">
                    <x-dynamic-component :component="$svg" class="w-[24px] h-[24px] inline-block text-white " />
                </div>
            </div>
            <div class="flex-1 w-0 ml-5">
                <dl>
                    <dt class="text-[13.5px] lg:text-[14px] manrope-600 text-gray-500 truncate"> {{ $title }}</dt>
                    <dd>
                        <div class=" text-[20px] md:text-[24px] manrope-600 font-medium text-[#111827]">{{ $count }}</div>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    @if($link)
    <div class="px-5 h-[52px] flex justify-start items-center bg-gray-light">
        <a href="{{$link}}" class="manrope-500 text-[14px] text-primary-blue hover:text-blue-hover">View all</a>
    </div>
    @endif
</div>