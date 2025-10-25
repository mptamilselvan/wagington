@props(['tabs'=>
[['key'=>1,'display' => 'tab 1'],
[ 'key'=>2 ,'display' => 'tab 2']]])



<div x-data="{ openTab: 1 }" class="">
    @if(count($tabs))
    <div class="flex border-b gap-[30px] text-[#737373] x-lay">
        @foreach ($tabs as $tab)
        <button @click="openTab = {{$tab['key']}}" 
        :class="{'text-[#2B2C45] hover:text-[#2B2C45] border-b-4 rounded-l-[3px] rounded-r-[3px] py-1 border-[#2B2C45] ': openTab == '{{$tab['key']}}'}"
        class="mr-1 -mb-px p-[16px] font-semibold ">
            <a class=""
                href="#">
                {{$tab['display']}}
            </a>
        </button>
        @endforeach
    </div>
    @endif
    <div class="">
        {{$tabData}}

    </div>
</div>