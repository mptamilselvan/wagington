<div>
    @if (session()->has('success'))
       <x-success-modal 
           :title="'Successfully updated!'" 
           :message="session('success')" 
           :duration="3000"
       />
       @php
           // Clear the success message after displaying it
           session()->forget('success');
       @endphp
    @endif

    @if (session()->has('alert'))
        <div id="alert-messages" x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show">
            {{-- <div class="fixed top-0 left-0 right-0 w-1/2 px-4 py-2 mx-auto text-center text-white bg-yellow-50"> --}}
            <div class="text-white bg-yellow-50">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">{{ session('alert') }}</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif


    @if (session()->has('error'))
        <div id="alert-messages" x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show">
            <div
                class="text-white fixed bg-red-50 border-state-red  z-50 px-4 py-2 rounded-[8px] border top-10 right-5">
                <div class="rounded-md bg-red-50">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-icons.error class="w-5 h-5 text-state-red" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-state-red"> {{ session('error') }}</h3>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    @endif

    @if (session()->has('notification'))
        <div id="alert-messages" class="text-white bg-blue-50" x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show">
            <div class="p-4 rounded-md bg-blue-50">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">{{ session('notification') }}</h3>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- <script>
        setTimeout(function() {
        document.getElementById('alert-messages').remove();
        }, 5000);
    </script> --}}
</div>