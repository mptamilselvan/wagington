<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Request;
use App\Services\DietaryPreferencesService;
use App\Traits\DietaryPreferencesTrait;

class DietaryPreferences extends Component
{
    use DietaryPreferencesTrait;
    
    protected $DietaryPreferencesService;

    public $pet_id, $notes, $feed_time, $created_by,$updated_by,$is_active,$allergies, $customer_id;
    public $DietaryPreferences;
    public $editId = null, $deleteId = null,$firstSegment;

    public  $popUp = false, $title = 'Add pet profile';

    public function boot(DietaryPreferencesService $DietaryPreferencesService)
    {
        $this->DietaryPreferencesService = $DietaryPreferencesService;
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

        \session(['submenu' => 'dietary-preferences']);
    }

    public function render()
    {
        try {
            $result = $this->DietaryPreferencesService->getDietaryPreferences($this->pet_id);

            if ($result['status'] === 'success') {
                // Get Dietary Preferences record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.dietary-preferences', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        if(request()->segment(1) == 'admin')
        {
            return view('backend.dietary-preferences');
        }
        return view('frontend.dietary-preferences');
    }
}
