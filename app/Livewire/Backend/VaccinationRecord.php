<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Auth;
use Illuminate\Support\Facades\Request;
// use App\Models\VaccinationRecord as VaccinationRecordModel;
use App\Models\Vaccination;
use Livewire\WithFileUploads;
// use App\Services\VaccinationRecordService;
use App\Traits\VaccinationRecordTrait;
use App\Models\Pet;
use App\Models\VaccineExemption;

class VaccinationRecord extends Component
{
    use WithFileUploads,VaccinationRecordTrait;

    // protected $vaccinationRecordService;

    public $pet_id, $vaccination_id, $date, $document, $notes, $cannot_vaccinate = 0, $created_by,$updated_by,$src,$customer_id;
    public $vaccinationRecord,$vaccinations;
    public $editId = null, $deleteId = null,$firstSegment,$vaccine_exemption;

    public  $popUp = false, $title = 'Add pet profile';

    public function mount()
    {
        $this->firstSegment = request()->segment(1);
        $this->pet_id = Request::route('id');
        if(\Session::get('edit') == "edit")
        {
            $this->title = 'Edit pet profile';
        }
        $this->customer_id = Request::route('customer_id');

        $pet = Pet::with('species')->find($this->pet_id);

        $vaccine_exemption = VaccineExemption::where('species_id', $pet->species_id)->first();

        if($vaccine_exemption)
        {
            $this->vaccine_exemption = $vaccine_exemption->blood_test_names_string;
        }

        $this->vaccinations = Vaccination::where('species_id', $pet->species_id)->select('id as value','name as option')->where('is_active',true)->get()->toArray();

        session(['submenu' => 'vaccination-records']);
    }

    public function render()
    {
        try {
            $result = $this->vaccinationRecordService->getVaccinationRecord($this->pet_id);

            if ($result['status'] === 'success') {
                // Get vaccination-record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            
            return view('livewire.backend.vaccination-record', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        if(request()->segment(1) == 'admin')
        {
            return view('backend.vaccination-record');
        }
        return view('frontend.vaccination-record');
    }
}