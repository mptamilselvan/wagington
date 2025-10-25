<?php
namespace App\Services;

use App\Models\ReferralPromotion;
use App\Models\BasePromotion;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Jobs\SendReferralPromotionJob;

class ReferralPromotionService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_INFO = 'info';
    private const STATUS_ERROR = 'error';

   public function getReferralPromotion()
   {
        try {
            $data = BasePromotion::withCount('voucher')->with('ReferralPromotion')->where('promotion','referralpromotion')->orderby('id','asc')->get();
            
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

    public function saveReferralPromotion(int $editId = null, array $data): array
    {
        try{
            if ($data['discount_type'] === 'percentage' && $data['referrer_reward'] > 100)
            {
                return [
                    'status' => 'error',
                    'errors' => [
                        'referrer_reward' => ['Maximum percentage is 100'],
                    ],
                    'message' => 'Invalid percentage values.',
                ];
            }

            if ($data['discount_type'] === 'percentage' && $data['referee_reward'] > 100)
            {
                return [
                    'status' => 'error',
                    'errors' => [
                        'referee_reward'  => ['Maximum percentage is 100'],
                    ],
                    'message' => 'Invalid percentage values.',
                ];
            }        

            // Combine date + time inputs if they’re separate
            $validFrom = $data['valid_from_date'] . ' ' . $data['valid_from_time'];
            $validTill = $data['valid_till_date'] . ' ' . $data['valid_till_time'];

            // Save or update BasePromotion first
            $basePromotionData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                // 'promo_code' => $data['promo_code'] ? strtoupper($data['promo_code']) : null,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'valid_from' => $validFrom,
                'valid_till' => $validTill,
                'coupon_validity' => $data['coupon_validity'],
                'promotion' => 'referralpromotion',
                'published' => $data['published'] ?? false,
                'stackable' => $data['stackable'] ?? false,
            ];

            if ($editId) {
                $basePromotionData['updated_by'] = $data['updated_by'];
                $campaign = ReferralPromotion::findOrFail($editId);
                $campaign->basePromotion->update($basePromotionData);
                $campaign->update([
                    'discount_type' => $data['discount_type'],
                    'referrer_reward' => $data['referrer_reward'],
                    'referee_reward' => $data['referee_reward'],
                ]);
            } else {
                $basePromotionData['created_by'] = $data['created_by'];
                $basePromotionData['updated_by'] = $data['updated_by'];
                $basePromotion = BasePromotion::create($basePromotionData);

                $campaign = ReferralPromotion::create([
                    'base_promotion_id' => $basePromotion->id,
                    'discount_type' => $data['discount_type'],
                    'referrer_reward' => $data['referrer_reward'],
                    'referee_reward' => $data['referee_reward'],
                ]);
            }

            return [
                'status' => 'success',
                'message' => $editId ? 'Referral Promotion updated successfully' : 'Referral Promotion created successfully',
                'data' => $campaign->load('basePromotion'),
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Referral Promotion not found or does not belong to you'
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
                'message' => 'Failed to save Referral Promotion: ' . $e->getMessage()
            ];
        }
    }

    public function sendReferralPromotionMail($promotion)
    {
        try{
            $customers = User::role('customer')->select('id','name','email')->where('phone_verified_at','!=',null)->where('email_verified_at','!=',null)->get();

            foreach ($customers as $customer) {
                dispatch(new SendReferralPromotionJob($customer, $promotion));
                    // ->delay(now()->addSeconds(rand(5,60))); // stagger sending
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Mail send'
            ];

        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to save Referral Promotion: ' . $e->getMessage()
            ];
        }
    }

    public function deleteReferralPromotion($deleteId): array
    {
        try {
            $Promotion = BasePromotion::findOrFail($deleteId);

            $Promotion->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'ReferralPromotion deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }
}