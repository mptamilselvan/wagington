<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Auth;
use App\Models\CancellationRefund as CancellationRefundModel;

class CancellationRefund extends Component
{
    public $type = [],$value = [],$title = 'Services Settings';
    public $cr;

    protected $rules = [
        'value.*'   => 'required|numeric|max:100'
    ];

    public function mount()
    {
        $this->cr = CancellationRefundModel::orderBy('id','asc')->get();
        foreach($this->cr as $cr)
        {
            $this->value[$cr->type] = $cr->value;
        }

        // dd($this->value);
        \session(['submenu' => 'cancellation-refund']);
    }

    public function render()
    {
        try {
         
            return view('livewire.backend.cancellation-refund');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.cancellation-refund');
    }

    public function save()
    {
        $this->validate();

        try {
            $data = $this->only(['value']);
            $data['created_by'] = Auth::user()->id;

            // dd($this);

            foreach($this->value as $key => $value)
            {
                CancellationRefundModel::updateOrCreate(
                    ['type' => $key],
                    ['value' =>$this->value[$key]]
                );
            }
            session()->flash('success', 'Cancellation & Refund Settings record updated successfully.');

        } catch (Exception $e) {
            $e->getMessage();
        }
    }
}
