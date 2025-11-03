<?php

namespace App\Livewire\Backend\Room;

use Livewire\Component;
use Exception;
use Auth;
use App\Models\Room\OffDayModel;

class OffDay extends Component
{

    public $title, $reason, $start_date, $end_date, $off_day_price_variation;
    public $offDay;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $page_title = 'Room Settings';

    protected $rules = [
        'title' => 'required|string',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'reason' => 'nullable|string',
        'off_day_price_variation' => 'required|numeric',
    ];

    public function mount()
    {
        \session(['submenu' => 'room-off-days']);
    }

    public function render()
    {
        try {
            $data = OffDayModel::orderby('id','asc')->paginate(10);
            return view('livewire.backend.room.off-day', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.room.off-day');
    }

    public function save()
    {
        
        $this->validate();

        try {
            // Check overlap with existing off days
            $start = $this->start_date;
            $end = $this->end_date;
            $overlaps = OffDayModel::where(function($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end])
                      ->orWhere(function($qq) use ($start, $end) {
                          $qq->where('start_date', '<=', $start)
                             ->where('end_date', '>=', $end);
                      });
                })
                ->when($this->editId, function($q) {
                    $q->where('id', '!=', $this->editId);
                })
                ->exists();

            if ($overlaps) {
                $this->addError('start_date', 'The selected date range overlaps an existing off day.');
                session()->flash('error', 'Date range overlaps with an existing off day.');
                return;
            }
            $data = $this->only(['title', 'start_date', 'end_date', 'reason', 'off_day_price_variation']);
            $data['created_by'] = Auth::user()->id;

           
            if ($this->editId) {
                OffDayModel::find($this->editId)->update($data);
            } else {
                OffDayModel::create($data);
            }

            $this->resetFields();
            session()->flash('success', 'Off day saved successfully.');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function edit($id)
    {
        try{
            $offDay = OffDayModel::findOrFail($id);
            $this->editId = $id;
            $this->title = $offDay->title;
            $this->start_date = $offDay->start_date->format('Y-m-d');
            $this->end_date = $offDay->end_date->format('Y-m-d');
            $this->reason = $offDay->reason;
            $this->off_day_price_variation = $offDay->off_day_price_variation;
        } catch (Exception $e) {
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
            OffDayModel::findOrFail($this->deleteId)->delete();
            $this->reset('deleteId', 'popUp');
            session()->flash('success', 'Off day deleted successfully.');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function resetFields()
    {
        $this->reset(['title', 'reason', 'start_date', 'end_date', 'editId', 'off_day_price_variation']);
    }
}