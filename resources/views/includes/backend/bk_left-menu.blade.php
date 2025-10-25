{{-- @php
    $countData = DataCount::getSideNavCount();
    $userCount = $countData['users'];
    $campaignCount = $countData['campaigns'];
    $leadCount = $countData['leads'];
    $userLeadCount = $countData['userleads'];
    $consultantcampCount = $countData['consultantcampCount'];
@endphp
@role('SuperAdmin')
    @php
        $menu = [
            // ['text' => 'Dashboard', 'link' => 'dashboard', 'icon'=>'icons.sidebar.dashboard','count'=>0],
            [
                'text' => 'Campaigns',
                'link' => 'campaigns',
                'icon' => 'icons.sidebar.campaign',
                'count' => $campaignCount,
            ],
            ['text' => 'Leads', 'link' => 'leads', 'icon' => 'icons.sidebar.lead', 'count' => $leadCount],
            ['text' => 'Consultants', 'link' => 'users', 'icon' => 'icons.user.singleUser', 'count' => $userCount],
            [
                'text' => 'Lead Requests',
                'link' => 'lead-requests-all',
                'icon' => 'icons.sidebar.lead-request',
                'count' => 0,
            ],
            [
                'text' => 'Contact Messages',
                'link' => 'contact-messages',
                'icon' => 'icons.sidebar.message',
                'count' => 0,
            ],
            ['text' => 'Agencies', 'link' => 'agencies', 'icon' => 'icons.user.singleUser', 'count' => 0],
            ['text' => 'Report', 'link' => 'reports', 'icon' => 'icons.user.singleUser', 'count' => 0],
            ['text' => 'Help', 'link' => 'help', 'icon' => 'icons.sidebar.campaign', 'count' => 0],
        ];
    @endphp
@else
    @php
        $menu = [
            // ['text' => 'Dashboard', 'link' => 'dashboard', 'icon'=>'icons.sidebar.dashboard','count'=>0],
            [
                'text' => 'Campaigns',
                'link' => 'campaigns',
                'icon' => 'icons.sidebar.campaign',
                'count' => DataCount::getLeadCampCount(),
            ],
            ['text' => 'Leads', 'link' => 'leads', 'icon' => 'icons.sidebar.lead', 'count' => $userLeadCount],
            ['text' => 'Messages', 'link' => 'user-messages', 'icon' => 'icons.sidebar.message', 'count' => 0],
            ['text' => 'Help', 'link' => 'help', 'icon' => 'icons.sidebar.campaign', 'count' => 0],
        ];
    @endphp
@endrole --}}


{{-- temp setup for wagington --}}
    @php
        $menu = [
            // ['text' => 'Dashboard', 'link' => 'dashboard', 'icon'=>'icons.sidebar.dashboard','count'=>0],
            [
                'text' => 'Campaigns',
                'link' => 'campaigns',
                'icon' => 'icons.sidebar.campaign',
                'count' => 0,
            ],
            ['text' => 'Leads', 'link' => 'leads', 'icon' => 'icons.sidebar.lead', 'count' => 0],
            ['text' => 'Consultants', 'link' => 'users', 'icon' => 'icons.user.singleUser', 'count' => 0],
            [
                'text' => 'Lead Requests',
                'link' => 'lead-requests-all',
                'icon' => 'icons.sidebar.lead-request',
                'count' => 0,
            ],
            [
                'text' => 'Contact Messages',
                'link' => 'contact-messages',
                'icon' => 'icons.sidebar.message',
                'count' => 0,
            ],
            ['text' => 'Agencies', 'link' => 'agencies', 'icon' => 'icons.user.singleUser', 'count' => 0],
            ['text' => 'Report', 'link' => 'reports', 'icon' => 'icons.user.singleUser', 'count' => 0],
            ['text' => 'Help', 'link' => 'help', 'icon' => 'icons.sidebar.campaign', 'count' => 0],
        ];
    @endphp






