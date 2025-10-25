<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Auth;
use Illuminate\Support\Facades\Request;
use App\Models\BloodTest;
use App\Models\Pet;
use App\Models\VaccineExemption;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use App\Services\BloodTestRecordService;
use App\Traits\BloodTestRecordTrait;

class BloodTestRecord extends Component
{
    use WithFileUploads, BloodTestRecordTrait;

    protected $BloodTestRecordService;

    public $pet_id, $blood_test_id, $date, $document, $notes, $status, $created_by,$updated_by,$src,$customer_id;
    public $BloodTestRecord,$bloodTest;
    public $editId = null, $deleteId = null,$firstSegment,$bloodTestNames,$pet,$vaccine_exemption;

    public  $popUp = false, $title = 'Add pet profile';

    public function boot(BloodTestRecordService $BloodTestRecordService)
    {
        $this->BloodTestRecordService = $BloodTestRecordService;
    }

    public function mount()
    {
        $this->firstSegment = request()->segment(1);
        $this->pet_id = Request::route('id');
        $this->customer_id = Request::route('customer_id');
        $this->pet = Pet::with('species')->find($this->pet_id);
        if(\Session::get('edit') == "edit")
        {
            $this->title = 'Edit pet profile';
        }

        $vaccine_exemption = VaccineExemption::where('species_id', $this->pet->species_id)->first();

        if($vaccine_exemption)
        {
            $this->vaccine_exemption = $vaccine_exemption->blood_test_names_string;
        }
        // ->map(fn($item) => $item->blood_test->name);

        $this->bloodTest = BloodTest::where('species_id', $this->pet->species_id)->select('id as value','name as option')->where('is_active',true)->get()->toArray();

        $this->bloodTestNames = collect($this->bloodTest)
            ->pluck('option')
            ->implode(', ');

        \session(['submenu' => 'blood-test-records']);
    }

    public function render()
    {
        try {
            $result = $this->BloodTestRecordService->getBloodTestRecord($this->pet_id);

            if ($result['status'] === 'success') {
                // Get Blood Test record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            
            return view('livewire.backend.blood-test-record', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        if(request()->segment(1) == 'admin')
        {
            return view('backend.blood-test-record');
        }
        return view('frontend.blood-test-record');
    }

    
}
