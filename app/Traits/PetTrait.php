<?php

namespace App\Traits;

use App\Models\Species;
use App\Models\Breed;
use App\Models\Pet;
use App\Models\User;
use Carbon\Carbon;
use Auth;

trait PetTrait
{
    public $species, $breeds = [],$filter_breed_option = [];    

    public function changeSpecies()
    {
        $this->breeds = Breed::select('id as value', 'name as option')
            ->where('species_id', $this->species_id)
            ->get()
            ->toArray();
    }

    public function changeFilterSpecies()
    {
        $this->filter_breed_option = Breed::select('id as value', 'name as option')
            ->where('species_id', $this->filterSpecies)
            ->get()
            ->toArray();
    }

    public function getAge()
    {
        $dob = Carbon::parse($this->date_of_birth);
        $now = Carbon::now();

        // Calculate years and remaining months
        $years = $dob->diffInYears($now);
        $months = $dob->copy()->addYears($years)->diffInMonths($now);

        $yearLabel = (int) $years === 1 ? ' year' : ' years';
        $monthLabel = (int) $months === 1 ? ' month' : ' months';

        $this->age_months = (int) $months.$monthLabel;
        $this->age_year = (int) $years.$yearLabel;
    }

    public function resetFields()
    {
        $this->reset([
            'name', 'gender', 'species_id', 'breed_id', 'color',
            'date_of_birth', 'age_months','age_year', 'sterilisation_status', 'profile_image','editId'
        ]);
    }

    function submitForm() {

        $data = $this->only(['user_id','name','gender','species_id','breed_id','color','date_of_birth','sterilisation_status']);

        $pet_data = [];

        if(Auth::user()->hasRole('customer'))
        {
            $pet_data = $this->only(['microchip_number', 'length_cm', 'height_cm', 'weight_kg', 'avs_license_number', 'date_expiry']);
            $pet_data['document'] = $this->document;
        }

        if ($this->editId) {
            $data['updated_by'] = Auth::id();
        }
        else
        {
            $data['updated_by'] = Auth::id();
            $data['created_by'] = Auth::id();
            session()->forget('edit');
        }

        $data['profile_image'] = $this->profile_image;
        
        $result = $this->petService->savePet($this->editId,$data,$pet_data);
        
        return $result;

    }
    

    function edit($editId) {
        try {
            $pet = Pet::findOrFail($editId);
            session(['edit' => 'edit']);

            $this->species = Species::select('id as value', 'name as option')->get()->toArray();
            if(Auth::user()->hasRole('admin'))
            {
                $this->customers = User::role('customer')->where('phone_verified_at','!=',null)->where('email_verified_at','!=',null)->get(['id', 'first_name', 'last_name', 'name', 'email'])->map(function ($user) {
                    return [
                        'value' => $user->id,
                        'option' => $user->name, // calls getNameAttribute()
                    ];
                })->toArray();
            }
            // dd($pet);
            $this->editId = $editId;
            $this->pet_id = $editId;
            $this->title = 'Basic Information';
            $this->form = true;
            $this->list = false;
            $this->view = false;

            $this->user_id = $pet->user_id;
            $this->customer_id = $pet->user_id;
            $this->name = $pet->name;
            $this->age_months = $pet->age_months;
            $this->age_year = $pet->age_year;
            // $this->profile_image = $pet->profile_image;
            $this->src = $pet->profile_image;
            $this->gender = $pet->gender;
            // dd($this->gender);
            $this->species_id = $pet->species_id;
            $this->breeds = [];
            $this->breeds = Breed::select('id as value', 'name as option')->where('species_id','=',$this->species_id)->get()->toArray();
            $this->breed_id = $pet->breed_id;
            $this->color = $pet->color;
            $this->date_of_birth = $pet->date_of_birth->format('Y-m-d');
            $this->sterilisation_status = $pet->sterilisation_status == false?'false':'true';

            if(Auth::user()->hasRole('customer'))
            {
                $this->microchip_number = $pet->microchip_number;
                $this->length_cm = $pet->length_cm;
                $this->height_cm = $pet->height_cm;
                $this->weight_kg = $pet->weight_kg;
                $this->avs_license_number = $pet->avs_license_number;
                $this->avs_license_expiry = $pet->avs_license_expiry;
                $this->date_expiry = $pet->date_expiry?$pet->date_expiry->format('Y-m-d'):'';
                $this->src_document = $pet->document;
            }

        }
        catch (Exception $e) {
            $e->getMessage();
        }

        
    }

    public function deletePopUp($id)
    {
        $this->deleteId = $id;
        $this->popUp = true;
    }

    public function delete()
    {
        try{
            // dd($this->deleteId);
            $result = $this->petService->deletePet($this->deleteId);
            
            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
            $this->reset('deleteId', 'popUp');
            return redirect()->route($this->firstSegment.'.pets');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }
}
