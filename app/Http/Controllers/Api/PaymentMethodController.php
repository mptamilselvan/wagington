<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Payment Methods",
 *     description="API endpoints for managing customer payment methods"
 * )
 */
class PaymentMethodController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * @OA\Get(
     *     path="/api/payment-methods",
     *     summary="Get user's payment methods",
     *     description="Retrieve all payment methods for the authenticated user",
     *     operationId="getPaymentMethods",
     *     tags={"Payment Methods"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Payment methods retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Payment methods retrieved successfully"),
     *             @OA\Property(
     *                 property="payment_methods",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", example="pm_1234567890"),
     *                     @OA\Property(property="brand", type="string", example="visa"),
     *                     @OA\Property(property="last4", type="string", example="4242"),
     *                     @OA\Property(property="exp_month", type="integer", example=12),
     *                     @OA\Property(property="exp_year", type="integer", example=2025),
     *                     @OA\Property(property="is_default", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="has_payment_methods", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve payment methods")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $result = $this->paymentService->getPaymentMethods($user);
            
            // Get default payment method
            $defaultResult = $this->paymentService->getDefaultPaymentMethod($user);
            $defaultPaymentMethodId = $defaultResult['status'] === 'success' && $defaultResult['default_payment_method'] 
                ? $defaultResult['default_payment_method']['id'] 
                : null;

            // Mark default payment method
            if ($result['status'] === 'success' && $defaultPaymentMethodId) {
                foreach ($result['payment_methods'] as &$method) {
                    $method['is_default'] = $method['id'] === $defaultPaymentMethodId;
                }
            }

            return response()->json([
                'status' => $result['status'],
                'message' => $result['message'],
                'payment_methods' => $result['payment_methods'] ?? [],
                'has_payment_methods' => !empty($result['payment_methods'])
            ], $result['status'] === 'success' ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/payment-methods/{paymentMethodId}",
     *     summary="Delete payment method",
     *     description="Delete a specific payment method from the user's account",
     *     operationId="deletePaymentMethod",
     *     tags={"Payment Methods"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="paymentMethodId",
     *         in="path",
     *         required=true,
     *         description="The ID of the payment method to delete",
     *         @OA\Schema(type="string", example="pm_1234567890")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Payment method deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Payment method not found or cannot be deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to delete payment method")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, string $paymentMethodId): JsonResponse
    {
        try {
            $user = $request->user();
            $result = $this->paymentService->deletePaymentMethod($user, $paymentMethodId);

            return response()->json([
                'status' => $result['status'],
                'message' => $result['message']
            ], $result['status'] === 'success' ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payment-methods/{paymentMethodId}/set-default",
     *     summary="Set payment method as default",
     *     description="Set a specific payment method as the default for the user's account",
     *     operationId="setDefaultPaymentMethod",
     *     tags={"Payment Methods"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="paymentMethodId",
     *         in="path",
     *         required=true,
     *         description="The ID of the payment method to set as default",
     *         @OA\Schema(type="string", example="pm_1234567890")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Default payment method set successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Default payment method set successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Payment method not found or cannot be set as default")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to set default payment method")
     *         )
     *     )
     * )
     */
    public function setDefault(Request $request, string $paymentMethodId): JsonResponse
    {
        try {
            $user = $request->user();
            $result = $this->paymentService->setDefaultPaymentMethod($user, $paymentMethodId);

            return response()->json([
                'status' => $result['status'],
                'message' => $result['message']
            ], $result['status'] === 'success' ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to set default payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payment-methods/create",
     *     summary="Create payment method from token or payment method ID",
     *     description="Create a payment method using either a Stripe token (legacy) or payment method ID (modern). This endpoint supports both web and mobile implementations.",
     *     operationId="createPaymentMethod",
     *     tags={"Payment Methods"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", description="Stripe token or payment method id", example="tok_1S6340AE9pDK20WeeFM3RZpm", pattern="^(tok_|pm_)[A-Za-z0-9_]+$"),
     *             @OA\Property(property="default", type="boolean", description="Set as default payment method", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment method created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Payment method created successfully"),
     *             @OA\Property(property="payment_method", type="object",
     *                 @OA\Property(property="id", type="string", example="pm_1234567890"),
     *                 @OA\Property(property="exp_month", type="integer", example=12),
     *                 @OA\Property(property="exp_year", type="integer", example=2025),
     *                 @OA\Property(property="last4", type="string", example="4242"),
     *                 @OA\Property(property="funding", type="string", example="credit"),
     *                 @OA\Property(property="brand", type="string", example="visa"),
     *                 @OA\Property(property="default", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid token or payment method ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to create payment method")
     *         )
     *     )
     * )
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validate([
                'token' => ['required', 'string', 'regex:/^(tok_|pm_)[A-Za-z0-9_]+$/'],
                'default' => ['nullable', 'boolean']
            ]);

            $token = $validated['token'];
            $setAsDefault = (bool)($validated['default'] ?? false);

            $result = $this->paymentService->createPaymentMethodFromToken($user, $token, $setAsDefault);

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment method created successfully',
                    'payment_method' => $result['payment_method']
                ], 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}