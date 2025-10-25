<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Request;
use App\Services\MedicationSupplementService;
use App\Traits\MedicationSupplementTrait;
use Auth;

class MedicationSupplement extends Component
{
    use MedicationSupplementTrait;

    protected $MedicationSupplementService;

    public $pet_id, $type, $name,$dosage,  $created_by,$updated_by,$is_active,$notes,$administer_name,$date,$time,$administer_notes,$medication_supplement_id,$customer_id;
    public $MedicationSupplement;
    public $editId = null, $deleteId = null,$showAdministeredPopup = false,$records,$firstSegment;

    public  $popUp = false, $title = 'Add pet profile';

    public function boot(MedicationSupplementService $MedicationSupplementService)
    {
        $this->MedicationSupplementService = $MedicationSupplementService;
    }

    public function mount()
    {
        $this->firstSegment = request()->segment(1);
        $this->pet_id = Request::route('id');
        $this->customer_id = Request::route('customer_id');
        $this->administer_name = Auth::user()->name;
        $this->date = date('Y-m-d');
        $this->time = date('H:i');
        if(\Session::get('edit') == "edit")
        {
            $this->title = 'Edit pet profile';
        }

        \session(['submenu' => 'medication-supplements']);
    }

    public function render()
    {
        try {
            $result = $this->MedicationSupplementService->getMedicationSupplement($this->pet_id);

            if ($result['status'] === 'success') {
                // Get Medication Supplement record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.medication-supplement', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        if(request()->segment(1) == 'admin')
        {
            return view('backend.medication-supplement');
        }
        return view('frontend.medication-supplement');
    }
}
