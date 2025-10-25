<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\Voucher as VoucherModels;

class Voucher extends Component
{
    public $title = 'Voucher';

    public function render()
    {
        $data = VoucherModels::with('promotion','customer')->orderBy('voucher_type','desc')->orderBy('id','desc')->paginate(15);
        return view('livewire.backend.voucher',['data' => $data]);
    }

    public function index()
    {
        return view('backend.voucher');
        
    }
}
