<div x-data="{ showOptions: 0 }">
    <div @mouseover="showOptions = {{$id }}" @mouseover.away="showOptions = 0">
        {{$data}}
    </div>
    <div class="absolute flex items-center justify-start" x-show="showOptions === {{ $id }}">
        <div
            class="bg-[#E7E7E7] tooltiptextLeftWrap w-fit absolute top-[10px] transition  ease-in duration-100 p-2 font-[500] tracking-wide  inset-x-0 text-[#5A5A5A] text-[11px] text-center  rounded-[4px]">
            @if(isset($list))
            <ul>
                @foreach ($list as $item)
                <li class="text-left whitespace-nowrap">{{$item}}</li>
                @endforeach
            </ul>
            @endif

            @if(isset($dataShow))
            {{$dataShow}}
            @endif

        </div>
    </div>

    <style>
        .tooltiptextLeftWrap {
            text-align: center;
            border-radius: 8px;
            position: absolute;
            z-index: 1;
            /* top: 150%; */
            left: -16px;
        }

        .tooltiptextLeftWrap::after {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 20%;
            margin-left: -10px;
            border-width: 7px;
            border-style: solid;
            border-color: transparent transparent #E7E7E7 transparent;
        }
    </style>
</div>