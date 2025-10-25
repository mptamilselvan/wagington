<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Auth;
use App\Models\TemperamentHealthEvaluation as TemperamentHealthEvaluationModel;
use App\Models\Pet;
use App\Models\Species;
use Illuminate\Support\Facades\Request;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class TemperamentHealthEvaluation extends Component
{
    use WithFileUploads;

    public $microchip_number, $length_cm, $height_cm, $weight_kg, $avs_license_number, $avs_license_expiry, $date_expiry, $document, $administer_name, $date, $notes, $behaviour, $status,$customer_id;
    public $temperament_health_evaluation;
    public $editId = null, $deleteId = null,$pet_id,$src_document,$firstSegment,$dob,$species_id;

    public  $popUp = false, $title = 'Add pet profile';

    protected $rules = [
        'administer_name' => 'required|string|max:100',
        'date' => 'required|date|before_or_equal:today',
        'notes' => 'nullable|string|max:200',
        'behaviour' => 'nullable|string|max:200',
        'status' => 'required|in:pass,fail',
    ];

    public function mount()
    {
        $this->firstSegment = request()->segment(1);
        $this->pet_id = Request::route('id');
        $this->customer_id = Request::route('customer_id');
        $pet = Pet::find($this->pet_id);
        $this->species_id = $pet->species_id;

        $this->microchip_number = $pet->microchip_number;
        $this->length_cm = $pet->length_cm;
        $this->height_cm = $pet->height_cm;
        $this->weight_kg = $pet->weight_kg;
        $this->avs_license_number = $pet->avs_license_number;
        $this->avs_license_expiry = $pet->avs_license_expiry;
        $this->date_expiry = $pet->date_expiry?$pet->date_expiry->format('Y-m-d'):null;
        $this->src_document = $pet->document;
        $this->dob = $pet->date_of_birth;
        $this->administer_name = Auth::user()->name;
        $this->date = date('Y-m-d');
        if(\Session::get('edit') == "edit")
        {
            $this->title = 'Edit pet profile';
        }

        \session(['submenu' => 'temperament-health-evaluations']);
    }

    public function render()
    {
        try {
            // dd($this->pet_id);
            
            $data = TemperamentHealthEvaluationModel::where('pet_id','=',$this->pet_id)->orderby('id','asc')->paginate(10);
            return view('livewire.backend.temperament-health-evaluation', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.temperament-health-evaluation');
    }

    protected function messages()
    {
        return \App\Rules\PetRules::messages();
    }

    public function save()
    {
        $this->validate(\App\Rules\PetRules::rules('',$flag = true));
        $this->validate();

        try {
            $pet_data = $this->only(['microchip_number', 'length_cm', 'height_cm', 'weight_kg', 'avs_license_number', 'date_expiry']);
            
            $evaluation_data = $this->only(['administer_name', 'date', 'notes', 'behaviour', 'status','pet_id']);
            if ($this->editId) {
                $evaluation_data['updated_by'] = Auth::user()->id;
            }
            else
            {
                $evaluation_data['created_by'] = Auth::user()->id;
                $evaluation_data['updated_by'] = Auth::user()->id;
            }
            

            $pet = Pet::find($this->pet_id);

            if (!empty($this->document)) {
                // Delete old image if it exists
                if (!empty($pet->document) && Storage::disk('do_spaces')->exists($pet->document)) {
                    Storage::disk('do_spaces')->delete($pet->document);
                }
             
                // Upload new one
                $path = $this->document->store('pets', 'do_spaces');
                $pet_data['document'] = $path;
            } else {
                // Don't overwrite existing image_url
                unset($this->document);
            }

            $pet->update($pet_data);
            TemperamentHealthEvaluationModel::create($evaluation_data);
            
            $this->resetFields();
            session()->flash('success', "Record updated");
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function resetFields()
    {
        $this->reset(['notes', 'behaviour', 'status','editId']);
        $this->editId = null;
        $this->popUp = false;
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
}