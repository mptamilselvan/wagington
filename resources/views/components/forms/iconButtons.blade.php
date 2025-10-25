
<button type="button" class="bg-[#F6F6F6] rounded-[8px] h-[36px] w-[110px] text-[13px] lg:text-[14px] group button-strip text-center" @if(isset($id))
id="{{ $id }}" @endif @if (isset($wireClickFn)) wire:click="{{ $wireClickFn }}" @endif 
@if (isset($onClickFn)) onclick='{{ $onClickFn }}' @endif
@if(isset($disabled))disabled="" @endif>
	<div
		class="flex items-center justify-center space-x-1 disabled:text-gray-104 group-active:text-primary-blue group-hover:text-gray-109 urbanist-500 text-gray-icons">
		<x-icons.filter
			class="w-[20px]  disabled:text-gray-104   group-active:text-primary-blue text-gray-icons group-hover:text-gray-109 h-[20px] inline-block" />
		<span class="text-[14px] text-gray-500 ">Filter By</span>
	</div>
</button>