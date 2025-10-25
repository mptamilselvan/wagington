<div class="flex flex-col flex-1 nav-lay">
    <div x-data="{ 'isModalOpen': false }" x-on:keydown.escape="isModalOpen=false"
        class="sticky top-0 z-30 flex flex-shrink-0 h-16 bg-gray-backdrop">
        <button @click="showNav = !showNav" type="button"
            class="px-4 text-gray-500 border-gray-200 button-strip lg:hidden">
            <span class="sr-only">Open sidebar</span>
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
            </svg>
        </button>
        <div class="flex justify-end flex-1 px-4">

            <div class="flex items-center ml-4 lg:ml-6">
                <!-- Profile dropdown -->
                <div class="relative ml-3 ">
                    <div class="mt-1 mr-1 lg:mt-6 lg:mr-4">

                        <button x-on:click="isModalOpen = true" type="button"
                            class="flex items-center text-sm bg-white rounded-full button-strip " id="user-menu-button"
                            aria-expanded="false" aria-haspopup="true">
                            @if (Session::has('userPhoto'))
                                <img class="rounded-full w-[35px] h-[35px] object-cover   block "
                                    src="{{ Session::get('userPhoto') }}?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                    alt="">
                            @else
                                <div
                                    class="rounded-full w-[35px] h-[35px] bg-[#858E96] flex justify-center items-center">
                                    <span
                                        class="text-white text-[25px] manrope-600">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                </div>
                            @endif
                        </button>

                    </div>

                    <!--
            Dropdown menu, show/hide based on menu state.

            Entering: "transition ease-out duration-100"
              From: "transform opacity-0 scale-95"
              To: "transform opacity-100 scale-100"
            Leaving: "transition ease-in duration-75"
              From: "transform opacity-100 scale-100"
              To: "transform opacity-0 scale-95"
          -->
                    <div x-show="isModalOpen" x-on:click.away="isModalOpen = false" x-cloak x-transition
                        class="absolute right-0 z-10 w-48 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                        role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                        <!-- Active: "bg-gray-100", Not Active: "" -->
                        <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 top-nav-menu-item"
                            role="menuitem" tabindex="-1" id="user-menu-item-0">Profile</a>


                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
