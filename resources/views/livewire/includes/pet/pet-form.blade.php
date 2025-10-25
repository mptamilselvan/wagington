<div class="py-6 mt-[10px]">
    @component('components.photo-upload-component', [
        'wireModel' => 'profile_image',
        'id' => 'profile_image',
        'label' => 'Profile Image',
        'star' => true,
        'src' => $src,
        'error' => $errors->first('profile_image'),
    ])
    @endcomponent

    <div class="grid grid-cols-2 gap-6">
        @if(Auth::user()->hasRole('admin'))
            @component('components.dropdown-component', [
                'wireModel' => 'user_id',
                'id' => 'user_id',
                'label' => 'Customer',
                'star' => true,
                'options' => $customers,
                'error' => $errors->first('user_id'),
                'placeholder_text' => "Select customer" 
            ])
            @endcomponent
        @else
            <input type="hidden" name="user_id" wire:model="user_id" value="{{ Auth::user()->id }}">
        @endif
        
        @component('components.textbox-component', [
            'wireModel' => 'name',
            'id' => 'name',
            'label' => "Pet's Name",
            'star' => true,
            'error' => $errors->first('name'),
            'placeholder' => "Enter your pet's name"
        ])
        @endcomponent

        @component('components.dropdown-component', [
            'wireModel' => 'gender',
            'id' => 'gender',
            'label' => 'Gender',
            'star' => true,
            'options' => [['value' => 'male','option' => 'Male'],['value' => 'female','option' => 'Female']],
            'error' => $errors->first('gender'),
            'placeholder_text' => "Select Gender"
        ])
        @endcomponent

        @component('components.dropdown-component', [
            'wireModel' => 'species_id',
            'id' => 'species_id',
            'label' => 'Species',
            'star' => true,
            'options' => $species,
            'wireChangeFn' => 'changeSpecies()',
            'error' => $errors->first('species_id'),
            'placeholder_text' => "Select Species"
        ])
        @endcomponent

        @component('components.dropdown-component', [
            'wireModel' => 'breed_id',
            'id' => 'breed_id',
            'label' => 'Breed',
            'star' => true,
            'options' => $breeds,
            'placeholder_text' => "Select breed",
            'error' => $errors->first('breed_id'),
        ])
        @endcomponent

        @component('components.textbox-component', [
            'wireModel' => 'color',
            'id' => 'color',
            'label' => 'Color',
            'star' => true,
            'error' => $errors->first('color'),
            'placeholder' => "Enter your pet's color"
        ])
        @endcomponent
        
        @component('components.date-component', [
            'wireModel' => 'date_of_birth',
            'id' => 'date_of_birth',
            'label' => 'Date of birth',
            'star' => true,
            'readonly' => false,
            'wireChangeFn' => 'getAge()',
            'error' => $errors->first('date_of_birth'),
            'max' => date('Y-m-d')
            ])
        @endcomponent

        <div>
            <label  class="block mb-2 text-gray-700">Age</label>
            <div class="flex items-center">
                <!-- Month Input -->
                <input type="text" placeholder="year" class="w-1/2 form-input" name="age_year" wire:model='age_year' readonly />

                <!-- Separator -->
                {{-- <span class="text-gray-400 select-none">|</span> --}}

                <!-- Year Input -->
                <input type="text" placeholder="Month" class="w-1/2 form-input" name="age_months" wire:model='age_months' readonly />
            </div>
                <span class="text-sm text-gray-400">Age is auto-populated when DOB is entered.</span>
        </div>

        
        
        {{-- @component('components.textbox-component', [
            'wireModel' => 'age_months',
            'id' => 'age_months',
            'label' => 'age (Months)',
            'readonly' => 'true',
            'placeholder_text' => 'Age is auto-populated when DOB is entered.',
            'placeholder' => "Age"
            ])
        @endcomponent --}}

        @component('components.dropdown-component', [
            'wireModel' => 'sterilisation_status',
            'id' => 'sterilisation_status',
            'label' => 'Sterilisation status',
            'star' => true,
            'options' => [['value' => 'true','option' => 'True'],['value' => 'false','option' => 'False']],
            'error' => $errors->first('sterilisation_status'),
            'placeholder_text' => "Select pet's sterilisation status"
        ])
        @endcomponent
    </div>
</div>

