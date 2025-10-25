<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Request;
use Livewire\WithFileUploads;
use App\Services\DewormingRecordService;
use App\Traits\DewormingRecordTrait;

class DewormingRecord extends Component
{
    use WithFileUploads, DewormingRecordTrait;

    protected $DewormingRecordService;

    public $pet_id,  $date, $document, $notes, $brand_name, $created_by,$updated_by,$src,$customer_id;
    public $DewormingRecord;
    public $editId = null, $deleteId = null,$firstSegment;

    public  $popUp = false, $title = 'Add pet profile';

    public function boot(DewormingRecordService $DewormingRecordService)
    {
        $this->DewormingRecordService = $DewormingRecordService;
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

        \session(['submenu' => 'deworming-records']);
    }

    public function render()
    {
        try {
            $result = $this->DewormingRecordService->getDewormingRecord($this->pet_id);

            if ($result['status'] === 'success') {
                // Get Deworming Record record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.deworming-record', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        if(request()->segment(1) == 'admin')
        {
            return view('backend.deworming-record');
        }
        return view('frontend.deworming-record');
    }

    
}
