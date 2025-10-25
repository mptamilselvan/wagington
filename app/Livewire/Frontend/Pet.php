<?php

namespace App\Livewire\Frontend;

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

    public  $form = false, $list = true, $view = false, $popUp = false, $title = 'Pet Profiles', $search = NULL, $sort,$deleteId,
        $mode = 'add', $openFilter = false, $filter = false, $campaignDetails = false, $distributionType, $consultantcampCount, $campaignCount, $editId,
        $SearchplaceHoder = 'Search',$filterStatus,$listRecord = false;

    public $user_id, $name, $profile_image,$src, $gender, $species_id, $breed_id, $color, $date_of_birth, $age_months,$age_year, $sterilisation_status, $created_by, $updated_by, $customers,$species,$breeds = [],$microchip_number, $length_cm, $height_cm, $weight_kg, $avs_license_number, $avs_license_expiry, $date_expiry, $document,$firstSegment,$pet_id,$customer_id,$src_document;


    public function boot(PetService $petService)
    {
        $this->petService = $petService;
    }

    public function mount()
    {
        $this->firstSegment = request()->segment(1);
        $this->page_title = 'Pet Profile';
        $this->form = false;
        $this->list = true;
        $this->listRecord = false;
        $this->view = false;
        $this->campaignDetails = false;
        $this->editId = Request::query('id');
        $this->user_id = Auth::user()->id;

        if ($this->editId) {
            $this->edit($this->editId);
        }

        session(['submenu' => 'pets']);
    }
    public function render()
    {
        $user_id = Auth::user()->id;
        $data = $this->petService->getPet($user_id);
        return view('livewire.frontend.pet', ['pets' => $data['data']]);
    }

    public function index()
    {
        return view('frontend.pet');
    }

    public function AddPetProfile(){
        $this->page_title = 'Add Pet Profile';
        $this->form = false;
        $this->listRecord = true;
        $this->list = false;
        $this->view = false;
    }

    public function showForm()
    {
        $this->page_title = 'Add pets';
        $this->form = true;
        $this->list = false;
        $this->listRecord = false;
        $this->view = false;
        $this->species = Species::select('id as value', 'name as option')->get()->toArray();
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
                $this->resetFields();
            } else {
                session()->flash('error', $result['message']);
            }
            
            $this->resetFields();
            // dd($this->user_id);
            return redirect()->route('customer.vaccination-records', ['id' => $result['pet']->id, 'customer_id' => $this->user_id]);

        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function dateExpiry()
    {
        $dateExpiry = Carbon::parse($this->date_expiry);

        if ($dateExpiry->isPast()) {
            $this->avs_license_expiry = "false";
        } else {
            $this->avs_license_expiry = "true";
        }
    }

    
    public function showFilter()
    {
        $this->openFilter = true;
    }

    public function resetFilter()
    {
        $this->filterStatus = '';
        $this->filter = false;
    }
    public function closeFilter()
    {
        $this->filterStatus = '';
        $this->openFilter = false;
        $this->filter = false;
    }

    public function applyFilter()
    {
        $this->openFilter = false;
        $this->filter = true;
    }

    public function cancelPopUp()
    {
        $this->popUp = false;
    }
}
