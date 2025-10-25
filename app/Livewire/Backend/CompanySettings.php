<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Auth;
use App\Models\CompanySetting;
use App\Models\Country;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Url;

class CompanySettings extends Component
{
    // #[Reactive]
    public $country_code;
    public $company_name,$uen_no,$country_id,$postal_code,$address_line1,$address_line2,$contact_number,$support_email;
    public $countries, $title = 'General Settings';

    protected $rules = [
        'company_name'   => 'required|string|max:150',
        'uen_no'         => 'required|string|max:50',
        'country_id'     => 'required|exists:countries,id',
        'postal_code'    => 'required|string|max:20',
        'address_line1'  => 'required|string|max:255',
        'address_line2'  => 'nullable|string|max:255',
        'contact_number' => 'required|string|max:20',
        'country_code' => 'required',
        'support_email'  => 'required|email|max:255'
    ];

    public function mount()
    {
        // dd("zd");
        $this->countries = Country::select('id as value', 'name as option')->orderby('id','asc')->get();
        $company_setting = CompanySetting::first();
        if($company_setting)
        {
            $this->company_name = $company_setting->company_name;
            $this->uen_no = $company_setting->uen_no;
            $this->country_id = $company_setting->country_id;
            $this->postal_code = $company_setting->postal_code;
            $this->address_line1 = $company_setting->address_line1;
            $this->address_line2 = $company_setting->address_line2;
            $this->contact_number = $company_setting->contact_number;
            $this->country_code = $company_setting->country_code;
            $this->support_email = $company_setting->support_email;
        }
        \session(['submenu' => 'company-settings']);
    }

    public function render()
    {
        try {
            $this->countries = Country::select('id as value', 'name as option')->orderby('id','asc')->get();
            return view('livewire.backend.company-settings');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.company-settings');
    }

    public function save()
    {
        
        $this->validate();
        try {
            // $this->contact_number = $this->contact_number_dial_code.$this->contact_number;
            $data = $this->only(['company_name','uen_no','country_id','postal_code','address_line1','address_line2','contact_number','support_email','country_code']);
            $data['created_by'] = Auth::user()->id;
            
            CompanySetting::updateOrCreate(
                ['id' => 1],$data
            );
            // dd($data);
            // session()->flash('success', $result['message']);
            session()->flash('success', 'Company setting record updated successfully.');
            // return redirect()->to('admin/company-settings');
            
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function changePostalCode()
    {
        // dd($this->postal_code);
        $this->resetErrorBag(['postal_code']);

        $oneMapService = app(\App\Services\OneMapService::class);
        $result = $oneMapService->searchByPostalCode($this->postal_code);
            
        if ($result['success'] && $result['data']) {
            $formattedData = $oneMapService->formatAddressData($result['data']);
            
            // Update address fields directly (more reliable than fill() for this case)
            if (isset($formattedData['address_line_1'])) {
                $this->address_line1 = $formattedData['address_line_1'];
            }
            if (isset($formattedData['address_line_2'])) {
                $this->address_line2 = $formattedData['address_line_2'];
            }
            
            // Clear validation errors
            $this->resetErrorBag(['postal_code']);
            $this->resetErrorBag(['address_line1', 'address_line2']);
        }
        else{
            // dd( $result['error']);
            $this->resetErrorBag(['postal_code']);
            $this->addError('postal_code', $result['error']);
            // session()->flash('address_error', $result['error'] ?? 'Address not found for this postal code');
        }
    }
}