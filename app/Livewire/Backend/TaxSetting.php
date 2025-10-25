<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Auth;
use App\Models\TaxSetting as TaxSettingModel;
use App\Models\Country;

class TaxSetting extends Component
{
    public $tax_type,$rate,$title = 'General Settings';

    protected $rules = [
        'rate'   => 'required|numeric'
    ];

    public function mount()
    {
        $tax_setting = TaxSettingModel::where('tax_type','Goods & Service Tax')->first();
        if($tax_setting)
        {
            $this->tax_type = $tax_setting->tax_type;
            $this->rate = $tax_setting->rate;
        }

        \session(['submenu' => 'tax-settings']);
    }

    public function render()
    {
        try {
            return view('livewire.backend.tax-setting');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.tax-setting');
    }

    public function save()
    {
        $this->validate();

        try {
            $data = $this->only(['rate']);
            $data['created_by'] = Auth::user()->id;


            TaxSettingModel::updateOrCreate(
                ['tax_type' => 'Goods & Service Tax'],$data
            );
            session()->flash('success', 'Tax setting record updated successfully.');

        } catch (Exception $e) {
            $e->getMessage();
        }
    }
}