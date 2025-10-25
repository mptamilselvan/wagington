<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\BasePromotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\VoucherService;

class PromotionWalletController extends Controller
{
    protected $voucherService;

    public function __construct(Request $request, VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * @OA\Get(
     *     path="/api/promotion/wallet",
     *     tags={"Promo code wallet"},
     *     summary="Promo code wallet",
     *     description="Get all Promo code wallet for the authenticated customer",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", example="active", enum={"active", "expired"}),
     *         description="Filter promotions by status"
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Promo code wallet retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function wallet(Request $request)
    {
        $today = Carbon::today();
        $status = $request->input('status', 'active');

        $query = Voucher::with('promotion.referralPromotion', 'promotion.marketingCampaign')
            ->where('customer_id', Auth::id());

        if ($status === 'active') {
            $query->where('valid_till', '>=', $today);
        } elseif ($status === 'expired') {
            $query->where('valid_till', '<', $today);
        }

        $wallet = $query->orderBy('voucher_type','desc')->orderBy('id','desc')->paginate($request->input('per_page', 10));

        $transformed = $wallet->getCollection()->map(function ($voucher) {
            $promotion = $voucher->promotion;
            $referral = $promotion->referralPromotion ?? null;
            $marketing = $promotion->marketingCampaign ?? null;
        
            return [
                'id' => $voucher->id,
                'name' => $promotion->name ?? null,
                'voucher_code' => $voucher->voucher_code ?? $promotion->promo_code,
                'description' => $promotion->description ?? null,
                'terms_and_conditions' => $promotion->terms_and_conditions,
                'customer_id' => $voucher->customer_id,
                'discount_type' => $voucher->discount_type,
                'voucher_type' => $voucher->voucher_type,
                'discount_value' => $voucher->discount_type === 'percentage' 
                ? $voucher->discount_value . '%' 
                : $voucher->discount_value.' SGD',
                'coupon_validity' => $promotion->coupon_validity,
                'max_usage' => $voucher->max_usage,
                'usage_count' => $voucher->usage_count,
                'status' => $voucher->status,
                'valid_until' => $voucher->valid_till ? \Carbon\Carbon::parse($voucher->valid_till)->format('d F Y') : null,
                'referee_id' => $voucher->referee_id,
                'published' => $promotion->published,
                'stackable' => $promotion->stackable,
                'deleted_at' => $voucher->deleted_at,
                'created_at' => $voucher->created_at,
                'updated_at' => $voucher->updated_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $transformed,
            'current_page' => $wallet->currentPage(),
            'last_page' => $wallet->lastPage(),
            'per_page' => $wallet->perPage(),
            'total' => $wallet->total(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/promotion/coupon/{id}",
     *     tags={"Promo code wallet"},
     *     summary="Get promo code wallet by ID",
     *     description="Retrieve a single promo code wallet entry for the authenticated customer",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Promo code wallet ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Promo code wallet retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="voucher_code", type="string", example="ABC123"),
     *                 @OA\Property(property="discount_value", type="number", example=50),
     *                 @OA\Property(property="valid_till", type="string", format="date", example="2025-09-30"),
     *                 @OA\Property(property="promotion", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Promo code not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Promo code not found")
     *         )
     *     )
     * )
     */
    public function couponDetail($id)
    {
        $voucher = Voucher::with('promotion.referralPromotion', 'promotion.marketingCampaign')
            ->where('customer_id', Auth::id())
            ->find($id);

        $data = [
                'id' => $voucher->id,
                'name' => $voucher->promotion->name ?? null,
                'voucher_code' => $voucher->voucher_code ?? $voucher->promotion->promo_code,
                'description' => $voucher->promotion->description ?? null,
                'terms_and_conditions' => $voucher->promotion->terms_and_conditions,
                'customer_id' => $voucher->customer_id,
                'discount_type' => $voucher->discount_type,
                'voucher_type' => $voucher->voucher_type,
                'discount_value' => $voucher->discount_type === 'percentage' 
                ? $voucher->discount_value . '%' 
                : $voucher->discount_value.' SGD',
                'coupon_validity' => $voucher->promotion->coupon_validity,
                'max_usage' => $voucher->max_usage,
                'usage_count' => $voucher->usage_count,
                'status' => $voucher->status,
                'valid_until' => $voucher->valid_till ? \Carbon\Carbon::parse($voucher->valid_till)->format('d F Y') : null,
                'referee_id' => $voucher->referee_id,
                'published' => $voucher->promotion->published,
                'stackable' => $voucher->promotion->stackable,
                'deleted_at' => $voucher->deleted_at,
                'created_at' => $voucher->created_at,
                'updated_at' => $voucher->updated_at,
            ];

        if (!$voucher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Promo code not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/promotion/add-promo",
     *     tags={"Promo code wallet"},
     *     summary="Add promo code to wallet",
     *     description="Redeem a promo code and add it to the authenticated customer's wallet",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"promo_code"},
     *             @OA\Property(property="promo_code", type="string", example="ABC123", description="Promo code to redeem")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Promo code added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=31),
     *             @OA\Property(property="promo_code", type="string", example="OCT-22")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid promo code",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid promo code")
     *         )
     *     )
     * )
     */
    public function addPromoCode(Request $request)
    {
        $request->validate([
            'promo_code' => 'required|string|max:32'
        ]);

        $promotion = BasePromotion::with('marketingCampaign')
            ->where('promo_code', $request->promo_code)
            ->first();

        if (!$promotion) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid promo code.'
            ], 400);
        }

        try {
            $voucher = $this->voucherService->createVoucher($promotion, Auth::user(), Voucher::TYPE_MARKETING);

            return response()->json([
                'id' => $voucher->id,
                'promo_code' => $voucher->voucher_code
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/promotion/validate-voucher-code",
     *     tags={"Promo code wallet"},
     *     summary="Validate voucher code",
     *     description="Validate a voucher code for authenticated customer",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"voucher_code"},
     *             @OA\Property(property="voucher_code", type="string", example="ABC123", description="Voucher code to redeem")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Voucher code is valid and can be used.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Promo code is valid and can be used."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="voucher_id", type="integer", example=1),
     *                 @OA\Property(property="voucher_code", type="string", example="ABC123"),
     *                 @OA\Property(property="discount_type", type="string", example="percentage"),
     *                 @OA\Property(property="discount_value", type="number", example=50),
     *                 @OA\Property(property="valid_till", type="string", format="date", example="2025-09-30"),
     *                 @OA\Property(property="stackable", type="string", example="yes")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid Voucher code",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid promo code")
     *         )
     *     )
     * )
     */
    public function validateVoucherCode(Request $request)
    {
        try {
            $user_id = Auth::id();
            $voucherData = $this->voucherService->validateVoucher($request->all(), $user_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Promo code is valid and can be used.',
                'data' => $voucherData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

        /**
     * @OA\Post(
     *     path="/api/promotion/voucher/{id}/increment-usage",
     *     tags={"Promo code wallet"},
     *     summary="Increment voucher usage",
     *     description="Increment the usage count of a voucher code for the authenticated customer and return updated voucher details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Voucher ID to increment usage",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Voucher usage updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="promo_code", type="string", example="OCT-22"),
     *                 @OA\Property(property="usage_count", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Voucher not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Voucher not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Maximum usage reached",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Maximum usage reached")
     *         )
     *     )
     * )
     */
    public function incrementVoucherUsage($id)
    {
        try {
            $result = $this->voucherService->incrementVoucherUsage($id);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }


}
