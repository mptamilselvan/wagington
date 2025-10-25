<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Services\ImageService;

/**
 * @OA\Tag(
 *     name="Health",
 *     description="Service health endpoints"
 * )
 */
class HealthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/health/storage",
     *     tags={"Health"},
     *     summary="Storage health check",
     *     description="Checks connectivity to the configured storage disk",
     *     @OA\Response(
     *         response=200,
     *         description="Storage is healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Storage is healthy"),
     *             @OA\Property(property="healthy", type="boolean", example=true),
     *             @OA\Property(property="disk", type="string", example="do_spaces")
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Storage is unhealthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Storage is unhealthy"),
     *             @OA\Property(property="healthy", type="boolean", example=false),
     *             @OA\Property(property="disk", type="string", example="do_spaces")
     *         )
     *     )
     * )
     */
    public function storage(): JsonResponse
    {
        $healthy = ImageService::storageHealthCheck();
        $disk = ImageService::getPreferredDisk();

        if ($healthy) {
            return response()->json([
                'status' => 'success',
                'message' => 'Storage is healthy',
                'healthy' => true,
                'disk' => $disk,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Storage is unhealthy',
            'healthy' => false,
            'disk' => $disk,
        ], 503);
    }
}