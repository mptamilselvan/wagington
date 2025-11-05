<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ECommerceService;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;

/**
 * Customer Order APIs
 *
 * @OA\Tag(
 *   name="E-commerce",
 *   description="Catalog, filters, cart, checkout, and orders APIs"
 * )
 */
class OrderController extends Controller
{
    public function __construct(
        private ECommerceService $svc,
        private InvoiceService $invoiceService
    ) {}

    /**
     * @OA\Get(
     *   path="/api/ecommerce/orders",
     *   summary="List authenticated user's orders",
     *   tags={"E-commerce"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Response(
     *     response=200,
     *     description="Orders retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Orders retrieved successfully."),
     *       @OA\Property(property="orders", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Unauthenticated.")
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = (int)($request->query('per_page', 10));
        $orders = $this->svc->listUserOrders($user->id, $perPage);
        return response()->json([
            'status' => 'success',
            'message' => 'Orders retrieved successfully.',
            'orders' => $orders
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/ecommerce/orders/{orderNumber}",
     *   summary="Get order details by order number (own orders only)",
     *   tags={"E-commerce"},
     *   security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Parameter(name="orderNumber", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(
     *     response=200,
     *     description="Order details retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Order details retrieved successfully."),
     *       @OA\Property(property="order", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Unauthenticated.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Order not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Order not found.")
     *     )
     *   )
     * )
     */
    public function show(string $orderNumber)
    {
        $user = Auth::user();
        try {
            $detail = $this->svc->getUserOrderDetail($user->id, $orderNumber);
            return response()->json([
                'status' => 'success',
                'message' => 'Order details retrieved successfully.',
                'order' => $detail
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve order details', [
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve order details.'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *   path="/api/thank-you/{orderNumber}",
     *   summary="Get order details for thank you page",
     *   tags={"E-commerce"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Parameter(name="orderNumber", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(
     *     response=200,
     *     description="Order details retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Order details retrieved successfully."),
     *       @OA\Property(property="order", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Unauthenticated.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Order not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Order not found.")
     *     )
     *   )
     * )
     */
    public function thankYou(string $orderNumber)
    {
        return $this->show($orderNumber);
    }
    
    /**
     * @OA\Post(
     *   path="/api/ecommerce/orders/{orderNumber}/generate-invoice",
     *   summary="Generate invoice for an order",
     *   tags={"E-commerce"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Parameter(name="orderNumber", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Invoice generated successfully"),
     *   @OA\Response(response=404, description="Order not found"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=403, description="Forbidden - Order doesn't belong to user")
     * )
     */
    public function generateInvoice(string $orderNumber)
    {
        $user = Auth::user();
        
        try {
            // Begin transaction and get the order with lock
            $result = \DB::transaction(function() use ($user, $orderNumber) {
                // Find the order that belongs to the user inside transaction
                $order = Order::where('order_number', $orderNumber)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                // Get and lock the first payment for this order
                $payment = $order->payments()
                    ->lockForUpdate()
                    ->first();
                
                if (!$payment) {
                    throw new \RuntimeException('No payment found for this order');
                }
                
                // Check if invoice already exists
                if (!empty($payment->invoice_url) && !empty($payment->invoice_pdf_url)) {
                    return [
                        'exists' => true,
                        'payment' => $payment
                    ];
                }
                
                // Generate and save the invoice
                $result = $this->invoiceService->saveInvoiceAndUpdatePayment($order, $payment);
                
                if (!$result) {
                    throw new \RuntimeException('Failed to generate invoice');
                }
                
                $payment->refresh();
                return [
                    'exists' => false,
                    'payment' => $payment
                ];
            });
            
            // Handle the result outside the transaction
            $payment = $result['payment'];
            return response()->json([
                'status' => 'success',
                'message' => $result['exists'] ? 'Invoice already exists' : 'Invoice generated successfully',
                'invoice_url' => $payment->invoice_url,
            ]);
            
        } catch (\InvalidArgumentException | \Illuminate\Validation\ValidationException $e) {
            Log::warning('Invalid data while generating invoice', [
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid order data: ' . $e->getMessage()
            ], 422);
            
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error while generating invoice', [
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $isDeadlock = str_contains($e->getMessage(), 'Deadlock') || 
                         str_contains($e->getMessage(), 'Lock wait timeout');
            
            return response()->json([
                'status' => 'error',
                'message' => $isDeadlock ? 
                    'Service temporarily unavailable, please try again' : 
                    'Internal server error while processing invoice'
            ], $isDeadlock ? 503 : 500);
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('External service error while generating invoice', [
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'has_response' => $e->hasResponse(),
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'External service unavailable, please try again later'
            ], 502);
            
        } catch (\Exception $e) {
            Log::error('Unexpected error while generating invoice', [
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while generating the invoice'
            ], 500);
        }
    }
}