<div class="flex w-screen overflow-y-auto ">
    <div
        class="flex flex-col items-center justify-center flex-1 w-full h-full pt-16 overflow-y-auto md:pt-20 md:overflow-hidden">
        <div class="mb-[10px] md:mb-[20px] lg:hidden">
            <x-icons.sidebar.logo type='black-logo' class="h-[34px]" />
        </div>

        {{ $slot }}
    </div>

    <div class="flex-1 hidden w-full h-full overflow-hidden lg:block">

        <img src="../images/auth.png" class="object-cover w-full h-full sm:hidden md:block" alt="">
        {{--
        <x-icons.auth /> --}}
    </div>
</div>
