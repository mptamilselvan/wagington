

@if($type=='active')

<div class="bg-[#D1FAE5] manrope-600 w-[57px] h-[20px] text-[12px] text-[#065F46] flex items-center justify-center rounded-full">
    Active
</div>
@endif

@if($type=='inactive')
<div class="bg-[#FEE2E2] manrope-600 w-[57px] h-[20px] text-[12px] text-[#991B1B] flex items-center justify-center rounded-full">
    Inactive
</div>
@endif

@if($type=='expired')
    <div class="bg-[#FEE2E2] manrope-600 w-[57px] h-[20px] text-[12px] text-[#991B1B] flex items-center justify-center rounded-full p-3">
        Expired
    </div>
@endif

