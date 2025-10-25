<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Exception;
use App\Traits\SMSTrait;

class Campaign extends Component
{
    use SMSTrait;

    public  $form = false, $list = true, $view = false, $popUp = false, $title = 'Campaigns', $search = NULL, $sort,
        $mode = 'add', $openFilter = false, $filter = false, $campaignDetails = false, $distributionType, $consultantcampCount, $campaignCount, $editId,
        $SearchplaceHoder = 'Search',$filterStatus, $published=1;


    public function mount()
    {
        $this->title = 'Campaigns';
        $this->form = false;
        $this->list = true;
        $this->view = false;
        $this->campaignDetails = false;
        $this->editId = '';
    }
    public function render()
    {
        $this->showCampaignDetails(1);
        return view('livewire.backend.campaign', ['campaigns' => array()]);
    }

    public function index()
    {
        return view('backend.campaign');
    }

    public function showForm()
    {
        $this->title = 'Add Campaign';
        $this->form = true;
        $this->list = false;
        $this->view = false;
    }

    public function cancel()
    {
        $this->form = false;
        $this->list = true;
        $this->editId = '';
        $this->title = 'Campaigns';
    }

    public function showList()
    {
        $this->view = true;
        $this->campaignDetails = true;
        $this->form = true;
        $this->list = false;
        $this->editId = 1;
        $this->title = 'Add Campaign';
        return;
    }

    public function showFilter()
    {
        $this->openFilter = true;
    }

    public function resetFilter()
    {
        $this->filterStatus = '';
        $this->filter = false;
    }
    public function closeFilter()
    {
        $this->filterStatus = '';
        $this->openFilter = false;
        $this->filter = false;
    }

    public function applyFilter()
    {
        $this->openFilter = false;
        $this->filter = true;
    }

    public function cancelPopUp()
    {
        $this->popUp = false;
    }

        public function showCampaignDetails($campaignId)
    {
        try {
            $this->view = true;
            $this->campaignDetails = true;            
            $this->list = false;
            $this->editId = 1;
            $this->form = true;

        } catch (Exception $e) {
            $e->getMessage();
        }
    }
}
