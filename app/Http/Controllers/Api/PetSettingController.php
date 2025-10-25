<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Species;
use App\Models\Size;
use Auth;
use Carbon\Carbon;


/**
 * @OA\Tag(
 *     name="Pets",
 *     description="Pets-specific APIs"
 * )
 */
class PetSettingController extends Controller
{
     /**
     * @OA\Get(
     * path="/api/pet-settings",
     * tags={"Pet Settings"},
     * summary="Get Pet Settings information",
     * description="Get all pet setting",
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *     response=200,
     *     description="Pet setting retrieved successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="pet settngs", type="array", @OA\Items(type="object"))
     *     )
     * )
     * )
     */
    public function index()
    {
        try {
            $data = Species::with('breed','vaccination','blood_test','pet_tag','vaccine_exemption','revaluation_workflow')->get();

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

    /**
     * @OA\Get(
     * path="/api/sizes",
     * tags={"Pet Settings"},
     * summary="Get Size information",
     * description="Get all Size",
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *     response=200,
     *     description="Size retrieved successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="size", type="array", @OA\Items(type="object"))
     *     )
     * )
     * )
     */
    public function size()
    {
        try {
            $data = Size::get();

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