<?php
namespace App\Services;

use App\Models\MarketingCampaign;
use App\Models\BasePromotion;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Jobs\SendPromotionMailJob;

class MarketingCampaignService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_INFO = 'info';
    private const STATUS_ERROR = 'error';

   public function getMarketingCampaign()
   {
        try {
            $data = BasePromotion::with('marketingCampaign')->where('promotion','marketingcampaign')->orderby('id','asc')->get();
            return [
                'status' => self::STATUS_SUCCESS,
                'data' => $data
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to get records'
            ];
        }
    }

    public function saveMarketingCampaign(int $editId = null, array $data): array
    {
        try{
            if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100)
            {
                return [
                    'status' => 'error',
                    'errors' => [
                        'discount_value' => ['Maximum percentage is 100'],
                    ],
                    'message' => 'Invalid percentage values.',
                ];
            }
            // $validator = $this->validateMarketingCampaignData($data,$editId);

            // Combine date + time inputs if they’re separate
            $validFrom = $data['valid_from_date'] . ' ' . $data['valid_from_time'];
            $validTill = $data['valid_till_date'] . ' ' . $data['valid_till_time'];

            // Save or update BasePromotion first
            $basePromotionData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'promo_code' => $data['promo_code'] ? strtoupper($data['promo_code']) : null,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'valid_from' => $validFrom,
                'valid_till' => $validTill,
                'coupon_validity' => $data['coupon_validity'],
                'promotion' => 'marketingcampaign',
                'published' => $data['published'] ?? false,
                'stackable' => $data['stackable'] ?? false,
            ];

            if($data['usage_type'] == 'single_use'){
                $data['customer_usage_limit'] = 1;
            } elseif($data['usage_type'] == 'multiple_use'){
                $data['customer_usage_limit'] = $data['customer_usage_limit'];
            } elseif($data['usage_type'] == 'unlimited'){
                $data['customer_usage_limit'] = 0;
            }

            if ($editId) {
                $basePromotionData['updated_by'] = $data['updated_by'];

                $campaign = MarketingCampaign::findOrFail($editId);
                $campaign->basePromotion->update($basePromotionData);
                $campaign->update([
                    'discount_type' => $data['discount_type'],
                    'discount_value' => $data['discount_type'] === 'percentage'
                        ? min($data['discount_value'], 100)
                        : $data['discount_value'],
                    'usage_type' => $data['usage_type'],
                    'customer_usage_limit' => $data['customer_usage_limit'],
                    'customer_type' => $data['customer_type'],
                    'selected_customer_ids' => $data['selected_customer_ids'],
                    'new_customer_days' => $data['new_customer_days'],
                ]);
            } else {
                $basePromotionData['created_by'] = $data['created_by'];
                $basePromotionData['updated_by'] = $data['updated_by'];
                $basePromotion = BasePromotion::create($basePromotionData);

                if($data['customer_type'] == "selected")
                {
                    $data['selected_customer_ids'] = json_encode($data['selected_customer_ids']);
                }else{
                    $data['selected_customer_ids'] = null;
                }

                $campaign = MarketingCampaign::create([
                    'base_promotion_id' => $basePromotion->id,
                    'discount_type' => $data['discount_type'],
                    'discount_value' => $data['discount_type'] === 'percentage'
                        ? min($data['discount_value'], 100)
                        : $data['discount_value'],
                    'usage_type' => $data['usage_type'],
                    'customer_usage_limit' => $data['customer_usage_limit'],
                    'customer_type' => $data['customer_type'],
                    'selected_customer_ids' => $data['selected_customer_ids'],
                    'new_customer_days' => $data['new_customer_days'],
                ]);

                // dd($campaign);
            }


            return [
                'status' => 'success',
                'message' => $editId ? 'Campaign updated successfully' : 'Campaign created successfully',
                'data' => $campaign->load('basePromotion'),
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Marketing Campaign not found or does not belong to you'
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ];
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to save Marketing Campaign: ' . $e->getMessage()
            ];
        }
    }

    public function sendMarketingCampaignMail($promotion,$customers)
    {
        try{
            $users = User::role('customer')->select('id','name','email')->where('phone_verified_at','!=',null)->where('email_verified_at','!=',null);

            if ($promotion['customer_type']=== 'all') {
                $customers = $users->get();
            } elseif ($promotion['customer_type']=== 'new') {
                $customers = $users->whereDate('created_at', '>=', now()->subDays($promotion['new_customer_days']))->get();
            } elseif ($promotion['customer_type']=== 'selected') {
                $customers = $users->whereIn('id', $promotion['selected_customer_ids'])->get();
            }

            // dd($customers);

            foreach ($customers as $customer) {
                dispatch(new SendPromotionMailJob($customer, $promotion));
                    // ->delay(now()->addSeconds(rand(5,60))); // stagger sending
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Mail send'
            ];

        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to save Marketing Campaign: ' . $e->getMessage()
            ];
        }
    }


    public function deleteMarketingCampaign($deleteId): array
    {
        try {
            $Promotion = BasePromotion::findOrFail($deleteId);

            $Promotion->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Marketing Campaign deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }
}