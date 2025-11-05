    <div class="hidden bg-gray-900 lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
        <!-- Sidebar component, swap this element with another sidebar if you like -->
        <div
            class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 px-6 dark:border-white/10 side_menu_bg">
            <div class="flex h-16 shrink-0 items-center">
                {{-- <img src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600"
                    alt="Your Company" class="h-8 w-auto dark:hidden" />
                <img src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500"
                    alt="Your Company" class="hidden h-8 w-auto dark:block" /> --}}
            </div>

            <span class="text-white text-3xl pl-5">Wagington</span>
            <nav class="flex flex-1 flex-col">
                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                        <ul role="list" class="-mx-2 space-y-1">
                            <li>
                                <!-- Current: "bg-white/5 text-white", Default: "text-gray-400 hover:text-white hover:bg-white/5" -->
                                <a href="#"
                                    class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('dashboard') ? 'bg-white/5 text-white' : 'text-gray-400' }} hover:bg-white/5 hover:text-white h-10">
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

                            @php
                                // Determine if the current route is within the settings section
                                $highlightPetProfileMenu = false;
                                $settingsMenus = [
                                    'pets',
                                    'vaccination-records',
                                    'blood-test-records',
                                    'deworming-records',
                                    'medical-history-records',
                                    'dietary-preferences',
                                    'medication-supplements',
                                    'temperament-health-evaluations',
                                    'size-managements',
                                ];
                                if (in_array(request()->segment(2), $settingsMenus)) {
                                    $highlightPetProfileMenu = true;
                                }
                            @endphp
                            <li>
                                <a href="{{ route('admin.pets') }}"
                                    class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold hover:bg-white/5 hover:text-white h-10 {{ $highlightPetProfileMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                    @include('components.icons.sidebar.pet')
                                    Pet Management
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.services') }}"
                                    class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('admin.services') ? 'bg-white/5 text-white' : 'text-gray-400' }} hover:bg-white/5 hover:text-white h-10">
                                    @include('components.icons.sidebar.addons')
                                    Services
                                </a>
                            </li>
                            @php
                                // Determine if the current route is within the Campaign section
                                $showCampaignSubmenu = 'hidden';
                                $highlightCampaignMenu = false;
                                $highlightReferralMenu = false;
                                $highlightmarketingcampaign = false;
                                $highlightVoucher = false;
                                if (request()->segment(3) == 'referralpromotion') {
                                    $showCampaignSubmenu = '';
                                    $highlightCampaignMenu = $highlightReferralMenu = true;
                                } elseif (request()->segment(3) == 'marketingcampaign') {
                                    $showCampaignSubmenu = '';
                                    $highlightCampaignMenu = true;
                                    $highlightmarketingcampaign = true;
                                } elseif (request()->segment(3) == 'voucher') {
                                    $showCampaignSubmenu = '';
                                    $highlightCampaignMenu = true;
                                    $highlightVoucher = true;
                                }
                            @endphp
                            <li>
                                <div
                                    class="flex flex-row hover:bg-white/5 hover:text-white h-10 {{ $highlightCampaignMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                    <span class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold">
                                        @include('components.icons.sidebar.marketing-campaign')
                                    </span>
                                    <button type="button" command="--toggle" commandfor="sub-menu-campaign"
                                        class="flex w-full items-center gap-x-3 rounded-md p-2 text-left text-sm/6 font-semibold">
                                        Campaign
                                    </button>
                                </div>
                                <el-disclosure id="sub-menu-campaign" {{ $showCampaignSubmenu }}
                                    class="[&:not([hidden])]:contents">
                                    <ul class="mt-1 px-2">
                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('admin.referralpromotion') }}"
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightReferralMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">Referral
                                                Promotions</a>
                                        </li>
                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('admin.marketingcampaign') }} "
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 text-gray-400 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightmarketingcampaign ? 'bg-white/5 text-white' : 'text-gray-400' }}">Marketing
                                                Campaigns</a>
                                        </li>

                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('admin.voucher') }} "
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 text-gray-400 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightVoucher ? 'bg-white/5 text-white' : 'text-gray-400' }}">Voucher</a>
                                        </li>
                                    </ul>
                                </el-disclosure>
                            </li>

                            @php
                                // E-commerce submenu logic
                                $showEcommerceSubmenu = 'hidden';
                                $highlightEcommerceMenu = false;
                                $highlightProductMenu = false;
                                $highlightOrderMenu = false;
                                $highlightShippingMenu = false;
                                $highlightInventoryMenu = false;
                                $ecommerceMenus = [
                                    'product-management',
                                    'order-management',
                                    'shipping-management',
                                    'inventory-restock',
                                ];
                                if (in_array(request()->segment(2), $ecommerceMenus)) {
                                    $showEcommerceSubmenu = '';
                                    $highlightEcommerceMenu = true;
                                    if (request()->segment(2) == 'product-management') {
                                        $highlightProductMenu = true;
                                    } elseif (request()->segment(2) == 'order-management') {
                                        $highlightOrderMenu = true;
                                    } elseif (request()->segment(2) == 'shipping-management') {
                                        $highlightShippingMenu = true;
                                    } elseif (request()->segment(2) == 'inventory-restock') {
                                        $highlightInventoryMenu = true;
                                    }
                                }
                            @endphp
                            <li>
                                <div
                                    class="flex flex-row hover:bg-white/5 hover:text-white h-10 {{ $highlightEcommerceMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                    <span class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold">
                                        @include('components.icons.sidebar.setting')
                                    </span>
                                    <button type="button" command="--toggle" commandfor="sub-menu-ecommerce"
                                        class="flex w-full items-center gap-x-3 rounded-md p-2 text-left text-sm/6 font-semibold">
                                        E-commerce
                                    </button>
                                </div>
                                <el-disclosure id="sub-menu-ecommerce" {{ $showEcommerceSubmenu }}
                                    class="[&:not([hidden])]:contents">
                                    <ul class="mt-1 px-2">
                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('product-management') }}"
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightProductMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                                Product Management
                                            </a>
                                        </li>
                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('order-management') }}"
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightOrderMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                                Order Management
                                            </a>
                                        </li>
                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('shipping-management') }}"
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightShippingMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                                Shipping Management
                                            </a>
                                        </li>
                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('inventory-restock') }}"
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightInventoryMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                                Inventory Management
                                            </a>
                                        </li>
                                    </ul>
                                </el-disclosure>
                            </li>

                            @php
                                // Room Management submenu logic
                                $showRoomSubmenu = 'hidden';
                                $highlightRoomManagementMenu = false;
                                $highlightRoomMenu = false;
                                $highlightRoomTypeMenu = false;
                                $highlightRoomSettingsMenu = false;
                                $highlightCancelSettingMenu = false;
                                $roomMenus = ['rooms', 'room-types'];
                                if (in_array(request()->segment(2), $roomMenus)) {
                                    $showRoomSubmenu = '';
                                    //$highlightRoomMenu = true;
                                    $highlightRoomManagementMenu = true;
                                    if (request()->segment(2) == 'rooms') {
                                        $highlightRoomMenu = true;
                                    } elseif (request()->segment(2) == 'room-types') {
                                        $highlightRoomTypeMenu = true;
                                    }
                                }
                            @endphp
                            <li>
                                <div
                                    class="flex flex-row hover:bg-white/5 hover:text-white h-10 {{ $highlightRoomManagementMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                    <span class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold">
                                        @include('components.icons.sidebar.rooms')
                                    </span>
                                    <button type="button" command="--toggle" commandfor="sub-menu-room"
                                        class="flex w-full items-center gap-x-3 rounded-md p-2 text-left text-sm/6 font-semibold">
                                        Rooms Management
                                    </button>
                                </div>
                                <el-disclosure id="sub-menu-room" {{ $showRoomSubmenu }}
                                    class="[&:not([hidden])]:contents">
                                    <ul class="mt-1 px-4">

                                        <li>
                                            <a href="{{ route('room-bookings') }}"
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightProductMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                                Booking Management
                                            </a>
                                        </li>
                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('room-types') }}"
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightRoomTypeMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                                Room Types
                                            </a>
                                        </li>
                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('rooms') }}"
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightRoomMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                                Rooms
                                            </a>
                                        </li>

                                    </ul>
                                </el-disclosure>
                            </li>

                            @php
                                // Determine if the current route is within the settings section
                                $showSubmenu = 'hidden';
                                $highlightMenu = false;
                                $highlightPetMenu = false;
                                $highlightGeneralMenu = false;
                                $highlightEcommerceMenu = false;
                                $highlightServiceSettingMenu = false;
                                $highlightRoomSettingsMenu = false;
                                $settingsMenus = [
                                    'species',
                                    'breeds',
                                    'vaccination',
                                    'blood-tests',
                                    'sizes',
                                    'pet-tags',
                                    'vaccine-exemptions',
                                    'revaluation-workflow',
                                ];
                                $generalsettingsMenus = [
                                    'system-settings',
                                    'company-settings',
                                    'operational-hours',
                                    'tax-settings',
                                ];
                                $servicesettingsMenus = ['service-settings'];
                                $roomsettingsMenus = [
                                    'species-size-settings',
                                    'pet-size-limit-settings',
                                    'room-peak-seasons',
                                    'room-off-days',
                                    'room-cancel-setting',
                                    'room-weekend',
                                    'room-price-options',
                                ];
                                $ecommercesettingsMenus = ['ecommerce-settings'];
                                if (in_array(request()->segment(2), $settingsMenus)) {
                                    $showSubmenu = '';
                                    $highlightMenu = $highlightPetMenu = true;
                                } elseif (in_array(request()->segment(2), $generalsettingsMenus)) {
                                    $showSubmenu = '';
                                    $highlightMenu = true;
                                    $highlightGeneralMenu = true;
                                } elseif (in_array(request()->segment(2), $ecommercesettingsMenus)) {
                                    $showSubmenu = '';
                                    $highlightMenu = true;
                                    $highlightEcommerceMenu = true;
                                } elseif (in_array(request()->segment(2), $servicesettingsMenus)) {
                                    $showSubmenu = '';
                                    $highlightMenu = true;
                                    $highlightServiceSettingMenu = true;
                                } elseif (in_array(request()->segment(2), $roomsettingsMenus)) {
                                    $showSubmenu = '';
                                    $highlightMenu = true;
                                    $highlightRoomSettingsMenu = true;
                                }
                            @endphp
                            <li>
                                <div
                                    class="flex flex-row hover:bg-white/5 hover:text-white h-10 {{ $highlightMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                    <span class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold">
                                        @include('components.icons.sidebar.setting')
                                    </span>
                                    <button type="button" command="--toggle" commandfor="sub-menu-settings"
                                        class="flex w-full items-center gap-x-3 rounded-md p-2 text-left text-sm/6 font-semibold">
                                        Settings
                                    </button>
                                </div>
                                <el-disclosure id="sub-menu-settings" {{ $showSubmenu }}
                                    class="[&:not([hidden])]:contents">
                                    <ul class="mt-1 px-2">
                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('admin.species') }}"
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightPetMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">Pet
                                                Settings</a>
                                        </li>
                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('system-settings') }} "
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 text-gray-400 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightGeneralMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">General
                                                Settings</a>
                                        </li>
                                        <li>
                                            <!-- 44px -->
                                            <a href="{{ route('admin.service-category') }} "
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 text-gray-400 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightServiceSettingMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">Service
                                                Settings</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('pet-size-limit-settings') }}"
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightRoomSettingsMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">
                                                Room Settings
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('ecommerce-settings') }}"
                                                class="block rounded-md py-2 pl-9 pr-2 text-sm/6 text-gray-400 hover:bg-white/5 hover:text-white h-10 font-semibold {{ $highlightEcommerceMenu ? 'bg-white/5 text-white' : 'text-gray-400' }}">E-commerce
                                                Settings</a>
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
                    {{-- <li class="-mx-6 mt-auto">
                        <a href="#"
                            class="flex items-center gap-x-4 px-6 py-3 text-sm/6 font-semibold text-white hover:bg-white/5">
                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                alt=""
                                class="size-8 rounded-full bg-gray-800 outline outline-1 -outline-offset-1 outline-white/10" />
                            <span class="sr-only">Your profile</span>
                            <span aria-hidden="true">{{ Auth::user()->name }}</span>
                        </a>
                    </li> --}}
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
                            <div
                                class="rounded-full w-[35px] h-[35px] bg-[#858E96] flex justify-center items-center mr-3">
                                <span
                                    class="text-white text-[16px] font-semibold">{{ substr(Auth::user()->name, 0, 1) }}</span>
                            </div>
                        @endif
                        <div class="flex-1 text-white group-hover:text-primary-navy">
                            <div class="text-sm font-semibold">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-gray-300 group-hover:text-gray-600">{{ Auth::user()->email }}
                            </div>
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
    </div>
