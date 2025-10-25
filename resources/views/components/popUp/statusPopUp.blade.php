<div class="fixed inset-0 top-0 left-0 z-50 flex items-center justify-center h-screen bg-center bg-no-repeat bg-cover outline-none min-w-screen animated fadeIn faster focus:outline-none"
    id="modal-id">
    <div class="absolute inset-0 z-0 bg-black opacity-80"></div>
    <div
        class="relative w-full max-w-[300px] sm:max-w-xs md:max-w-[470px] mx-auto my-auto bg-white shadow-lg rounded-[20px] ">
        
        <!--content-->
        <div class="">
            {{$content}}
        </div>
         <!--content end-->
         
        <!--footer-->
        <div class="">
            {{$footer}}
        </div>
        <!--footer end-->

       
    </div>
</div>