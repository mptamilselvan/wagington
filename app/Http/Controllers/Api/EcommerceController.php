<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use App\Services\ECommerceService;

/**
 * @OA\Tag(
 *   name="E-commerce",
 *   description="Catalog, filters, cart, checkout, and orders APIs"
 * )
 */
class EcommerceController extends Controller
{
    public function __construct(private ECommerceService $svc) {}

    /**
     * @OA\Get(
     *   path="/api/ecommerce/landing",
     *   summary="Get landing page sections",
     *   tags={"E-commerce"},
     *   security={{},{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Landing page sections retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Landing page sections retrieved successfully."),
     *       @OA\Property(property="sections", type="array", @OA\Items(type="object"))
     *     )
     *   )
     * )
     */
    public function landing()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Landing page sections retrieved successfully.',
            'sections' => $this->svc->getLandingSections(),
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/ecommerce/products",
     *   summary="List products",
     *   tags={"E-commerce"},
     *   security={{},{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="category_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="catalog_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="shippable", in="query", required=false, description="Filter by shippable status", @OA\Schema(type="string", enum={"true", "false"})),
     *   @OA\Parameter(name="q", in="query", required=false, description="Search query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort", in="query", required=false, description="Sort order", @OA\Schema(type="string", enum={"newest", "price_asc", "price_desc"})),
     *   @OA\Response(
     *     response=200,
     *     description="Products retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Products retrieved successfully."),
     *       @OA\Property(property="products", type="object")
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {
        $list = $this->svc->listProducts($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Products retrieved successfully.',
            'products' => $list
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/ecommerce/filters",
     *   summary="Get dynamic filters for category",
     *   tags={"E-commerce"},
     *   security={{},{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Parameter(name="category_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Response(
     *     response=200,
     *     description="Filters retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Filters retrieved successfully."),
     *       @OA\Property(property="filters", type="object")
     *     )
     *   )
     * )
     */
    public function filters(Request $request)
    {
        // Pass null when no category filter is provided
        $categoryId = $request->filled('category_id')
            ? $request->integer('category_id')
            : null;
        $filters = $this->svc->buildFilters($categoryId);
        return response()->json([
            'status' => 'success',
            'message' => 'Filters retrieved successfully.',
            'filters' => $filters
        ]);
    }

    // /**
    //  * @OA\Get(
    //  *   path="/api/ecommerce/category-page",
    //  *   summary="Get category page with products and filters",
    //  *   tags={"E-commerce"},
    //  *   @OA\Parameter(name="category_id", in="query", required=false, @OA\Schema(type="integer")),
    //  *   @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
    //  *   @OA\Parameter(name="q", in="query", required=false, description="Search query", @OA\Schema(type="string")),
    //  *   @OA\Parameter(name="sort", in="query", required=false, description="Sort order", @OA\Schema(type="string", enum={"newest", "price_asc", "price_desc"})),
    //  *   @OA\Response(response=200, description="OK")
    //  * )
    //  */
    // public function categoryPage(Request $request)
    // {
    //     $params = $request->all();
    //     // Preserve nullable semantics: if client didn't include category_id, use null
    //     $category = $request->has('category_id') ? (int) $request->get('category_id') : null;
    //     // Ensure listProducts sees the nullable category_id instead of defaulting to 0
    //     $params['category_id'] = $category;
    //     $list = $this->svc->listProducts($params);
    //     $filters = $this->svc->buildFilters($category);
    //     return response()->json([
    //         'filters' => $filters,
    //         'products' => $list,
    //     ]);
    // }

    /**
     * @OA\Get(
     *   path="/api/ecommerce/products/{slug}",
     *   summary="Get product details",
     *   tags={"E-commerce"},
     *   security={{},{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(
     *     response=200,
     *     description="Product details retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Product details retrieved successfully."),
     *       @OA\Property(property="product", type="object"),
     *       @OA\Property(property="attribute_meta", type="object"),
     *       @OA\Property(property="recommendations", type="array", @OA\Items(type="object"))
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Product not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Product not found.")
     *     )
     *   )
     * )
     */
    public function show(string $slug)
    {
        try {
            $product = $this->svc->getProductBySlug($slug);
            return response()->json([
                'status' => 'success',
                'message' => 'Product details retrieved successfully.',
                'product' => $this->svc->presentDetail($product),
                'attribute_meta' => $this->svc->getAttributeMeta($product),
                'recommendations' => $this->svc->getRecommendations($product),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found.'
            ], 404);
        }
    }

    /**
     * @OA\Get(
     *   path="/api/ecommerce/cart",
     *   summary="Get current cart",
     *   tags={"E-commerce"},
     *   security={{},{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Cart retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Cart retrieved successfully."),
     *       @OA\Property(property="cart", type="object")
     *     )
     *   )
     * )
     */
    public function cart()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Cart retrieved successfully.',
            'cart' => $this->svc->getCart()
        ]);
    }

    /**
 * @OA\Post(
 *   path="/api/ecommerce/cart/items",
 *   summary="Add item to cart",
 *   tags={"E-commerce"},
 *   security={{},{"bearerAuth":{}}},
 *   @OA\Parameter(
 *     name="Accept",
 *     in="header",
 *     required=true,
 *     @OA\Schema(type="string", default="application/json")
 *   ),
 *   @OA\RequestBody(
 *     required=true,
 *     @OA\JsonContent(
 *       required={"variant_id", "qty"},
 *       @OA\Property(property="variant_id", type="integer", example=4),
 *       @OA\Property(property="qty", type="integer", example=1),
 *       @OA\Property(
 *         property="addons",
 *         type="array",
 *         @OA\Items(
 *           type="object",
 *           @OA\Property(property="product_id", type="integer", example=1),
 *           @OA\Property(property="variant_id", type="integer", example=1),
 *           @OA\Property(property="qty", type="integer", example=1)
 *         )
 *       ),
 *       example={
 *         "variant_id": 4,
 *         "qty": 1,
 *         "addons": {
 *           {
 *             "product_id": 1,
 *             "variant_id": 1,
 *             "qty": 1
 *           },
 *           {
 *             "product_id": 2,
 *             "variant_id": 2,
 *             "qty": 1
 *           }
 *         }
 *       }
 *     )
 *   ),
 *   @OA\Response(
 *     response=200,
 *     description="Item added to cart successfully",
 *     @OA\JsonContent(
 *       @OA\Property(property="status", type="string", example="success"),
 *       @OA\Property(property="message", type="string", example="Item added to cart successfully."),
 *       @OA\Property(property="cart", type="object")
 *     )
 *   ),
 *   @OA\Response(
 *     response=422,
 *     description="Validation error",
 *     @OA\JsonContent(
 *       @OA\Property(property="status", type="string", example="error"),
 *       @OA\Property(property="message", type="string", example="Validation failed.")
 *     )
 *   )
 * )
 */
    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'qty' => 'required|integer|min:1',
            'addons' => 'array',
        ]);

        try {
            $cart = $this->svc->addToCart(
                $data['variant_id'],
                $data['qty'],
                $data['addons'] ?? []
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Item added to cart successfully.',
                'cart' => $cart
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // /**
    //  * @OA\Patch(
    //  *   path="/api/ecommerce/cart/items/{id}",
    //  *   summary="Update cart item quantity",
    //  *   tags={"E-commerce"},
    //  *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
    //  *   @OA\RequestBody(
    //  *     required=true,
    //  *     @OA\JsonContent(@OA\Property(property="qty", type="integer", example=2))
    //  *   ),
    //  *   @OA\Response(response=200, description="OK")
    //  * )
    //  */
    // public function updateCartItem(string $id, Request $request)
    // {
    //     $data = $request->validate(['qty' => 'required|integer|min:1']);
    //     return response()->json($this->svc->updateCartItem($id, $data['qty']));
    // }

    /**
     * @OA\Delete(
     *   path="/api/ecommerce/cart/items/{id}",
     *   summary="Remove cart item",
     *   tags={"E-commerce"},
     *   security={{},{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(
     *     response=200,
     *     description="Cart item removed successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Item removed from cart successfully."),
     *       @OA\Property(property="cart", type="object")
     *     )
     *   )
     * )
     */
    public function removeCartItem(string $id)
    {
        $cart = $this->svc->removeCartItem($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Item removed from cart successfully.',
            'cart' => $cart
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/ecommerce/invoice/{paymentId}/download",
     *   summary="Download invoice PDF for an order",
     *   tags={"E-commerce"},
     *   security={{},{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="Accept",
     *     in="header",
     *     required=true,
     *     @OA\Schema(type="string", default="application/json")
     *   ),
     *   @OA\Parameter(name="paymentId", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(
     *     response=200,
     *     description="Invoice PDF downloaded successfully",
     *     @OA\MediaType(
     *       mediaType="application/pdf"
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Unauthorized"
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Invoice PDF not available"
     *   )
     * )
     * 
     * Download invoice PDF for an order
     * 
     * @param string $paymentId
     * @return \Illuminate\Http\Response
     */
    public function downloadInvoice(string $paymentId)
    {
        // For web requests (browser clicks), check web session authentication
        // For API requests, check API token authentication
        $isWebRequest = !request()->expectsJson();
        $isWebAuthenticated = Auth::check();
        $isApiAuthenticated = Auth::guard('api')->check();
        
        // Debug logging
        if (config('app.debug')) {
            \Log::info('Invoice download attempt', [
                'payment_id' => $paymentId,
                'is_web_request' => $isWebRequest,
                'web_auth' => $isWebAuthenticated,
                'api_auth' => $isApiAuthenticated,
            ]);
        }
        
        // Check authentication based on request type
        $isAuthenticated = $isWebRequest ? $isWebAuthenticated : $isApiAuthenticated;
        
        // If not authenticated, return appropriate response
        if (!$isAuthenticated) {
            if (request()->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
            } else {
                // For web requests, redirect to login
                return redirect()->route('customer.login');
            }
        }
        
        // Get the authenticated user ID
        $userId = $isWebRequest ? Auth::id() : Auth::guard('api')->id();
        
        try {
            // Find the payment record
            $payment = Payment::findOrFail($paymentId);
            
            // Ensure the user owns this payment
            $order = $payment->order;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::info('Invoice download failed - Payment not found', [
                'payment_id' => $paymentId,
                'user_id' => $userId
            ]);
            
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment record not found'
                ], 404);
            } else {
                abort(404, 'Payment record not found');
            }
        }
        if (!$order || $order->user_id !== $userId) {
            \Log::warning('Unauthorized invoice download attempt', [
                'user_id' => $userId,
                'order_user_id' => $order->user_id ?? null,
                'payment_id' => $paymentId
            ]);
            
            if (request()->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
            } else {
                abort(403, 'Unauthorized');
            }
        }
        
        // Check if we have a PDF URL
        if (!$payment->invoice_pdf_url) {
            if (request()->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Invoice PDF not available'], 404);
            } else {
                abort(404, 'Invoice PDF not available');
            }
        }
        
        try {
            // Fetch the PDF content
            $response = Http::timeout(30)->get($payment->invoice_pdf_url);
            
            if ($response->failed()) {
                if (request()->expectsJson()) {
                    return response()->json(['status' => 'error', 'message' => 'Failed to fetch invoice PDF'], 500);
                } else {
                    abort(500, 'Failed to fetch invoice PDF');
                }
            }
            
            // Sanitize the order number for use in filename
            $sanitizedOrderNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', $order->order_number);
            
            // Return the PDF with proper headers for download
            return response($response->body())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="invoice-' . $sanitizedOrderNumber . '.pdf"');
        } catch (\Exception $e) {
            \Log::error('Invoice download failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            
            if (request()->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Failed to download invoice'], 500);
            } else {
                abort(500, 'Failed to download invoice');
            }
        }
    }
}