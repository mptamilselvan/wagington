<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OneMapService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    private $oneMapService;

    public function __construct(OneMapService $oneMapService)
    {
        $this->oneMapService = $oneMapService;
    }

    // /**
    //  * @OA\Post(
    //  *     path="/api/address/search",
    //  *     tags={"Address"},
    //  *     summary="Search address by postal code",
    //  *     description="Lookup address details using a postal code (currently SG only)",
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="postal_code", type="string", example="123456"),
    //  *             @OA\Property(property="country", type="string", example="SG")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Address found",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="message", type="string", example="Address found successfully"),
    //  *             @OA\Property(property="data", type="object")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=404,
    //  *         description="Address not found",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="not_found"),
    //  *             @OA\Property(property="message", type="string", example="Address not found"),
    //  *             @OA\Property(property="data", type="null")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=422,
    //  *         description="Validation error",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="invalid"),
    //  *             @OA\Property(property="message", type="string", example="Invalid input: ..."),
    //  *             @OA\Property(property="data", type="null")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Server error",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="message", type="string", example="An error occurred while searching for the address."),
    //  *             @OA\Property(property="data", type="null")
    //  *         )
    //  *     )
    //  * )
    //  */
    public function searchByPostalCode(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'postal_code' => 'required|string|size:6|regex:/^\d{6}$/',
                'country' => 'required|string|in:SG'
            ]);

            $postalCode = $request->input('postal_code');
            $country = $request->input('country');

            // Currently only supporting Singapore
            if ($country !== 'SG') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Address lookup is currently only supported for Singapore.',
                    'data' => null
                ], 400);
            }

            $result = $this->oneMapService->searchByPostalCode($postalCode);

            if ($result['success']) {
                $formattedData = $this->oneMapService->formatAddressData($result['data']);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Address found successfully',
                    'data' => $formattedData
                ])->header('Access-Control-Allow-Origin', '*')
                  ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
                  ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
            } else {
                return response()->json([
                    'status' => 'not_found',
                    'message' => $result['error'] ?? 'Address not found',
                    'data' => null
                ], 404);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Invalid input: ' . implode(', ', $e->validator->errors()->all()),
                'data' => null
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while searching for the address.',
                'data' => null
            ], 500);
        }
    }

    // /**
    //  * @OA\Get(
    //  *     path="/api/address/status",
    //  *     tags={"Address"},
    //  *     summary="Get OneMap API status",
    //  *     description="Retrieve status information for the OneMap API",
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Status retrieved",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="message", type="string", example="OneMap API status retrieved successfully"),
    //  *             @OA\Property(property="data", type="object")
    //  *         )
    //  *     )
    //  * )
    //  */
    public function status(): JsonResponse
    {
        $status = $this->oneMapService->getApiStatus();
        
        return response()->json([
            'status' => 'success',
            'message' => 'OneMap API status retrieved successfully',
            'data' => $status
        ]);
    }
}