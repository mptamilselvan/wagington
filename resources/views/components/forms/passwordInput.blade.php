<div class="relative" x-data="{ show: true }">
    <input class="form-password" {!! $attributes->merge(['class' =>
    'form-input']) !!} :type="show ? 'password' : 'text'" type="password" >

    <div class="absolute right-0 cursor-pointer top-1/2 transform-center-y">
        <template x-if="show">
            <x-icons.eye.closedEye class="h-[23px] w-[45px] text-[#C4C4C4]" @click="show = !show" />


        </template>
        <template x-if="!show">
            <x-icons.eye.openedEye class="h-[18px] w-[45px] text-[#C4C4C4]" @click="show = !show" />

        </template>
    </div>
</div>


