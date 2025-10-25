<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use Auth;
use App\Models\PeakSeason as peakSeasonModel;

class PeakSeason extends Component
{

    public $title, $description, $start_date, $end_date, $price_variation;
    public $peakSeason;
    public $editId = null, $deleteId = null;

    public  $popUp = false, $page_title = 'General Settings';

    protected $rules = [
        'title' => 'required|string',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'price_variation' => 'required|numeric',
        'description' => 'nullable|string',
    ];

    public function mount()
    {
        \session(['submenu' => 'peak-seasons']);
    }

    public function render()
    {
        try {
            $data = peakSeasonModel::orderby('id','asc')->paginate(10);
            return view('livewire.backend.peak-season', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.peak-season');
    }

    public function save()
    {
        
        $this->validate();

        try {
            $data = $this->only(['title', 'start_date', 'end_date', 'description','price_variation']);
            $data['created_by'] = Auth::user()->id;


            if ($this->editId) {
                peakSeasonModel::find($this->editId)->update($data);
            } else {
                peakSeasonModel::create($data);
            }

            $this->resetFields();
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function edit($id)
    {
        try{
            $peakSeason = peakSeasonModel::findOrFail($id);
            $this->editId = $id;
            $this->title = $peakSeason->title;
            $this->start_date = $peakSeason->start_date->format('Y-m-d');
            $this->end_date = $peakSeason->end_date->format('Y-m-d');
            $this->price_variation = $peakSeason->price_variation;
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
            peakSeasonModel::findOrFail($this->deleteId)->delete();
            $this->reset('deleteId', 'popUp');
            session()->flash('success', 'Peak season deleted successfully.');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function resetFields()
    {
        $this->reset(['title', 'description', 'start_date', 'end_date', 'editId','price_variation']);
    }
}