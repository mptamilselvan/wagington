<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
// use App\Models\Booking;
use App\Models\Voucher;
use App\Models\BasePromotion;
use App\Helpers\SystemSettingHelper;
// use App\Http\Resources\ReferralSignupResource;

class ReferralSignupController extends Controller
{
    /**
 * @OA\Get(
 *     path="/api/referrals/signups",
 *     tags={"Profile - Referral"},
 *     summary="Referral Signups",
 *     description="Get list of users referred by the authenticated customer",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=12),
 *                     @OA\Property(property="name", type="string", example="John Doe"),
 *                     @OA\Property(property="phone", type="string", example="+123456789"),
 *                     @OA\Property(property="created", type="string", format="date-time", example="2025-09-10T12:30:00Z"),
 *                     @OA\Property(property="first_booking_on", type="string", format="date-time", example="2025-09-12T15:00:00Z"),
 *                     @OA\Property(property="made_first_booking", type="boolean", example=true),
 *                     @OA\Property(property="voucher_expiry_on", type="string", format="date-time", example="2025-10-12T23:59:59Z")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Authentication Required")
 * )
 */

    public function index(Request $request)
    {
        $customer = Auth::user();

        // Get referrals
        $referrals = User::where('referred_by_id', $customer->id)->orderBy('created_at','desc')
            ->get()
            ->map(function ($ref) {
                $voucher = Voucher::where('customer_id', $ref->id)
                    ->where('voucher_type', 'referee_reward')
                    ->where('status', 'available')
                    ->first();

                return [
                    'id'                => $ref->id,
                    'name'              => '******'.substr($ref->name,-4),
                    'phone'             => '******'.substr($ref->phone,-4),
                    'signed_up_on'           => $ref->created_at->format('jS M Y'),
                    // 'first_booking_on'  => '',
                    // 'made_first_booking'=> '',
                    // 'voucher_expiry_on' => $voucher?->valid_till,
                    'day_left' => $voucher?->getDaysLeft(),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $referrals
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/referrals/status",
     *     tags={"Profile - Referral"},
     *     summary="Referral Status",
     *     description="Get referral stats for the authenticated customer",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="referral_code", type="string", example="ABC123"),
     *             @OA\Property(property="signed_up", type="integer", example=5),
     *             @OA\Property(property="made_first_booking", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Authentication Required")
     * )
     */
    public function referralStatus(Request $request)
    {
        $customer = Auth::user();

        $promotion = BasePromotion::with('referralPromotion')
            ->where('promotion', BasePromotion::TYPE_REFERRAL)
                ->where('valid_from','<=', now())
                ->where('valid_till' ,'>=', now())
                ->where('published',true)
                ->first();

        $signedUp = User::where('referred_by_id', $customer->id)->count();

        // Count referrals without booking
        // $signedUp = $referrals->filter(function ($ref) {
        //     return !Booking::where('customer_id', $ref->id)
        //         ->where('status', Booking::STATUS_FINISHED)
        //         ->exists();
        // })->count();

        // Count referrals with at least one finished booking
        // $madeFirstBooking = $referrals->filter(function ($ref) {
        //     return Booking::where('customer_id', $ref->id)
        //         ->where('status', Booking::STATUS_FINISHED)
        //         ->exists();
        // })->count();

        return response()->json([
            "referral_discount" => SystemSettingHelper::getCurrency().' $'. $promotion->referralPromotion->referrer_reward,
            "referee_discount" => SystemSettingHelper::getCurrency().' $'.$promotion->referralPromotion->referee_reward,
            'referral_code' => $customer->referal_code,
            'signed_up' => $signedUp,
            // 'made_first_booking' => null,
        ]);
    }

}
