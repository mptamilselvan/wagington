<div  class="relative z-30" role="dialog" aria-modal="true">
    <!-- opacity -->
    <div class="fixed inset-0 block transition-opacity bg-[#000000] opacity-40"></div>

    <div class="fixed  inset-0 z-10 rouned-[20px] overflow-hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-full px-2 text-center lg:px-4">
            <!-- This element is to trick the browser into centering the modal contents. -->
            <span class="hidden md:inline-block md:h-screen md:align-middle" aria-hidden="true">&#8203;</span>

            <div class="flex flex-col w-full transform text-left text-base transition  my-4 md:my-8  px-4 
                max-w-[556px]">
                <div
                    class="relative box rounded-[16px] md:rounded-[10px] flex flex-col w-full items-center bg-white  shadow-2xl   ">
                    <!-- header -->

                    <div
                        class=" z-10 bg-primary-navy  h-[78px] p-[30px]   rounded-t-[16px] md:rounded-t-[10px] w-full items-center flex  justify-between">
                        <h1
                            class="text-white text-[19px] md:text-[20px] font-semibold lg:text-[24px] font-[500] capitalize  ">
                            {{$title}}
                        </h1>

                        <button wire:click='{{$closePopUpFun}}'>
                            <x-icons.close class="w-[20px] h-[24px] text-white" />
                        </button>
                    </div>

                    <div class="w-full h-full ">
                        {{$slot}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>