<?php

namespace App\Livewire\Backend\Room;

use Livewire\Component;
use Exception;
use Auth;
use App\Models\Room\PeakSeasonModel;

class PeakSeason extends Component
{

    public $title, $description, $start_date, $end_date, $peak_price_variation, $weekend_price_variation;
    public $peakSeason;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $page_title = 'General Settings';

    protected $rules = [
        'title' => 'required|string',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'peak_price_variation' => 'required|numeric',
        'weekend_price_variation' => 'required|numeric',
        'description' => 'nullable|string',
    ];

    public function mount()
    {
        \session(['submenu' => 'peak-seasons']);
    }

    public function render()
    {
        try {
            $data = PeakSeasonModel::orderby('id','asc')->paginate(10);
        } catch (Exception $e) {
            \Log::error('PeakSeason render failed', [
                'message' => $e->getMessage(),
            ]);
            $data = collect([]);
        }
        return view('livewire.backend.room.peak-season', ['data' => $data]);
    }

    public function index()
    {
        return view('backend.room.peak-season');
    }

    public function save()
    {
        
        $this->validate();

        try {
            $data = $this->only(['title', 'start_date', 'end_date', 'description','peak_price_variation', 'weekend_price_variation']);
            $data['created_by'] = Auth::user()->id;


            if ($this->editId) {
                PeakSeasonModel::find($this->editId)->update($data);
            } else {
                PeakSeasonModel::create($data);
            }

            $this->resetFields();
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function edit($id)
    {
        try{
            $peakSeason = PeakSeasonModel::findOrFail($id);
            $this->editId = $id;
            $this->title = $peakSeason->title;
            $this->start_date = $peakSeason->start_date->format('Y-m-d');
            $this->end_date = $peakSeason->end_date->format('Y-m-d');
            $this->peak_price_variation = $peakSeason->peak_price_variation;
            $this->weekend_price_variation = $peakSeason->weekend_price_variation;
            $this->description = $peakSeason->description;
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
            PeakSeasonModel::findOrFail($this->deleteId)->delete();
            $this->reset('deleteId', 'popUp');
            session()->flash('success', 'Peak season deleted successfully.');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function resetFields()
    {
        $this->reset(['title', 'description', 'start_date', 'end_date', 'editId','peak_price_variation', 'weekend_price_variation']);
    }
}