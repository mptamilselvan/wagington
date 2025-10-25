<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
// use App\Traits\SMSTrait;
use App\Models\User;
use App\Models\Species;
use App\Models\Breed;
use App\Models\Pet as PetModel;
use Carbon\Carbon;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Livewire\TemporaryUploadedFile;
use Auth;
use App\Services\PetService;
use App\Traits\PetTrait;
use Illuminate\Support\Facades\Request;

class Pet extends Component
{
    use  WithFileUploads,PetTrait;

    protected $petService;

    public  $form = false, $list = true, $view = false, $popUp = false, $title = 'Pet Management', $search = NULL, $sort,$searchpet = '',
        $mode = 'add', $openFilter = false, $filter = false, $campaignDetails = false, $distributionType, $consultantcampCount, $campaignCount, $editId,
        $SearchplaceHoder = 'Search',$filterStatus,$firstSegment,$pet_id,$customer_id;

    public $user_id, $name, $profile_image,$src, $gender, $species_id, $breed_id, $color, $date_of_birth, $age_months,$age_year, $sterilisation_status, $created_by, $updated_by, $customers,$species,$breeds = [];

    public $deleteId;

    public  $filterGender = [],$filterArray = [],$filterSterilisation = [],$filterSpecies,$filterStartDate,$filterEndDate,$filterBreed,$filter_breed_option = [],$filterEvaluated = [];

    public function applyFilter()
    {
        $this->validate([
            'filterStartDate' => 'nullable|date|before_or_equal:today',
            'filterEndDate'   => 'nullable|date|after_or_equal:filterStartDate|before_or_equal:today',
        ], [
            'filterStartDate.before_or_equal' => 'Start date cannot be in the future.',
            'filterEndDate.before_or_equal'   => 'End date cannot be in the future.',
            'filterEndDate.after_or_equal'    => 'End date must be after or equal to start date.',
        ]);

        $this->filterArray = [
            'filterGender' => $this->filterGender,
            'filterSterilisation' => $this->filterSterilisation,
            'filterEvaluated' => $this->filterEvaluated,
            'filterSpecies' => $this->filterSpecies,
            'filterBreed' => $this->filterBreed,
            'filterStartDate' => $this->filterStartDate,
            'filterEndDate' => $this->filterEndDate,
        ];
        // $data = $this->petService->getPet(null,$this->searchpet,$filterArray);
        $this->openFilter = false;
        $this->filter = true;
    }

    public function resetFilter()
    {
        $this->filterSterilisation = [];
        $this->filterEvaluated = [];
        $this->filterGender = [];
        $this->filterSpecies = '';
        $this->filterBreed = '';
        $this->filterStartDate = '';
        $this->filterEndDate = '';
        $this->filter = false;

        $this->resetValidation('filterStartDate');
        $this->resetValidation('filterEndDate');
    }


    public function boot(PetService $petService)
    {
        $this->petService = $petService;
    }

    public function mount()
    {
        // $this->title = 'Pets';
        $this->firstSegment = request()->segment(1);
        $this->form = false;
        $this->list = true;
        $this->view = false;
        $this->campaignDetails = false;
        $this->editId = Request::query('id');
        $this->species = Species::select('id as value', 'name as option')->get()->toArray();

        if ($this->editId) {
            $this->edit($this->editId);
            session(['edit' => 'edit']);
        }

        session(['submenu' => 'pets']);
    }
    public function render()
    {
        $data = $this->petService->getPet(null,$this->searchpet,$this->filterArray);
        return view('livewire.backend.pet', ['pets' => $data['data']]);
    }
    
    public function index()
    {
        return view('backend.pet');
    }

    public function showForm()
    {
        $this->title = 'Add pet profile';
        $this->form = true;
        $this->list = false;
        $this->view = false;

        $this->customers = User::role('customer')->where('phone_verified_at','!=',null)->where('email_verified_at','!=',null)->get(['id', 'first_name', 'last_name', 'name', 'email'])->map(function ($user) {
            return [
                'value' => $user->id,
                'option' => $user->name, // calls getNameAttribute()
            ];
        })->toArray();
    }

    protected function messages()
    {
        return \App\Rules\PetRules::messages();
    }

    public function save()
    {
        $this->validate(\App\Rules\PetRules::rules($this->species_id));

        try {

            $result = $this->submitForm();

            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }            
            // $this->resetFields();
            return redirect()->route('admin.vaccination-records', ['id' => $result['pet']->id, 'customer_id' => $this->user_id]);

        } catch (Exception $e) {
            $e->getMessage();
        }
    }


    public function showFilter()
    {
        $this->openFilter = true;
    }

   
    public function closeFilter()
    {
        $this->filterStatus = '';
        $this->openFilter = false;
        $this->filter = false;
    }

    public function cancelPopUp()
    {
        $this->popUp = false;
    }
}
