<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use App\Models\SystemSetting;
use App\Models\CompanySetting;
use App\Models\OperationalHour;
use App\Models\TaxSetting;
use App\Models\OffDay;
use App\Models\peakSeason;


class GeneralSettingController extends Controller
{
     /**
     * @OA\Get(
     * path="/api/general-settings",
     * tags={"General Settings"},
     * summary="Get General Settings information",
     * description="Get all General setting",
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *     response=200,
     *     description="General setting retrieved successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="General settngs", type="array", @OA\Items(type="object"))
     *     )
     * )
     * )
     */
    public function index()
    {
        try {
            $system_setting = SystemSetting::get();
            $company_setting = CompanySetting::first();
            $operational_hours = OperationalHour::orderby('id','asc')->get();
            $tax_setting = TaxSetting::where('tax_type','Goods & Service Tax')->first();
            $off_days = OffDay::orderby('id','asc')->get();
            $peak_season = peakSeason::orderby('id','asc')->get();

            $data = [
                'system_setting' => $system_setting,
                'company_setting' => $company_setting,
                'operational_hours' => $operational_hours,
                'tax_setting' => $tax_setting,
                'off_days' => $off_days,
                'peak_season' => $peak_season,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $data
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
