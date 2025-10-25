<div style="background-color: #FAFAFA">
    @component('components.header', [
        'title' => $title,
        'type' => 'titleOnly',
        'height' => 'h-[90px] lg:h-[72px]',
        // 'link'=>( ),
        // 'count'=>($list == true ? : '' )
        'distributionType' => $distributionType,
    ])
    @endcomponent

    {{-- alert --}}
    @component('components.alert-component')
    @endcomponent

    <div class="overflow-hidden x-lay w-min-screen mt-3" style="background-color: #FAFAFA">

    <div class="flex gap-2">
        <div class="flex-none w-52">
             @component('components.subMenu', [
                        'active' => 'campaignDetail',
                        'classNames' => 'top-[160px] sm:top-[130px] md:top-[145px] lg:top-[162px]',
                        'distributionType' => $distributionType,
                        'subMenuType' => 'petSettings',
                    ])
                @endcomponent
        </div>
        <div class="flex-initial w-full bg-white px-10 py-10">
                <div class="">

                    @component('components.textbox-component', [
                        'wireModel' => 'name',
                        'id' => 'name',
                        'label' => 'Name',
                        'star' => true,      
                        'placeholder' => 'Enter name',              
                        'error' => $errors->first('name'),
                    ])
                    @endcomponent

                    <div>
                        @component('components.textarea-component', [
                            'wireModel' => 'description',
                            'id' => 'description',
                            'rows' => 5,
                            'label' => 'Description',                            
                            'star' => false,
                            'placeholder' => 'Type here',  
                            'error' => $errors->first('description'),
                        ])
                        @endcomponent
                    </div>
                    
                    <div>
                        @component('components.photo-upload-component', [
                            'wireModel' => 'photo',
                            'id' => 'photos',                          
                            'label' => 'photos',                            
                            'star' => false,
                            'placeholder' => 'Type here',  
                            'error' => $errors->first('description'),
                        ])
                        @endcomponent
                        <img src="https://wagginton-staging.sgp1.digitaloceanspaces.com/test/yER8ktoLdHx9UrD69g9Zk8TXepJPdLdiGMpdSgXd.jpg" class="w-10">
                    </div>

                    <div class="flex justify-end gap-2 items-center ">
                            @component('components.button-component', [
                                'label' => 'Clear',
                                'id' => 'clear',
                                'type' => 'cancelSmall',
                                'wireClickFn' => 'resetFields',
                            ])
                            @endcomponent

                            @component('components.button-component', [
                                'label' => 'Save',
                                'id' => 'save',
                                'type' => 'buttonSmall',
                                'wireClickFn' => 'save',
                            ])
                            @endcomponent
                    </div>
                    <div class="mt-[28px] table-wrapper">
                        <table class="table">
                            <thead class="bg-[#F9FAFB]">
                                <th class="th">Image</th>
                                <th class="th">Name</th>
                                <th class="th">Description</th>     
                            </thead>
                            <tbody>
                                 @foreach ($species_data as $species)                                   
                                        <tr>
                                            <td class="td">
                                                {{ ucfirst($species->name) }}
                                            </td>
                                            <td class="td">
                                                {{ ucfirst($species->name) }}
                                            </td>
                                            <td class="td">{{ $species->description }}</td>
                                            <td class="td hover:text-blue-hover">
                                                @role('Admin')
                                                    <a href="#"><svg
                                                            wire:click='showCampaignDetails({{ $species->id }})'
                                                            xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                            class="w-6 h-6">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                        </svg>
                                                    </a>
                                                @else
                                                    <a href="{{ 'leads/user/' . $species->id }}"><svg
                                                            xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                            class="w-6 h-6">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                        </svg>
                                                    </a>
                                                @endrole
                                            </td>
                                        </tr>                                    
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>        
    </div>


     
                




       {{-- delete pop up --}}
        @if ($popUp == true)
            @component('components.popUpFolder.statusPopUp')
                @slot('content')
                    <div class="justify-center items-center flex-auto p-[20px] md:p-[60px] text-center">

                        <h2 class="manrope-600 text-[20px] md:text-[24px] text-[#232323] ">Are you sure you want to
                            delete the campaign</h2>
                    </div>
                @endslot
                @slot('footer')
                    <div
                        class=" h-[97px] flex gap-3 sm:gap-6 md:gap-10 justify-center items-center  mt-2 rounded-b-[20px]  bg-[#FAFAFA] ">
                        <button wire:click='cancelPopUp' class="text-button-green">
                            Cancel
                        </button>

                        <button wire:click='deleteCampaignDetails' class="button-primary-red-small">
                            Delete
                        </button>
                    </div>
                @endslot
            @endcomponent
        @endif
        {{-- delete pop up end --}}

    </div>
</div>
