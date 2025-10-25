<el-dialog>
        <dialog id="sidebar" class="m-0 p-0 backdrop:bg-transparent lg:hidden">
            <el-dialog-backdrop
                class="fixed inset-0 bg-gray-900/80 transition-opacity duration-300 ease-linear data-[closed]:opacity-0"></el-dialog-backdrop>

            <div tabindex="0" class="fixed inset-0 flex focus:outline focus:outline-0">
                <el-dialog-panel
                    class="group/dialog-panel relative mr-16 flex w-full max-w-xs flex-1 transform transition duration-300 ease-in-out data-[closed]:-translate-x-full">
                    <div
                        class="absolute left-full top-0 flex w-16 justify-center pt-5 duration-300 ease-in-out group-data-[closed]/dialog-panel:opacity-0">
                        <button type="button" command="close" commandfor="sidebar" class="-m-2.5 p-2.5">
                            <span class="sr-only">Close sidebar</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                data-slot="icon" aria-hidden="true" class="size-6 text-white">
                                <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>

                    <!-- Sidebar component, swap this element with another sidebar if you like -->
                    <div
                        class="relative flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-2 ring-1 ring-white/10 dark:before:pointer-events-none dark:before:absolute dark:before:inset-0 dark:before:bg-black/10">
                        <div class="relative flex h-16 shrink-0 items-center">
                            <span class="text-white text-2xl">Wagington</span>
                        </div>

                        <nav class="flex flex-1 flex-col">
                            <ul role="list" class="flex flex-1 flex-col gap-y-7">
                                <li>
                                    <ul role="list" class="-mx-2 space-y-1">
                                        @php
                                            $isDashboard = request()->routeIs('dashboard');
                                            $settingsMenus = ['species', 'vaccination', 'blood-tests', 'sizes', 'pet-tags', 'vaccine-exemptions', 'revaluation-workflow'];
                                            $showSubmenu = in_array(request()->segment(2), $settingsMenus) ? '' : 'hidden';
                                            $highlightMenu = in_array(request()->segment(2), $settingsMenus);
                                            $highlightPetMenu = in_array(request()->segment(2), $settingsMenus);
                                        @endphp
                                        <li>
                                            <a href="{{ route('dashboard') }}"
                                                class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ $isDashboard ? 'bg-white/5 text-white' : 'text-gray-400' }} hover:bg-white/5 hover:text-white h-10">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                                    data-slot="icon" aria-hidden="true" class="size-6 shrink-0">
                                                    <path
                                                        d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                Dashboard
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('customers') }}"
                                                class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('customers') ? 'bg-white/5 text-white' : 'text-gray-400' }} hover:bg-white/5 hover:text-white h-10">
                                                @include('components.icons.user.twoUsers')
                                                Customers
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.pets') }}"
                                                class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('admin.pets') ? 'bg-white/5 text-white' : 'text-gray-400' }} hover:bg-white/5 hover:text-white h-10">
                                                @include('components.icons.sidebar.pet')
                                                Pet Management
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#"
                                                class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-gray-400 hover:bg-white/5 hover:text-white h-10">
                                                @include('components.icons.sidebar.addons')
                                                Services Add-ons
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#"
                                                class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-gray-400 hover:bg-white/5 hover:text-white h-10">
                                                @include('components.icons.sidebar.promotion')
                                                Referral Promotions
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('campaigns') }}"
                                                class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('campaigns') ? 'bg-white/5 text-white' : 'text-gray-400' }} hover:bg-white/5 hover:text-white h-10">
                                                @include('components.icons.sidebar.marketing-campaign')
                                                Marketing Campaigns
                                            </a>
                                        </li>
                                        <li>
                                            <div class="flex flex-row hover:bg-white/5 hover:text-white h-10 {{ $highlightMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                                <span class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold">
                                                    @include('components.icons.sidebar.setting')
                                                </span>
                                                <button type="button" command="--toggle" commandfor="sub-menu-settings"
                                                    class="flex w-full items-center gap-x-3 rounded-md p-2 text-left text-sm/6 font-semibold">
                                                    Settings
                                                </button>
                                            </div>
                                            <el-disclosure id="sub-menu-settings" {{$showSubmenu}} class="[&:not([hidden])]:contents">
                                                <ul class="mt-1 px-2">
                                                    <li>
                                                        <a href="{{ route('admin.species') }}"
                                                            class="block rounded-md py-2 pl-9 pr-2 text-sm/6 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightPetMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">Pet Settings</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('company-settings') }}"
                                                            class="block rounded-md py-2 pl-9 pr-2 text-sm/6 text-gray-400 hover:bg-white/5 hover:text-white h-10 font-semibold">Company Settings</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('system-settings') }}"
                                                            class="block rounded-md py-2 pl-9 pr-2 text-sm/6 text-gray-400 hover:bg-white/5 hover:text-white h-10 font-semibold">System Settings</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('operational-hours') }}"
                                                            class="block rounded-md py-2 pl-9 pr-2 text-sm/6 text-gray-400 hover:bg-white/5 hover:text-white h-10 font-semibold">Operational Hours</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('tax-settings') }}"
                                                            class="block rounded-md py-2 pl-9 pr-2 text-sm/6 text-gray-400 hover:bg-white/5 hover:text-white h-10 font-semibold">Tax Settings</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('peak-season') }}"
                                                            class="block rounded-md py-2 pl-9 pr-2 text-sm/6 text-gray-400 hover:bg-white/5 hover:text-white h-10 font-semibold">Peak Seasons</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('off-days') }}"
                                                            class="block rounded-md py-2 pl-9 pr-2 text-sm/6 text-gray-400 hover:bg-white/5 hover:text-white h-10 font-semibold">Off Days</a>
                                                    </li>
                                                </ul>
                                            </el-disclosure>
                                        </li>
                                        <li>
                                            <a href="#"
                                                class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-gray-400 hover:bg-white/5 hover:text-white h-10">
                                                @include('components.icons.sidebar.car')
                                                Limo Service Management
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </nav>

                        <!-- Admin Profile at Bottom of Sidebar -->
                        <div class="pb-6">
                            <div x-data="{ 'isModalOpen': false }" x-on:keydown.escape="isModalOpen=false" class="relative">
                                <button x-on:click="isModalOpen = true" type="button"
                                    class="w-full flex items-center px-2 py-3 text-left rounded-md hover:bg-white hover:text-primary-navy group transition-colors">
                                    @if (Session::has('userPhoto'))
                                        <img class="rounded-full w-[35px] h-[35px] object-cover mr-3"
                                            src="{{ Session::get('userPhoto') }}?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                            alt="">
                                    @else
                                        <div class="rounded-full w-[35px] h-[35px] bg-[#858E96] flex justify-center items-center mr-3">
                                            <span class="text-white text-[16px] font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex-1 text-white group-hover:text-primary-navy">
                                        <div class="text-sm font-semibold">{{ Auth::user()->name }}</div>
                                        <div class="text-xs text-gray-300 group-hover:text-gray-600">{{ Auth::user()->email }}</div>
                                    </div>
                                </button>

                                <!-- Dropdown Menu -->
                                <div x-show="isModalOpen" x-on:click.away="isModalOpen = false" x-cloak x-transition
                                    class="absolute bottom-full left-0 w-full mb-2 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Log Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </el-dialog-panel>
            </div>
        </dialog>
    </el-dialog>