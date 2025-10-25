{{-- <div class="fixed inset-0 top-0 left-0 z-50 flex items-center justify-center h-screen bg-center bg-no-repeat bg-cover outline-none min-w-screen animated fadeIn faster focus:outline-none"
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
</div> --}}

        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-medium text-gray-900" style="font-family: 'Rubik', sans-serif;">{{ isset($title)?$title : 'Delete Record' }}</h3>
                            <button wire:click='$set("popUp", false)' class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mb-6">
                            <p class="text-gray-600 text-sm mt-2" style="font-family: 'Rubik', sans-serif;">{{$content}}</p>
                        </div>

                        <div class="flex justify-end space-x-3">
                            {{$footer}}
                        </div>
                    </div>
                </div>
            </div>
        </div>