<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Auth;
use App\Models\BookingSlot;

class BookingSlots extends Component
{
    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    public $slots = [];

    public $day,$start_time = array(),$end_time = array();
    public $operational_hours, $title = 'Services Settings';

    public function mount()
    {
        // Load existing slots from DB
        $existingSlots = BookingSlot::all()->groupBy('day');

        foreach ($this->days as $day) {
            if (isset($existingSlots[$day])) {
                $this->slots[$day] = $existingSlots[$day]->map(function($slot){
                    return [
                        'id' => $slot->id,
                        'start' => $slot->start_time,
                        'end' => $slot->end_time
                    ];
                })->toArray();
            } else {
                $this->slots[$day] = [['start'=>'','end'=>'']];
            }
        }
        \session(['submenu' => 'booking-slots']);
    }

    public function render()
    {
        try {
            return view('livewire.backend.booking-slots');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.booking-slots');
    }

    // In your Livewire component

    public function addSlot($day)
    {   
        // Validate the new slot first
        $this->validate([
            "slots.{$day}.new.start" => 'required|date_format:H:i',
            "slots.{$day}.new.end"   => 'required|date_format:H:i|after:slots.'.$day.'.new.start',
        ], [
            "slots.{$day}.new.start.required" => "Please enter a start time for $day.",
            "slots.{$day}.new.end.required"   => "Please enter an end time for $day.",
            "slots.{$day}.new.end.after"      => "End time for $day must be after the start time.",
        ]);

        // Add the new slot to the slots array
        $this->slots[$day][] = [
            'start' => $this->slots[$day]['new']['start'],
            'end'   => $this->slots[$day]['new']['end']
        ];

        // Reset the "new" input fields
        $this->slots[$day]['new'] = ['start' => '', 'end' => ''];
    }


    public function removeSlot($day, $index)
    {
        unset($this->slots[$day][$index]);
        $this->slots[$day] = array_values($this->slots[$day]);
    }


    public function save()
    {
        try{
            // dd($this);
            $userId = Auth::id();

            foreach ($this->days as $day) {
                // Delete all existing slots for that day
                BookingSlot::where('day', $day)->delete();

                // Save new slots
                foreach ($this->slots[$day] as $index => $slot) {

                    // Skip the 'new' key
                    if ($index === 'new') {
                        continue;
                    }

                    // Skip empty slots
                    if (empty($slot['start']) || empty($slot['end'])) {
                        continue;
                    }

                    $ans = [
                        'day' => $day,
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                        'created_by' => $userId,
                        'updated_by' => $userId
                    ];

                    // dd($ans);
                    
                    BookingSlot::create($ans);
                }
            }


            // foreach ($this->start_time as $day => $start) {

            //     if($this->start_time[$day] != null)
            //         $this->start_time[$day] = date('H:i', strtotime($start));
            //     if($this->end_time[$day] != null)
            //         $this->end_time[$day]   = date('H:i', strtotime($this->end_time[$day]));

            //     $this->validate([
            //             "start_time.$day" => 'required|date_format:H:i',
            //             "end_time.$day"   => 'required|date_format:H:i|after:start_time.' . $day,
            //         ],
            //         [
            //             "start_time.$day.required" => "Please enter a start time for $day.",
            //             "start_time.$day.date_format" => "Start time for $day must be in HH:MM format.",
            //             "end_time.$day.required" => "Please enter an end time for $day.",
            //             "end_time.$day.date_format" => "End time for $day must be in HH:MM format.",
            //             "end_time.$day.after" => "End time for $day must be after the start time.",
            //         ]
            //     );

                

            // }
            session()->flash('success', 'Booking slots saved successfully!');
        } catch (Exception $e) {
            session()->flash('error',$e->getMessage());
        }
    }
}
