<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use App\Models\ServiceCategory;
use App\Models\ServiceSubCategory;
use App\Models\PoolSetting;
use App\Models\AdvanceDuration;
use App\Models\CancellationRefund;
use App\Models\BookingSlot;
use App\Models\OffDay;
use App\Models\peakSeason;


class ServiceSettingsController extends Controller
{
     /**
     * @OA\Get(
     * path="/api/service-settings",
     * tags={"Service Settings"},
     * summary="Get Service Settings information",
     * description="Get all Service setting",
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *     response=200,
     *     description="Service setting retrieved successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="Service settngs", type="array", @OA\Items(type="object"))
     *     )
     * )
     * )
     */
    public function index()
    {
        try {
            $service_category = ServiceCategory::with('service_subcategory')->get();
            $pool_setting = PoolSetting::get();
            $advance_duration = AdvanceDuration::first();
            $cancellation_refund = CancellationRefund::get();
            $booking_slots = BookingSlot::select('day', 'start_time', 'end_time')
            ->orderByRaw("
                CASE day
                    WHEN 'Monday' THEN 1
                    WHEN 'Tuesday' THEN 2
                    WHEN 'Wednesday' THEN 3
                    WHEN 'Thursday' THEN 4
                    WHEN 'Friday' THEN 5
                    WHEN 'Saturday' THEN 6
                    WHEN 'Sunday' THEN 7
                END
            ")
            ->get()
            ->groupBy('day')
            ->map(function ($daySlots) {
                return $daySlots->map(function ($slot) {
                    return [
                        'start' => date('H:i', strtotime($slot->start_time)),
                        'end'   => date('H:i', strtotime($slot->end_time)),
                    ];
                });
            });

            // print_r($booking_slots);
            // die;
            $off_days = OffDay::orderby('id','asc')->get();
            $peak_season = peakSeason::orderby('id','asc')->get();

            $data = [
                'service_category' => $service_category,
                'pool_setting' => $pool_setting,
                'advance_duration' => $advance_duration,
                'cancellation_refund' => $cancellation_refund,
                'booking_slots' => $booking_slots,
                'off_days' => $off_days,
                'peak_season' => $peak_season,
            ];

            return response()->json([
                'status' => 'success',
                'Service settngs' => $data
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get records: ' . $e->getMessage()
            ], 400);
        }
    }
}
