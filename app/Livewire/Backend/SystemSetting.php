<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\SystemSetting as SystemSettingModel;

class SystemSetting extends Component
{
    public $system_setting, $title='General Settings';

    public function mount()
    {
        $this->system_setting = SystemSettingModel::get();

        \session(['submenu' => 'system-settings']);
    }

    public function render()
    {
        return view('livewire.backend.system-setting');
    }

    public function index()
    {
        return view('backend.system-setting');
    }
}
