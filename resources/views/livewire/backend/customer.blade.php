<main class="lg:pl-72 bg-gray-50">
    @if ($this->form == true)
        <!-- Add Customer Form - Full Width Matching Figma -->
        <div class="min-h-screen bg-gray-50">
            <!-- Header Container - Boxed like Form -->
            <div class="px-4 pt-2 pb-4 sm:px-6 lg:px-8">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="px-4 py-4 my-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <button wire:click="goBack" 
                                        class="p-1 mr-3 transition-colors duration-200 rounded-full hover:bg-gray-100"
                                        title="{{ $currentStep > 1 ? 'Go to previous step' : 'Back to customer list' }}">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <div>
                                    <h1 class="text-xl font-semibold text-gray-900">
                                        {{ $mode === 'edit' ? 'Update customer' : 'Add customer' }}
                                    </h1>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Flash Messages for Form -->
            <div class="px-4 pb-4 sm:px-6 lg:px-8">
                @if (session()->has('message'))
                    <x-success-modal :title="'Successfully updated!'" :message="session('message')" :duration="5000" />
                @endif

                @if(session()->has('address_success'))
                    <x-success-modal :title="'Successfully updated!'" :message="session('address_success')" :duration="5000" />
                @endif

                @if (session()->has('error'))
                    <div class="p-4 mb-4 text-red-700 bg-red-100 border border-red-400 rounded-md">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session()->has('address_error'))
                    <div class="p-4 mb-4 text-red-700 bg-red-100 border border-red-400 rounded-md">
                        {{ session('address_error') }}
                    </div>
                @endif
            </div>

            <!-- Form Content - Single Container with Tabs Inside -->
            <div class="px-4 pb-8 sm:px-6 lg:px-8">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                    <!-- Tab Navigation Inside Form Container -->
                    <nav class="flex px-6 space-x-8 border-b border-gray-200">
                         <button wire:click="setCurrentStep(1)" class="py-4 px-1 border-b-2 {{ $currentStep == 1 ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }} font-medium text-sm whitespace-nowrap">
                            Customer Profile
                        </button>
                        <button wire:click="setCurrentStep(2)" class="py-4 px-1 border-b-2 {{ $currentStep == 2 ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }} font-medium text-sm whitespace-nowrap">
                            Secondary Contact
                        </button>
                         <button wire:click="setCurrentStep(3)" class="py-4 px-1 border-b-2 {{ $currentStep == 3 ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }} font-medium text-sm whitespace-nowrap">
                            Addresses  
                        </button>
                    </nav>
                    
                    <!-- Form Content -->
                    <div class="px-6 py-8">
                        @if($currentStep == 1)
                            @include('livewire.backend.step1')
                        @elseif($currentStep == 2)
                            @include('livewire.backend.step2')
                        @elseif($currentStep == 3)
                            @include('livewire.backend.step3')
                        @endif
                    </div>
                </div>
            </div>
            

            
            <!-- Include OTP Modal -->
            @include('livewire.backend.otp-modal')
        </div>
    @elseif ($this->view == true)
        <!-- Customer View Mode - Read-Only Details -->
        <div class="min-h-screen bg-gray-50">
            <!-- Header Container -->
            <div class="px-4 pt-5 pb-4 sm:px-6 lg:px-8">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="px-4 py-2 mt-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <button wire:click="goBack" 
                                        class="p-1 mr-3 transition-colors duration-200 rounded-full hover:bg-gray-100"
                                        title="Back to customer list">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <div>
                                    <h1 class="text-xl font-semibold text-gray-900">Customer Details</h1>
                                    <p class="text-sm text-gray-500">{{ $first_name }} {{ $last_name }}</p>
                                </div>
                            </div>
                            <!-- Status Display -->
                            <div class="flex items-center">
                                <span class="px-3 py-1 {{ $this->isCustomerActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} text-sm font-medium rounded-full">
                                    {{ $this->isCustomerActive ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Details Content -->
            <div class="px-4 pb-8 sm:px-6 lg:px-8">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="px-4 py-2 mt-3">
                        <!-- Customer Information Grid -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Basic Information -->
                            <div class="space-y-4">
                                <h3 class="pb-2 text-lg font-medium text-gray-900 border-b">Basic Information</h3>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">First Name</label>
                                        <p class="text-sm text-gray-900">{{ $first_name ?: 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Last Name</label>
                                        <p class="text-sm text-gray-900">{{ $last_name ?: 'N/A' }}</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Email</label>
                                    <p class="text-sm text-gray-900">{{ $email ?: 'N/A' }}</p>
                                </div>
                                 <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Referal Id</label>
                                    <p class="text-sm text-gray-900">{{ $referal_code ?: 'N/A' }}</p>
                                </div>
</div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Country Code</label>
                                        <p class="text-sm text-gray-900">{{ $country_code ?: 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Phone</label>
                                        <p class="text-sm text-gray-900">{{ $phone ?: 'N/A' }}</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Date of Birth</label>
                                    <p class="text-sm text-gray-900">{{ $dob ?: 'N/A' }}</p>
                                </div>
                                
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Passport/NRIC/FIN</label>
                                    <p class="text-sm text-gray-900">{{ $passport_nric_fin_number ?: 'N/A' }}</p>
                                </div>
                                </div>

                               
                            </div>

                            <!-- Secondary Contact -->
                            <div class="space-y-4">
                                <h3 class="pb-2 text-lg font-medium text-gray-900 border-b">Secondary Contact</h3>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">First Name</label>
                                        <p class="text-sm text-gray-900">{{ $secondary_first_name ?: 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Last Name</label>
                                        <p class="text-sm text-gray-900">{{ $secondary_last_name ?: 'N/A' }}</p>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Email</label>
                                    <p class="text-sm text-gray-900">{{ $secondary_email ?: 'N/A' }}</p>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Country Code</label>
                                        <p class="text-sm text-gray-900">{{ $secondary_country_code ?: 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Phone</label>
                                        <p class="text-sm text-gray-900">{{ $secondary_phone ?: 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Addresses Section -->
                        @if(!empty($addresses))
                        <div class="mt-8">
                            <h3 class="pb-2 mb-4 text-lg font-medium text-gray-900 border-b">Addresses</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                @foreach($addresses as $address)
                                <div class="p-4 border border-gray-200 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900">{{ $address['label'] ?? 'Address' }}</h4>
                                        <div class="flex space-x-2">
                                            @if($address['is_billing_address'] ?? false)
                                                <span class="px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded">Billing</span>
                                            @endif
                                            @if($address['is_shipping_address'] ?? false)
                                                <span class="px-2 py-1 text-xs text-green-800 bg-green-100 rounded">Shipping</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="space-y-1 text-sm text-gray-600">
                                        <p>{{ $address['address_line1'] ?? 'N/A' }}</p>
                                        @if($address['address_line2'])
                                            <p>{{ $address['address_line2'] }}</p>
                                        @endif
                                        <p>{{ $address['country'] ?? 'N/A' }} {{ $address['postal_code'] ?? '' }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif


                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Main Container with Proper Background -->
        <div class="min-h-screen bg-light-gray">
            <!-- Flash Messages -->
            @if (session()->has('message'))
                <x-success-modal :title="'Successfully updated!'" :message="session('message')" :duration="5000" />
            @endif
            
            @if (session()->has('error'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-init="setTimeout(() => show = false, 4000)"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform translate-y-2"
                     class="p-4 mx-6 mb-4 text-red-700 bg-red-100 border border-red-400 rounded-md">
                    {{ session('error') }}
                </div>
            @endif
            
            <!-- Page Title Container - Aligned with Sidebar -->
            <div class="px-4 py-2 mb-2 sm:px-6">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="px-4 py-4 my-2">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <h1 class="font-semibold text-gray-900" style="font-size:20px;">Customers</h1>
                            <div class="flex flex-wrap items-center w-full gap-2 lg:w-auto">
                                <!-- Search Input -->
                                <div class="relative flex-1 min-w-[200px]">
                                    <input type="text" wire:model.live="search" 
                                        class="w-full px-4 py-2 pl-10 pr-4 text-sm border border-gray-300 rounded-lg sm:w-80 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Search by name, mobile number, email">
                                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                
                                <!-- Filter Button -->
                                <button wire:click="toggleFilterModal" 
                                    class="flex items-center justify-center flex-none px-4 py-2 text-sm text-gray-500 transition-colors border border-gray-300 rounded-lg">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                    </svg>
                                    Filter By
                                </button>
                                
                                <!-- Add Button - Matching Figma Blue -->
                                <button wire:click="showForm" 
                                    class="button-primary bg-[#1B85F3] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]" style="background-color: #4785FF;">
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Container - Separate with Gap -->
            <div class="px-6 pb-6">
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-white">
                                    <tr>
                                        <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Image</th>
                                        <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">First Name</th>
                                        <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Last Name</th>
                                        <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Mobile Number</th>
                                        <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">E-Mail Address</th>
                                        <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Pets</th>
                                        <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Status</th>
                                        <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Referrals</th>
                                        <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Bookings</th>
                                        <th class="px-6 py-4 text-sm font-normal text-left text-gray-500"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                        @forelse($customers as $customer)
                                    <tr class="hover:bg-gray-100 transition-colors {{ $customer->trashed() ? 'bg-red-50 opacity-75' : ($loop->odd ? 'bg-gray-50' : 'bg-white') }}" style="border-bottom: 1px solid #F3F4F6;">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($customer->image)
                                                <img src="{{ $customer->profile_image_url }}" alt="{{ $customer->first_name }}" 
                                                     class="object-cover w-10 h-10 rounded-full">
                                            @else
                                                <div class="flex items-center justify-center w-10 h-10 text-sm font-medium text-white bg-blue-500 rounded-full">
                                                    {{ strtoupper(substr($customer->first_name, 0, 1) . substr($customer->last_name, 0, 1)) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $customer->first_name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $customer->last_name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap"> {{ $customer->country_code }} {{ $customer->phone }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $customer->email }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $customer->pet_count }} </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($customer->trashed())
                                                <div class="flex items-center">
                                                    <div class="w-2 h-2 mr-2 bg-red-600 rounded-full"></div>
                                                    <span class="text-sm font-medium text-red-700">Deleted</span>
                                                </div>
                                            @elseif($customer->is_customer_active)
                                                <div class="flex items-center">
                                                    <div class="w-2 h-2 mr-2 bg-green-500 rounded-full"></div>
                                                    <span class="text-sm text-gray-700">Active</span>
                                                </div>
                                            @else
                                                <div class="flex items-center">
                                                    <div class="w-2 h-2 mr-2 bg-red-500 rounded-full"></div>
                                                    <span class="text-sm text-gray-700">Inactive</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $customer->referred_users_count }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">0</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                            <x-action-menu :direction="$loop->last ? 'up' : null">
                                                <x-action-menu-item wire:click="viewCustomer({{ $customer->id }})" icon="eye">
                                                    View
                                                </x-action-menu-item>
                                                
                                                @if(!$customer->trashed())
                                                    <x-action-menu-item wire:click="editCustomer({{ $customer->id }})" icon="edit">
                                                        Update
                                                    </x-action-menu-item>
                                                    
                                                    <x-action-menu-item 
                                                        wire:click="toggleStatus({{ $customer->id }})" 
                                                        icon="{{ $customer->is_active ? 'x' : 'check' }}"
                                                        variant="{{ $customer->is_active ? 'warning' : 'success' }}">
                                                        {{ $customer->is_active ? 'Deactivate' : 'Activate' }}
                                                    </x-action-menu-item>
                                                @else
                                                    <x-action-menu-item 
                                                        wire:click="restoreCustomer({{ $customer->id }})" 
                                                        icon="refresh"
                                                        variant="success">
                                                        Restore Account
                                                    </x-action-menu-item>
                                                @endif
                                            </x-action-menu>
                                        </td>
                        </tr>
                        @empty
                        <tr class="bg-white">
                            <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <h3 class="mb-2 text-lg font-medium text-gray-900">No customers found</h3>
                                    <p class="mb-4 text-gray-500">Get started by adding your first customer.</p>
                                    <button wire:click="showForm" 
                                        class="px-4 py-2 text-white bg-blue-500 rounded-md hover:bg-blue-600">
                                        Add Customer
                                    </button>
                                </div>
                            </td>
                        </tr>
                                        @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination - Figma Design (Left Aligned) -->
                        <div class="px-6 py-4 border-t" style="border-color: #F3F4F6; background-color: #FAFBFC;">
                            <div class="flex items-center justify-start space-x-6">
                                <!-- Results info -->
                                <div class="text-sm text-gray-600">
                                    Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} results
                                </div>
                                
                                <!-- Pagination controls -->
                                @if($customers->hasPages())
                                <div class="flex items-center space-x-1">
                        <!-- Previous Button -->
                        @if($customers->onFirstPage())
                            <button disabled class="px-3 py-2 text-gray-400 cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                        @else
                            <button wire:click="previousPage" class="px-3 py-2 text-gray-600 transition-colors rounded-md hover:text-gray-900 hover:bg-gray-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                        @endif
                        
                        <!-- Page Numbers (limit to show max 5 pages) -->
                        @php
                            $start = max(1, $customers->currentPage() - 2);
                            $end = min($customers->lastPage(), $customers->currentPage() + 2);
                        @endphp
                        
                        @for($page = $start; $page <= $end; $page++)
                            @if($page == $customers->currentPage())
                                <button class="px-3 py-2 text-sm font-medium text-white rounded-md" style="background-color: #4785FF;">
                                    {{ $page }}
                                </button>
                            @else
                                <button wire:click="gotoPage({{ $page }})" 
                                    class="px-3 py-2 text-sm text-gray-600 transition-colors rounded-md hover:text-gray-900 hover:bg-gray-100">
                                    {{ $page }}
                                </button>
                            @endif
                        @endfor
                        
                        @if($end < $customers->lastPage())
                            <span class="px-2 text-gray-500">...</span>
                        @endif
                        
                        <!-- Next Button -->
                        @if($customers->hasMorePages())
                            <button wire:click="nextPage" class="px-3 py-2 text-gray-600 transition-colors rounded-md hover:text-gray-900 hover:bg-gray-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        @else
                            <button disabled class="px-3 py-2 text-gray-400 cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                                        @endif
                                    </div>
                                    @endif
                                    
                                    <!-- Page Size Selector -->
                                    <div class="flex items-center space-x-2">
                                        <select wire:model.live="perPage" 
                                            class="w-16 px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                        <span class="text-sm text-gray-600">/Page</span>
                                    </div>
                                </div>
                            </div>
        </div>
        
        <!-- Filter Modal - Figma Design -->
        @if($showFilterModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showFilterModal') }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" 
                     wire:click="toggleFilterModal"></div>
                
                <!-- Modal panel -->
                <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-white border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Filter</h3>
                            <button wire:click="toggleFilterModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Modal Body -->
                    <div class="px-6 py-6 space-y-6 bg-white">
                        <!-- Verification Status -->
                        <div>
                            <h4 class="mb-3 text-sm font-medium text-gray-900">Verification status</h4>
                            <div class="flex items-center space-x-6">
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="filterVerification" value="mobile_verified"
                                        class="text-blue-600 border-gray-300 rounded shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Mobile Verified</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="filterVerification" value="email_verified"
                                        class="text-blue-600 border-gray-300 rounded shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Email Verified</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div>
                            <h4 class="mb-3 text-sm font-medium text-gray-900">Status</h4>
                            <div class="flex items-center space-x-6">
                                <label class="flex items-center">
                                    <input type="radio" wire:model="filterStatus" value="active" name="status"
                                        class="text-blue-600 border-gray-300 focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Active</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" wire:model="filterStatus" value="inactive" name="status"
                                        class="text-blue-600 border-gray-300 focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Inactive</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="filterDeleted" value="deleted"
                                        class="text-red-600 border-gray-300 rounded shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-red-700">Deleted Accounts</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Species -->
                        <div>
                            <h4 class="mb-3 text-sm font-medium text-gray-900">Species</h4>
                            <div>
                                <select wire:model.live="filterSpecies" multiple 
                                    class="w-full text-sm text-gray-700 border border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <option value="">Select species</option>
                                    @foreach($species as $speciesItem)
                                        <option value="{{ $speciesItem->id }}">{{ $speciesItem->name }}</option>
                                    @endforeach
                                </select>
                                
                                <!-- Selected Species Tags -->
                                @if(!empty($filterSpecies))
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        @foreach($filterSpecies as $selectedSpeciesId)
                                            @php
                                                $speciesName = $species->where('id', $selectedSpeciesId)->first()?->name ?? 'Unknown';
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-md">
                                                {{ $speciesName }}
                                                <button wire:click="removeSpeciesFilter({{ $selectedSpeciesId }})" 
                                                    type="button"
                                                    class="inline-flex items-center p-0.5 ml-2 text-sm text-blue-400 bg-transparent rounded-sm hover:bg-blue-200 hover:text-blue-600 focus:outline-none focus:bg-blue-200 focus:text-blue-600">
                                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                                    </svg>
                                                    <span class="sr-only">Remove species filter</span>
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                
                                <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple species</p>
                            </div>
                        </div>

                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="flex justify-end px-6 py-4 space-x-3 bg-gray-50">
                        <button wire:click="clearFilters" 
                            class="button bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] lg:text-[14px]">
                            Clear
                        </button>
                        <button wire:click="applyFilters" 
                            class="flex-1 px-6 py-2 text-sm font-medium text-white button-primary-small bg-[#1B85F3] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
                </div> <!-- Close Table Container -->
            </div> <!-- Close Content Container -->
        </div> <!-- Close Main Container -->
    @endif
</main>

@script
<script>
    // Alpine.js data for verification UI feedback
    Alpine.data('verificationDebouncer', () => ({
        init() {
            // Listen for verification success and refresh component only
            $wire.on('otp-verified-refresh-page', () => {
                // Immediate component refresh after successful OTP verification
                $wire.$refresh();
            });
        }
    }));
</script>
@endscript