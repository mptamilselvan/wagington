<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Auth;
use Illuminate\Support\Facades\Request;
use App\Models\MedicalHistoryRecord as MedicalHistoryRecordModel;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use App\Services\MedicalHistoryRecordService;
use App\Traits\MedicalHistoryRecordTrait;

class MedicalHistoryRecord extends Component
{
    use WithFileUploads, MedicalHistoryRecordTrait;

    protected $MedicalHistoryRecordService;

    public $pet_id, $document, $notes, $name, $created_by,$updated_by,$src, $customer_id;
    public $MedicalHistoryRecord;
    public $editId = null, $deleteId = null,$firstSegment;

    public  $popUp = false, $title = 'Add pet profile';

    public function boot(MedicalHistoryRecordService $MedicalHistoryRecordService)
    {
        $this->MedicalHistoryRecordService = $MedicalHistoryRecordService;
    }

    public function mount()
    {
        $this->firstSegment = request()->segment(1);
        $this->pet_id = Request::route('id');
        $this->customer_id = Request::route('customer_id');
        if(\Session::get('edit') == "edit")
        {
            $this->title = 'Edit pet profile';
        }

        \session(['submenu' => 'medical-history-records']);
    }

    public function render()
    {
        try {
            $result = $this->MedicalHistoryRecordService->getMedicalHistoryRecord($this->pet_id);

            if ($result['status'] === 'success') {
                // Get Medical History Record record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.medical-history-record', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        if(request()->segment(1) == 'admin')
        {
            return view('backend.medical-history-record');
        }
        return view('frontend.medical-history-record');
    }

    
}
