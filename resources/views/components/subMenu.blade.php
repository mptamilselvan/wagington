@if ($subMenuType == 'generalSetting')
    @props([
        'subMenu' => [
            ['key' => 'system-settings', 'value' => 'System Settings', 'route' => route('system-settings')],
            ['key' => 'company-settings', 'value' => 'Company Settings', 'route' => route('company-settings')],
            ['key' => 'operational-hours', 'value' => 'Operational Hours', 'route' => route('operational-hours')],
            ['key' => 'tax-settings', 'value' => 'Tax Settings', 'route' => route('tax-settings')],
        ],
        'active' => 'system-settings',
    ])
@endif

@if ($subMenuType == 'serviceSettings')
    @props([
        'subMenu' => [
            ['key' => 'service-category', 'value' => 'Service category', 'route' => route('admin.service-category')],
            ['key' => 'service-subcategory', 'value' => 'Service subcategory', 'route' => route('admin.service-subcategory')],
            ['key' => 'pool-setting', 'value' => 'Pool Setting', 'route' => route('admin.pool-settings')],
            ['key' => 'advance-duration', 'value' => 'Minimum Advance Duration', 'route' => route('admin.advance-duration')],
            ['key' => 'cancellation-refund', 'value' => 'Cancellation & Refund Settings', 'route' => route('admin.cancellation-refund')],
            ['key' => 'booking-slots', 'value' => 'Booking Slots', 'route' => route('admin.booking-slots')],
            ['key' => 'service-pricing-attributes', 'value' => 'Service Pricing Attributes', 'route' => route('admin.service-pricing-attributes')],
            ['key' => 'peak-seasons', 'value' => 'Peak Season', 'route' => route('peak-season')],
            ['key' => 'off-days', 'value' => 'Off Days', 'route' => route('off-days')],
            ['key' => 'booking-status-settings', 'value' => 'Booking stattus setting', 'route' => route('admin.booking-status-settings')],
        ],
        'active' => 'admin.service-category',
    ])
@endif

@if ($subMenuType == 'petSettings')
    @props([
        'subMenu' => [
            ['key' => 'species', 'value' => 'Species Management', 'route' => route('admin.species')],
            ['key' => 'breeds', 'value' => 'Breed Management', 'route' =>  route('admin.breeds')],
            ['key' => 'vaccination', 'value' => 'Vaccination Settings', 'route' => route('admin.vaccination')],
            ['key' => 'blood-tests', 'value' => 'Blood Test Settings', 'route' => route('admin.blood-tests')],
            ['key' => 'sizes', 'value' => 'Size Settings', 'route' => route('admin.sizes')],
            ['key' => 'pet-tags', 'value' => 'Pet Tag Workflow', 'route' => route('admin.pet-tags')],
            ['key' => 'vaccine-exemptions', 'value' => 'Vaccine Exemptions Settings', 'route' => route('admin.vaccine-exemptions')],
            ['key' => 'revaluation-workflow', 'value' => 'Revaluation Workflow Settings', 'route' => route('admin.revaluation-workflow')],
        ],
        'active' => 'species',
    ])
@endif

@if ($subMenuType == 'petAdmin')
    @php
        $subMenu = [
            ['key' => $firstSegment.'.pets', 'value' => 'Basic Information', 'route' => route($firstSegment.'.pets',['id' => $pet_id,'customer_id' => $customer_id])],
            ['key' => $firstSegment.'.vaccination-records', 'value' => 'Vaccination Records', 'route' => route($firstSegment.'.vaccination-records',['id' => $pet_id,'customer_id' => $customer_id])],
            ['key' => $firstSegment.'.blood-test-records', 'value' => 'Blood test records', 'route' => route($firstSegment.'.blood-test-records',['id' => $pet_id,'customer_id' => $customer_id])],
            ['key' => $firstSegment.'.deworming-records', 'value' => 'Deworming & Parasite Treatment', 'route' => route($firstSegment.'.deworming-records',['id' => $pet_id,'customer_id' => $customer_id])],
            ['key' => $firstSegment.'.medical-history-records', 'value' => 'Medical History', 'route' => route($firstSegment.'.medical-history-records',['id' => $pet_id,'customer_id' => $customer_id])],
            ['key' => $firstSegment.'.dietary-preferences', 'value' => 'Dietary Preferences', 'route' => route($firstSegment.'.dietary-preferences',['id' => $pet_id,'customer_id' => $customer_id])],
            ['key' => $firstSegment.'.medication-supplements', 'value' => 'Medication and Supplements', 'route' => route($firstSegment.'.medication-supplements',['id' => $pet_id,'customer_id' => $customer_id])],
        ];

        if (strtolower($firstSegment) == 'admin') {
            $subMenu[] = ['key' => $firstSegment.'.temperament-health-evaluations', 'value' => 'Temperament Health Evaluation', 'route' => route($firstSegment.'.temperament-health-evaluations',['id' => $pet_id,'customer_id' => $customer_id])];
            $subMenu[] = ['key' => $firstSegment.'.size-managements', 'value' => 'Size Management', 'route' => route($firstSegment.'.size-managements',['id' => $pet_id,'customer_id' => $customer_id])];
        }
    @endphp

    @props([
        'subMenu' => $subMenu,
        'active' => 'admin.vaccination-records',
    ])
@endif

@if ($subMenuType == 'roomSettings')
    @props([
        'subMenu' => [
            ['key' => 'pet-size-limit-settings', 'value' => 'Pet Size Limit Settings', 'route' => route('pet-size-limit-settings')],
            ['key' => 'room-peak-seasons', 'value' => 'Room Peak Seasons', 'route' => route('room-peak-seasons')],
            ['key' => 'room-off-days', 'value' => 'Room Off Days', 'route' => route('room-off-days')],
            ['key' => 'room-price-options', 'value' => 'Room Price Options', 'route' => route('room-price-options')],
            ['key' => 'room-cancel-setting', 'value' => 'Room Cancel Setting', 'route' => route('room-cancel-setting')],
        ],
        'active' => $active ?? 'pet-size-limit-settings',
    ])
@endif

<div x-data="{ openTab: '{{ $active }}' }" class="bg-[#FFF]  v-wrapper h-full z-10 nav-left">

    <div class="pb-60 pt-5 text-[#232323] space-y-[30px]">

        @foreach ($subMenu as $sub)
            <button class="w-full button-strip hover:text-primary-blue"
                @if (isset($wireClickFn)) wire:click="{{ $wireClickFn($sub['key']) }}" @endif
                @if (isset($onClickFn)) onclick='{{ $onClickFn(
                    $sub['key'],) }}' @endif>
                <a href="{{ $sub['route'] }}">

                    <div class="flex items-center text-left  justify-between px-4 sm:px-6 md:px-10  lg:px-[10px] text-[12px] sm:text-[12.8px] md:text-[14px] lg:text-[14px] @if(str_contains($sub['key'],Session::get('submenu'))) text-[#1B85F3] bg-blue-50 h-10 font-semibold rounded-lg @else text-gray-500 @endif"
                        >
                        {{ $sub['value'] }}
                         
                    </div>

                </a>

            </button>
        @endforeach

    </div>


</div>