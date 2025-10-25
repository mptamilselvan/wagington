<?php
namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\BasePromotion;
use App\Models\ReferralPromotion as ReferralPromotionModel;
use App\Models\User;
use App\Services\ReferralPromotionService;
use Auth;

class ReferralPromotion extends Component
{
    public $name, $description, $terms_and_conditions;
    public $valid_from, $valid_till, $coupon_validity = 60;
    public $discount_type, $referrer_reward, $referee_reward;
    public $editId,$valid_from_date,$valid_from_time,$valid_till_date,$valid_till_time,$stackable,$published = false,$deleteId,$basePromotionId;

    public $title = 'Referral Promotions',$popUp = false,$list = true,$form = false;

    public function boot(ReferralPromotionService $ReferralPromotionService)
    {
        $this->ReferralPromotionService = $ReferralPromotionService;
    }

    public function mount()
    {
        $this->form = false;
        $this->list = true;
    }

    public function render()
    {
        $result = $this->ReferralPromotionService->getReferralPromotion();

        if ($result['status'] === 'success') {
            // Get Medication Supplement record
            $data = $result['data'];
        } else {
            // Handle validation errors
            session()->flash('error', $result['message']);
        }
        return view('livewire.backend.referral-promotion',['data' => $result['data']]);
    }

    public function index()
    {
        
        return view('backend.referral-promotion');
    }

    public function showForm()
    {
        $this->title = 'Add Referral Promotions';
        $this->form = true;
        $this->list = false;
        $this->dispatch('load-ckeditor');
    }

    public function changeValidFromDate()
    {
        $this->valid_till_date = '';
        $this->valid_from_date = $this->valid_from_date;
    }

    public function save()
    {
        // $this->promo_code = $this->promo_code ? strtoupper($this->promo_code) : null;

        $this->validate(\App\Rules\ReferralPromotionRules::rules($this->basePromotionId,$this->only([
            'valid_from_date',
            'valid_from_time',
            'valid_till_date',
            'valid_till_time',
        ])));
        
        try {
            $data = $this->only(['name','description','terms_and_conditions','valid_from_date','valid_from_time','valid_till_date','valid_till_time','discount_type','coupon_validity','stackable','published','discount_type','referrer_reward','referee_reward'
            ]);
        
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }
        
            $result = $this->ReferralPromotionService->saveReferralPromotion($this->editId,$data);

            if($this->published == true)
            {
                $result_mail = $this->ReferralPromotionService->sendReferralPromotionMail($data);
            }

            if ($result['status'] === 'success') {
                // Store record ID for address creation
                session()->flash('success', $result['message']);
                
                $this->resetFields();
                $this->title = 'Referral Promotions';
                $this->form = false;
                $this->list = true;
            } else {
                // Handle validation errors
                if (isset($result['errors'])) {
                    foreach ($result['errors'] as $field => $messages) {
                        foreach ($messages as $message) {
                            $this->addError($field, $message);
                        }
                    }
                } else {
                    session()->flash('error', $result['message']);
                }
            }
            
        } catch (Exception $e) {
            dd($e);
        }
    }

    public function edit($id)
    {
        try{
            $data = BasePromotion::with('referralPromotion')->where('promotion','referralpromotion')->findOrFail($id);
            $this->title = 'Add Referral Promotions';
            $this->form = true;
            $this->list = false;
            $this->editId = $data->referralPromotion->id;
            $this->basePromotionId = $data->id;
            $this->name = $data->name;
            $this->description = $data->description;
            $this->terms_and_conditions = $data->terms_and_conditions;
            $this->valid_from_date = date('Y-m-d',strtotime($data->valid_from));
            $this->valid_from_time = date('h:i',strtotime($data->valid_from));
            $this->valid_till_date = date('Y-m-d',strtotime($data->valid_till));
            $this->valid_till_time = date('h:i',strtotime($data->valid_till));
            $this->discount_type = $data->discount_type;
            $this->coupon_validity = $data->coupon_validity;
            $this->stackable = $data->stackable == true?'yes':'no';
            $this->published = $data->published;
            $this->discount_type = $data->referralPromotion->discount_type;
            $this->referrer_reward = $data->referralPromotion->referrer_reward;
            $this->referee_reward = $data->referralPromotion->referee_reward;

            $this->dispatch('load-ckeditor');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', $e->getMessage());
        }  catch (Exception $e) {
            session()->flash('error', $e->getMessage());
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
            $result = $this->ReferralPromotionService->deleteReferralPromotion($this->deleteId);
            
            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
            $this->reset('deleteId', 'popUp');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function resetFields()
    {
        $this->reset([
            'editId',
            'name',
            'description',
            'terms_and_conditions',
            'valid_from_date',
            'valid_from_time',
            'valid_till_date',
            'valid_till_time',
            'discount_type',
            'coupon_validity',
            'stackable',
            'published',
            'referrer_reward', 
            'referee_reward'
        ]);
        $this->title = "Add Referral Promotions";
    }

}
