<?php
namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\BasePromotion;
use App\Models\MarketingCampaign as MarketingCampaignModel;
use App\Models\User;
use App\Services\MarketingCampaignService;
use Auth;

class MarketingCampaign extends Component
{
    public $name, $description, $promo_code, $terms_and_conditions;
    public $valid_from, $valid_till, $coupon_validity = 60;
    public $discount_type, $discount_value, $usage_type, $customer_usage_limit = 1,$customers = [];
    public $editId,$valid_from_date,$valid_from_time,$valid_till_date,$valid_till_time,$stackable,$customer_type,$published = true,$selected_customer_ids = [],$new_customer_days,$deleteId,$basePromotionId;

    public $title = 'Marketing Campaigns',$popUp = false,$list = true,$form = false;

    public function boot(MarketingCampaignService $MarketingCampaignService)
    {
        $this->MarketingCampaignService = $MarketingCampaignService;
    }

    public function mount()
    {
        $this->form = false;
        $this->list = true;
    }

    public function render()
    {
        $result = $this->MarketingCampaignService->getMarketingCampaign();

        if ($result['status'] === 'success') {
            // Get Medication Supplement record
            $data = $result['data'];
        } else {
            // Handle validation errors
            session()->flash('error', $result['message']);
        }
        return view('livewire.backend.marketing-campaign',['data' => $result['data']]);
    }

    public function index()
    {
        
        return view('backend.marketing-campaign');
    }

    public function showForm()
    {
        $this->title = 'Add Marketing Campaign';
        $this->form = true;
        $this->list = false;
    }

    public function changeCustomerType()
    {
        $this->customer_type = $this->customer_type;
        if($this->customer_type == 'selected')
        {
            $this->customers = User::role('customer')->where('phone_verified_at','!=',null)->where('email_verified_at','!=',null)->get(['id', 'first_name', 'last_name', 'name', 'email'])->map(function ($user) {
                return [
                    'value' => $user->id,
                    'option' => $user->name, // calls getNameAttribute()
                ];
            })->toArray();
            $this->dispatch('set-customer',selected_customer_ids:[]);
        }
    }

    public function changeUsageType()
    {
        $this->usage_type = $this->usage_type;
    }

    public function changeValidFromDate()
    {
        $this->valid_till_date = '';
        $this->valid_from_date = $this->valid_from_date;
    }

    public function save()
    {
        $this->promo_code = $this->promo_code ? strtoupper($this->promo_code) : null;

        // try {
            $this->validate(\App\Rules\MarketingCampaignRules::rules($this->basePromotionId));
        // } catch (\Illuminate\Validation\ValidationException $e) {
        //     dd($e->errors());   // 👈 shows errors for each field
        // }
        
        try {
            $data = $this->only(['name','description','terms_and_conditions','valid_from_date','valid_from_time','valid_till_date','valid_till_time','promo_code','usage_type','customer_usage_limit','discount_type','discount_value','coupon_validity','stackable','customer_type','published','selected_customer_ids','new_customer_days'
            ]);
        
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }
        
            $result = $this->MarketingCampaignService->saveMarketingCampaign($this->editId,$data);
            if($this->published == true)
            {
                $result_mail = $this->MarketingCampaignService->sendMarketingCampaignMail($data,$this->customers);
            }

            if ($result['status'] === 'success') {
                // Store record ID for address creation
                session()->flash('success', $result['message']);
                
                $this->resetFields();
                $this->title = 'Marketing Campaigns';
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
            $data = BasePromotion::with('marketingCampaign')->where('promotion','marketingcampaign')->findOrFail($id);  
            $this->title = 'Add marketing Campaign';
            $this->form = true;
            $this->list = false;
            $this->editId = $data->marketingCampaign->id;
            $this->basePromotionId = $data->id;
            $this->name = $data->name;
            $this->description = $data->description;
            $this->terms_and_conditions = $data->terms_and_conditions;
            $this->valid_from_date = date('Y-m-d',strtotime($data->valid_from));
            $this->valid_from_time = date('h:i',strtotime($data->valid_from));
            $this->valid_till_date = date('Y-m-d',strtotime($data->valid_till));
            $this->valid_till_time = date('h:i',strtotime($data->valid_till));
            $this->coupon_validity = $data->coupon_validity;
            $this->stackable = $data->stackable == true?'yes':'no';
            $this->published = $data->published;
            $this->promo_code = $data->promo_code;
            $this->discount_type = $data->marketingCampaign->discount_type;
            $this->discount_value = $data->marketingCampaign->discount_value;
            $this->usage_type = $data->marketingCampaign->usage_type;
            $this->customer_usage_limit = $data->marketingCampaign->customer_usage_limit;
            $this->customer_type = $data->marketingCampaign->customer_type;
            $this->selected_customer_ids = json_decode($data->marketingCampaign->selected_customer_ids);
            $this->new_customer_days = $data->marketingCampaign->new_customer_days;

            if($this->customer_type == 'selected')
            {
                $this->customers = User::role('customer')->where('phone_verified_at','!=',null)->where('email_verified_at','!=',null)->get(['id', 'first_name', 'last_name', 'name', 'email'])->map(function ($user) {
                    return [
                        'value' => $user->id,
                        'option' => $user->name, // calls getNameAttribute()
                    ];
                })->toArray();
                $this->dispatch('set-customer', selected_customer_ids: $this->selected_customer_ids);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', $e->getMessage());
        }  catch (Exception $e) {
            dd($e->getMessage());
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
            $result = $this->MarketingCampaignService->deleteMarketingCampaign($this->deleteId);
            
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
            'promo_code',
            'usage_type',
            'customer_usage_limit',
            'discount_type',
            'discount_value',
            'coupon_validity',
            'stackable',
            'customers',
            'published',
            'new_customer_days',
            'customer_type',
            'selected_customer_ids'
        ]);
        $this->title = "Create Marketing Campaign";
    }

}
