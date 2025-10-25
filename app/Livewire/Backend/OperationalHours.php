<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Auth;
use App\Models\OperationalHour;
use App\Helpers\FormatHelper;
use Illuminate\Support\Carbon;


class OperationalHours extends Component
{
    public $day,$start_time = array(),$end_time = array(),$days = array();
    public $operational_hours, $title = 'General Settings',$distributionType;

    public function mount()
    {
        $this->operational_hours = OperationalHour::orderby('id','asc')->get();
        foreach($this->operational_hours as $hour)
        {
            $this->start_time[$hour->day] = $hour->start_time;
            $this->end_time[$hour->day] = $hour->end_time;
        }
        \session(['submenu' => 'operational-hours']);
    }

    public function render()
    {
        try {
            return view('livewire.backend.operational-hours');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.operational-hours');
    }

    public function save()
    {
        try{
            foreach ($this->start_time as $day => $start) {

                if($this->start_time[$day] != null)
                    $this->start_time[$day] = date('H:i', strtotime($start));
                if($this->end_time[$day] != null)
                    $this->end_time[$day]   = date('H:i', strtotime($this->end_time[$day]));

                $this->validate([
                        "start_time.$day" => 'required|date_format:H:i',
                        "end_time.$day"   => 'required|date_format:H:i|after:start_time.' . $day,
                    ],
                    [
                        "start_time.$day.required" => "Please enter a start time for $day.",
                        "start_time.$day.date_format" => "Start time for $day must be in HH:MM format.",
                        "end_time.$day.required" => "Please enter an end time for $day.",
                        "end_time.$day.date_format" => "End time for $day must be in HH:MM format.",
                        "end_time.$day.after" => "End time for $day must be after the start time.",
                    ]
                );

                OperationalHour::updateOrCreate(
                    ['day' => $day], // Unique condition
                    [
                        'start_time' => $this->start_time[$day],
                        'end_time' => $this->end_time[$day],
                        'created_by' => Auth::id(),
                    ]
                );

            }
            session()->flash('success', 'Record updated successfully.');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }
}