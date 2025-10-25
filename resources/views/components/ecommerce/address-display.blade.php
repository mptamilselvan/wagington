@props([
    'address',
    'title',
    'icon' => null
])

@if($address)
<div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
    <div class="flex items-start space-x-4">
        @if($icon)
            <div class="flex-shrink-0 mt-0.5">
                <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center">
                    {!! str_replace('w-5 h-5 text-gray-400', 'w-5 h-5 text-blue-600', $icon) !!}
                </div>
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                {{ $title }}
                @if($address->label)
                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full font-medium">{{ $address->label }}</span>
                @endif
            </h4>
            <div class="text-sm text-gray-600 space-y-2 leading-relaxed">
                <div class="flex items-start">
                    <svg class="w-4 h-4 mr-2 mt-0.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <div class="space-y-1">
                        <p class="font-medium text-gray-800">{{ $address->address_line1 }}</p>
                        @if($address->address_line2)
                            <p>{{ $address->address_line2 }}</p>
                        @endif
                        <p><span class="font-medium">{{ $address->country }}</span> {{ $address->postal_code }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif