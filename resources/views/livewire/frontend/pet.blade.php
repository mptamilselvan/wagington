
{{-- <main class="@if($list == true) max-w-6xl @endif mx-auto p-6"> --}}
<div class="relative min-h-screen bg-white">
<div class="w-full px-4 py-8 sm:px-6 lg:px-8 ">
  <div class="p-6 border border-gray-200 rounded-lg shadow-sm bg-gray-50 sm:p-8">
      @if($list == true || $listRecord == true)
      <div class="flex items-start mb-3">
        <a href="{{ route('home') }}" class="flex items-center mt-1 mr-3 text-gray-600 transition-colors hover:text-gray-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 sm:text-xl">{{ $title }}</h1>
            <p class="mt-2 text-gray-500">Add or edit your pet’s profile information</p>
        </div>
      </div>
      @endif
      <!-- Header -->
      @if($form == true)
        <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
            <div class="items-center justify-between h-20 md:flex">
              <!-- Back link -->
              <a href="{{route($firstSegment.'.pets') }}"
              class="inline-flex items-center justify-center rounded-full p-1.5 text-primary-blue hover:bg-gray-100 hover:text-blue-hover transition">
                  <x-icons.arrow.leftArrow class="w-4 h-4" />
                  <span class="sr-only">Back</span>
                <h1 class="px-4 text-2xl font-semibold text-gray-900 sm:text-xl sm:tracking-tight">{{ $title }}</h1>
              </a>

              <!-- Title -->
              @if ($editId != '')
                <div class="flex items-center justify-between sm:gap-4 sm:justify-end">
                  @component('components.button-component', [
                  'label' => 'Delete Pet',
                  'id' => 'list',
                  'type' => 'buttonSmallRed',
                  'wireClickFn' => 'deletePopUp('.$pet_id.')',
                  ])
                  @endcomponent
                </div>
              @endif
            </div>
        </div>
        {{-- <span class="pl-16 text-base font-normal text-gray-500 bg-white ">Add your pet's vaccination information. </span> --}}
      @endif

      @if($list == true)
        <!-- Pet Cards -->
        {{-- <div class="flex items-center justify-between p-4 mt-8 bg-white rounded-2xl">
          <p class="text-gray-500">{{ count($pets)?'You can add more pet profile.':'Go ahead and add your first pet profile' }}</p>
          @component('components.button-component', [
            'label' => 'Add Pet',
            'id' => 'list',
            'type' => 'buttonSmall',
            'wireClickFn' => 'showForm()',
            ])
            @endcomponent
        </div> --}}
        {{-- <div class="flex items-center justify-between"> --}}
         <div class="grid grid-cols-1 gap-4 mt-3 md:grid-cols-3">
            @forelse($pets as $pet)
              <div class="flex flex-col h-full p-4 bg-white shadow rounded-2xl">
                <div class="grid grid-cols-2 gap-4 mt-3 md:grid-cols-2">
                  <!-- Top Part: Name, Species, Age -->
                  <div class="mb-4">
                      <div class="flex items-center space-x-2">
                          <h2 class="text-lg font-bold text-gray-900">{{ $pet->name }}</h2>
                          @if($pet->evaluation_status == 'pass')
                              <x-icons.pets.verified/>
                          @endif
                      </div>
                      <p class="text-gray-500">{{ $pet->species->name }}</p>
                     <p class="text-sm text-gray-500">{{ $pet->age_year }} {{ $pet->age_months }} old</p>
                  </div>
                  <div class="flex items-center justify-end mb-4">
                      <img src="{{ $pet->profile_image ? env('DO_SPACES_URL').'/'.$pet->profile_image : 'https://placehold.co/80x80' }}" class="object-cover w-16 h-16 rounded-xl" alt="pet" />
                  </div>
                </div>
                <div class="grid grid-cols-6 mt-3 md:grid-cols-6">
                  <div class="flex flex-wrap col-span-5 gap-2 mt-auto">
                      <span class="px-3 py-1 text-sm text-blue-600 bg-blue-100 rounded-full">{{ $pet->breed ? $pet->breed->name : '-' }}</span>
                      <span class="px-3 py-1 text-sm text-blue-600 bg-blue-100 rounded-full">{{ ucfirst($pet->gender) }}</span>
                      <span class="px-3 py-1 text-sm text-blue-600 bg-blue-100 rounded-full">{{ $pet->sterilisation_status ? 'Sterilised' : 'Not Sterilised' }}</span>
                  </div>
                  <div class="flex items-center justify-end">
                    <svg class="w-5 h-5 text-gray-400 cursor-pointer" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" wire:click="edit({{ $pet->id }})">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                      </svg>
                  </div>
                  
                </div>
                  

              </div>
            @empty
             <div class="flex items-center justify-center w-full col-span-3 mt-20 mb-20">
                <div class="text-center">
                  <img src="{{ env('DO_SPACES_URL').'/'.'cat-and-dog.png' }}" class="object-cover h-60 w-60 rounded-xl" alt="pet" />
                  <p class="text-sm text-gray-500">No pet profile added yet, create your pet profile now.</p>
                </div>
              </div>
            @endforelse
          
        </div>
        <div class="flex items-center justify-between p-4 mt-8 bg-white rounded-2xl">
          <p class="text-gray-500">{{ count($pets)?'You can add more pet profile.':'Go ahead and add your first pet profile' }}</p>
          @component('components.button-component', [
            'label' => 'Add now',
            'id' => 'list',
            'type' => 'buttonSmall',
            'wireClickFn' => 'showForm()',
            ])
            @endcomponent
        </div>


        {{-- </div> --}}
        {{ $pets->links() }}
        
      @endif

      @if($listRecord == true)
        <div class="">

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <!-- Basic Information -->
              <a href="#" class="flex items-center justify-between p-4 transition bg-white border border-gray-200 shadow rounded-xl hover:shadow-md" wire:click="showForm()">
                  <div class="flex items-center space-x-3">
                      <x-icons.pets.basic_information class="p-2 text-lg rounded-lg bg-blue-50"/>
                      <span class="font-medium">Basic Information</span>
                  </div>
                  <span class="text-gray-500">➝</span>
              </a>
              <!-- Vaccination Records -->
              <a href="#"
                class="flex items-center justify-between p-4 transition bg-white border border-gray-200 shadow rounded-xl hover:shadow-md">
                  <div class="flex items-center space-x-3">
                      <x-icons.pets.vaccination class="p-2 text-lg rounded-lg bg-blue-50"/>
                      <span class="font-medium">Vaccination Records</span>
                  </div>
                  <span class="text-gray-500">➝</span>
              </a>
              <!-- Blood Test -->
              <a href="#"
                class="flex items-center justify-between p-4 transition bg-white border border-gray-200 shadow rounded-xl hover:shadow-md">
                  <div class="flex items-center space-x-3">
                      <x-icons.pets.blood_test class="p-2 text-lg rounded-lg bg-blue-50"/>
                      <span class="font-medium">Blood Test</span>
                  </div>
                  <span class="text-gray-500">➝</span>
              </a>

              <!-- De-worming and Parasite Treatment -->
              <a href="#"
                class="flex items-center justify-between p-4 transition bg-white border border-gray-200 shadow rounded-xl hover:shadow-md">
                  <div class="flex items-center space-x-3">
                      <x-icons.pets.deworming class="p-2 text-lg rounded-lg bg-blue-50"/>
                      <span class="font-medium">De-worming and Parasite Treatment</span>
                  </div>
                  <span class="text-gray-500">➝</span>
              </a>

              <!-- Medical History -->
              <a href="#"
                class="flex items-center justify-between p-4 transition bg-white border border-gray-200 shadow rounded-xl hover:shadow-md">
                  <div class="flex items-center space-x-3">
                      <x-icons.pets.medical_history class="p-2 text-lg rounded-lg bg-blue-50"/>
                      <span class="font-medium">Medical History</span>
                  </div>
                  <span class="text-gray-500">➝</span>
              </a>

              <!-- Dietary Preferences -->
              <a href="#"
                class="flex items-center justify-between p-4 transition bg-white border border-gray-200 shadow rounded-xl hover:shadow-md">
                  <div class="flex items-center space-x-3">
                      <x-icons.pets.dietary_preferences class="p-2 text-lg rounded-lg bg-blue-50"/>
                      <span class="font-medium">Dietary Preferences</span>
                  </div>
                  <span class="text-gray-500">➝</span>
              </a>

              <!-- Medication and Supplements -->
              <a href="#"
                class="flex items-center justify-between p-4 bg-white border-2 border-gray-200 shadow-md rounded-xl">
                  <div class="flex items-center space-x-3">
                      <x-icons.pets.medication_supplements class="p-2 text-lg rounded-lg bg-blue-50"/>
                      <span class="font-medium">Medication and Supplements</span>
                  </div>
                  <span class="text-blue-500">➝</span>
              </a>
          </div>
        </div>
      @endif


      @if($form == true)
        {{-- @include('includes/backend/pet_admin_sidebar') --}}
        <!-- Pet Form -->
        <div class="flex gap-3 mx-3 my-3">
          @if ($editId != '')
            <div class="w-1/5 p-4 bg-white">
              @component('components.subMenu', [
                  'active' => 'pets',
                  'subMenuType' => 'petAdmin',
                  'firstSegment' => $firstSegment,
                  'pet_id' => $pet_id,
                  'customer_id' => $customer_id,
              ])
              @endcomponent
            </div>
          @endif

          <div class="@if ($editId != '')w-4/5 @else w-full @endif p-4 bg-white">
            <div class="p-6 bg-white shadow-sm">
              @include('livewire.includes.pet.pet-form')
              <div class="grid grid-cols-2 gap-6">
                  @include('livewire.includes.pet.pet-evaluation', ['src' => $src])
              </div>
              <div class="flex items-center justify-end gap-2 mt-9">
                
                  @component('components.button-component', [
                        'label' => 'Cancel',
                        'id' => 'clear',
                        'type' => 'cancelSmall',
                        'wireClickFn' => 'resetFields',
                    ])
                    @endcomponent

                  @component('components.button-component', [
                  'label' => 'Save',
                  'id' => 'save',
                  'type' => 'submitSmall',
                  'wireClickFn' => 'save',
                  ])
                  @endcomponent
              </div>
            </div>
          </div>
        </div>
              
      @endif
      {{-- delete pop up --}}
    @if ($popUp == true)
        @component('components.popUpFolder.statusPopUp')
        @slot('content')
            Are you sure you want to delete the record?
        @endslot
        @slot('footer')
            <div class="flex items-center justify-end gap-2 mt-5">
                @component('components.button-component', [
                    'label' => 'Cancel',
                    'id' => 'cancel',
                    'type' => 'cancelSmall',
                    'wireClickFn' => '$set("popUp", false)',
                ])
                @endcomponent

                @component('components.button-component', [
                    'label' => 'Delete',
                    'id' => 'delete',
                    'type' => 'buttonSmall',
                    'wireClickFn' => 'delete',
                ])
                @endcomponent
            </div>
        @endslot
        @endcomponent
    @endif
    {{-- delete pop up end --}}
    </div>
  </div>
</div>