<div>

    {{-- MOBILE --}}

    <!-- Off-canvas menu for mobile, show/hide based on off-canvas menu state. -->
    <div x-show="showNav" x-cloak class="relative z-40 lg:hidden" role="dialog" aria-modal="true">

        <div class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>

        <div class="fixed inset-0 z-40 flex">
            <div x-show="showNav" x-cloak x-transition:enter="transition ease-in-out duration-100"
                x-transition:enter-start="opacity-0 transform -translate-x-1/2"
                x-transition:enter-end="opacity-100 transform  translate-x-0"
                x-transition:leave="transition ease-in-out duration-200"
                class="relative flex flex-col flex-1 w-full max-w-xs pt-5 pb-4 bg-primary-navy">

                <div class="absolute top-0 right-0 pt-2 -mr-12">
                    <button @click="showNav = !showNav" type="button"
                        class="flex items-center justify-center w-10 h-10 ml-1 button-strip">
                        <span class="sr-only">Close sidebar</span>
                        <x-icons.close class="w-[17.6px] h-[17.6px] text-white" />
                    </button>
                </div>

                <div class="flex items-center flex-shrink-0 px-4">
                    <x-icons.sidebar.logo class="w-[35.61px] h-[31px]" />
                </div>
                <div class="flex-1 h-0 mt-5 overflow-y-auto">
                    <nav class="px-2 space-y-1">
                        @foreach ($menu as $men)
                            <a href="{{ $men['link'] }}"
                                class=" {{ Route::current()->getName() === $men['link'] ? 'bg-white text-primary-navy' : '' }} flex  items-center justify-between px-2 h-[43px]  lg:text-[16px] xl:text-[17px] manrope-600 rounded-md hover:text-primary-navy group hover:bg-white text-gray-menu">

                                <p class="flex ">
                                    <x-dynamic-component :component="$men['icon']" class="flex-shrink-0 w-6 h-6 mr-3 " />
                                    {{ $men['text'] }}
                                </p>
                                @if ($men['count'] > 0)
                                    <div
                                        class="flex-shrink-0 w-6 flex justify-center items-center text-[12px] h-6 text-white rounded-full text-14px bg-primary-blue">
                                        {{ $men['count'] }}</div>
                                @endif
                            </a>
                        @endforeach

                    </nav>
                </div>
            </div>

            <div class="flex-shrink-0 w-14" aria-hidden="true">
                <!-- Dummy element to force sidebar to shrink to fit close icon -->
            </div>
        </div>
    </div>



    {{-- DESKTOP --}}

    <!-- Static sidebar for DESKTOP -->
    <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-[225px] xl:w-[240px] lg:flex-col">
        <!-- Sidebar component, swap this element with another sidebar if you like -->
        <div class="flex flex-col flex-grow pt-5 overflow-y-auto bg-primary-navy">

            <div class="flex items-center mt-[50px] justify-center flex-shrink-0 px-4">
                <x-icons.sidebar.logo class="w-[35.61px] h-[31px]" />
            </div>


            <div class="flex flex-col flex-1 mt-[70px]">
                <nav class="flex-1 px-4 pb-4 space-y-2 ">

                    @foreach ($menu as $men)
                        <a href="/{{ $men['link'] }}"
                            class=" {{ request()->path() === $men['link'] ? 'bg-white text-primary-navy' : '' }} flex  items-center justify-between px-2 h-[43px]  lg:text-[16px] xl:text-[17px] manrope-600 rounded-md hover:text-primary-navy group hover:bg-white text-gray-menu">
                            <p class="flex ">
                                <x-dynamic-component :component="$men['icon']" class="flex-shrink-0 w-6 h-6 mr-3 " />
                                {{ $men['text'] }}
                            </p>
                            @if ($men['count'] > 0)
                                <div
                                    class="flex-shrink-0 w-6 flex justify-center items-center text-[12px] h-6 text-white rounded-full text-14px bg-primary-blue">
                                    {{ $men['count'] }}</div>
                            @endif
                        </a>
                    @endforeach

                </nav>
            </div>
        </div>
    </div>
